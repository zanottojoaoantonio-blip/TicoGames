const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { test } = require('node:test');

const front = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(front, file), 'utf8');
const validationFiles = [
    'comum', 'usuario', 'category', 'platform', 'genre', 'product',
    'address', 'cart-item', 'cart', 'order-item', 'order', 'admin',
];
const localScripts = page => [...read(page).matchAll(/<script\b[^>]*src="(assets\/js\/[^"?]+)(?:\?[^"\s]*)?"[^>]*>/g)];
const product = {
    id: 1, name: 'Jogo', image_path: '', stock: 2, is_active: true, price: '19.90',
    description: 'Descrição', platform: { name: 'PC' }, categories: [], genres: [],
};
const cart = {
    items: [{ id: 1, product_id: 1, quantity: 1, subtotal: '19.90', product }],
    total: '19.90', subtotal: '19.90', shipping_cost: '0.00',
};
const user = { id: 1, name: 'Cliente', role: 'customer' };

async function loadStore() {
    const axios = {
        defaults: {},
        get: async url => ({ data: { data: url === '/api/me' ? user : cart } }),
    };
    const context = vm.createContext({ window: new EventTarget(), Event, axios, URLSearchParams, FormData });

    for (const [, file] of localScripts('index.html')) {
        if (!file.includes('/dom/')) {
            vm.runInContext(read(file), context, { filename: file });
        }
    }

    const [validacoes, store, produto, usuario, admin] = vm.runInContext('[Validacoes, Store, ProdutoAPI, UsuarioAPI, AdminAPI]', context);
    await store.ready;
    return { validacoes, store, axios, produto, usuario, admin };
}

test('valida IDs, quantidades, preços e respostas antes de usá-los', async () => {
    const { validacoes: v, store } = await loadStore();

    assert.equal(v.urlId('1'), true);
    assert.equal(v.urlId(String(Number.MAX_SAFE_INTEGER)), true);
    for (const value of [null, '', '0', '-1', '01', '1.5', ' 1', '1\n', '1e2', '9007199254740992']) {
        assert.equal(v.urlId(value), false, String(value));
    }
    assert.equal(v.quantity(4294967295), true);
    for (const value of [0, -1, 1.5, '1', 4294967296]) {
        assert.equal(v.quantity(value), false);
    }
    assert.equal(v.cents('99999999999999999999.99'), 9999999999999999999999n);
    assert.equal(store.money(v.cents('19.90')), 'R$ 19,90');
    for (const value of [19.90, '-1.00', '1.9', '1,90', '1e2', '100000000000000000000.00']) {
        assert.throws(() => v.cents(value), /Preço inválido/);
    }

    assert.equal(v.product(product), product);
    assert.equal(v.account(user), user);
    v.productDetails(product, 1);
    v.cart(cart);
    assert.throws(() => v.account({ ...user, role: 'root' }), /Conta inválida/);
    assert.throws(() => v.product({ ...product, stock: -1 }), /Produto inválido/);
    assert.throws(() => v.productDetails(product, 2), /dados esperados/);
    assert.throws(() => v.productDetails({ ...product, genres: [null] }, 1), /dados esperados/);
    assert.throws(() => store.setCart({ ...cart, total: 'inválido' }), /Preço inválido/);
    assert.equal(store.cartTotal, cart.total);
    assert.equal(store.cart, cart.items);
    assert.throws(() => v.cart({ ...cart, items: [{ ...cart.items[0], product_id: 2 }] }), /Carrinho inválido/);
});

test('valida listas, pedidos e endereços usados pelas páginas', async () => {
    const { validacoes: v } = await loadStore();
    const address = { id: 1, ...Object.fromEntries(v.addressFields.map(field => [field, 'Texto'])) };
    const order = {
        id: 1, customer_number: 1, status: 'pending_payment', total: '19.90',
        items: [{ id: 1, product_name: 'Jogo', quantity: 1, unit_price: '19.90' }],
        ...Object.fromEntries(v.addressFields.map(field => [`shipping_${field}`, 'Texto'])),
    };

    for (const model of ['categories', 'platforms', 'genres']) {
        v[model]([{ id: 1, name: 'Nome' }]);
        assert.throws(() => v[model](null), /Opções inválidas/);
        assert.throws(() => v[model]([{ id: 0, name: 'Nome' }]), /Opções inválidas/);
        assert.throws(() => v[model]([{ id: 1, name: null }]), /Opções inválidas/);
    }
    v.products([product]);
    v.addresses([address]);
    v.orders([order]);
    v.catalogPage([product], { last_page: 1 });
    assert.equal(v.order(order), order);
    assert.equal(v.admin(user), false);
    assert.equal(v.admin({ ...user, role: 'admin' }), true);
    assert.equal(v.admin(null), false);
    assert.equal(v.adminProduct(product, product.id), product);
    assert.throws(() => v.adminProduct(product, 2), /Produto inesperado/);
    assert.throws(() => v.products([{ ...product, categories: null }]), /Relacionamentos inválidos/);
    assert.throws(() => v.addresses([{ ...address, city: null }]), /Endereços inválidos/);
    assert.throws(() => v.selectedAddress(address, 2), /Endereço inesperado/);
    assert.throws(() => v.savedAddress({ id: 0 }), /Endereço inválido/);
    assert.throws(() => v.orders({}), /Lista de pedidos inválida/);
    assert.throws(() => v.order({ ...order, shipping_street: null }), /Endereço histórico inválido/);
    assert.throws(() => v.order({ ...order, items: [] }), /Pedido inválido/);
    assert.throws(() => v.orderItem({ ...order.items[0], quantity: 0 }), /Pedido inválido/);
    assert.throws(() => v.selectedOrder(order, 2), /Pedido inesperado/);
    assert.throws(() => v.orderResult(order, 1, 'paid'), /Resultado inesperado/);
    assert.throws(() => v.createdOrder({ ...order, status: 'paid' }), /Estado inesperado/);
    assert.throws(() => v.catalogPage([], {}), /Página inválida/);
});

test('clientes separam catálogo, conta e operações administrativas', async () => {
    const { store, produto, usuario, admin } = await loadStore();
    const calls = [];
    store.request = async (...args) => {
        calls.push(args);
        return product;
    };
    await produto.list({ page: 2, q: 'Jogo & DLC' });
    assert.equal(calls.at(-1)[3], true);
    const url = new URL(calls.at(-1)[1], 'http://localhost');
    assert.equal(url.searchParams.get('q'), 'Jogo & DLC');
    assert.equal(url.searchParams.get('page'), '2');

    await usuario.addCartItem(3, 2);
    assert.equal(calls.at(-1)[0], 'post');
    assert.equal(calls.at(-1)[1], '/api/cart/items');
    assert.equal(calls.at(-1)[2].product_id, 3);
    assert.equal(calls.at(-1)[2].quantity, 2);
    await usuario.saveAddress(null, { city: 'Recife' });
    assert.equal(calls.at(-1)[0], 'post');
    assert.equal(calls.at(-1)[1], '/api/addresses');
    await usuario.saveAddress(7, { city: 'Recife' });
    assert.equal(calls.at(-1)[0], 'patch');
    assert.equal(calls.at(-1)[1], '/api/addresses/7');
    await usuario.orderAction(4, 'pay');
    assert.equal(calls.at(-1)[0], 'post');
    assert.equal(calls.at(-1)[1], '/api/orders/4/pay');
    await admin.updateOrderStatus(4, 'shipped');
    assert.equal(calls.at(-1)[0], 'patch');
    assert.equal(calls.at(-1)[1], '/api/orders/4/status');
    assert.equal(calls.at(-1)[2].status, 'shipped');
});

test('admin preserva upload, criação e atualização de produtos', async () => {
    const { store, admin } = await loadStore();
    const calls = [];
    store.request = async (method, url, data) => {
        calls.push({ method, url, data });
        return url === '/api/product-images' ? { image_path: '/storage/jogo.png' } : product;
    };
    const payload = { ...product };
    const image = new Blob(['imagem'], { type: 'image/png' });
    assert.equal(await admin.saveProduct(null, payload, image), product);
    assert.deepEqual(calls.map(call => [call.method, call.url]), [
        ['post', '/api/product-images'], ['post', '/api/products'],
    ]);
    assert.ok(calls[0].data.get('image') instanceof Blob);
    assert.equal(calls[1].data.image_path, '/storage/jogo.png');

    calls.length = 0;
    await admin.saveProduct(1, payload);
    assert.deepEqual(calls.map(call => [call.method, call.url]), [['patch', '/api/products/1']]);
    store.request = async () => { throw new Error('Upload falhou'); };
    await assert.rejects(admin.saveProduct(1, payload, image), /Upload falhou/);
});

test('mantém sessão, envelopes, CSRF e escritas sem repetição automática', async () => {
    const { store, axios } = await loadStore();
    assert.equal(store.user, user);
    assert.equal(store.cartLoaded, true);
    assert.equal(axios.defaults.withCredentials, true);
    assert.equal(axios.defaults.withXSRFToken, true);
    assert.equal(axios.defaults.baseURL, '');

    const calls = [];
    const envelope = { data: [product], meta: { last_page: 1 } };
    axios.get = async (url, config) => {
        calls.push({ url, config });
        return { data: envelope };
    };
    axios.request = async config => {
        calls.push(config);
        return { status: 204 };
    };

    assert.equal(await store.request('get', '/api/products'), envelope.data);
    assert.equal(await store.request('get', '/api/products', undefined, true), envelope);
    calls.length = 0;
    assert.equal(await store.request('delete', '/api/cart'), null);
    assert.deepEqual(calls.map(call => call.url), ['/sanctum/csrf-cookie', '/api/cart']);
    assert.equal(calls[0].config.timeout, 15000);
    assert.equal(calls[1].headers.Accept, 'application/json');

    calls.length = 0;
    axios.request = async config => {
        calls.push(config);
        throw new Error('Resposta perdida');
    };
    await assert.rejects(store.request('post', '/api/orders', { address_id: 1 }), /Resposta perdida/);
    assert.deepEqual(calls.map(call => call.url), ['/sanctum/csrf-cookie', '/api/orders']);
});

test('todas as páginas carregam os scripts existentes na ordem necessária', async () => {
    const { validacoes, store, produto, usuario, admin } = await loadStore();
    const clients = { ProdutoAPI: produto, UsuarioAPI: usuario, AdminAPI: admin };
    const prefix = [
        'assets/js/requisicoes/config.js',
        ...validationFiles.map(name => `assets/js/validacoes/${name}.js`),
        ...['produto', 'usuario', 'admin'].map(name => `assets/js/requisicoes/${name}.js`),
        'assets/js/requisicoes/store.js', 'assets/js/dom/main.js',
    ];
    const pages = fs.readdirSync(front).filter(file => file.endsWith('.html'));
    assert.equal(pages.length, 8);

    for (const page of pages) {
        const scripts = localScripts(page);
        assert.deepEqual(scripts.slice(0, prefix.length).map(match => match[1]), prefix, page);

        for (const [tag, file] of scripts) {
            assert.match(tag, /\bdefer\b/, file);
            const source = read(file);
            new vm.Script(source, { filename: file });
            for (const [, name] of source.matchAll(/\bValidacoes\.(\w+)/g)) {
                assert.ok(name in validacoes, `${file}: Validacoes.${name}`);
            }
            for (const [, name] of source.matchAll(/\bStore\.(\w+)/g)) {
                assert.ok(name in store || ['notice', 'confirm', 'fieldErrors', 'authenticating'].includes(name), `${file}: Store.${name}`);
            }
            for (const [, client, name] of source.matchAll(/\b(ProdutoAPI|UsuarioAPI|AdminAPI)\.(\w+)/g)) {
                assert.equal(typeof clients[client][name], 'function', `${file}: ${client}.${name}`);
            }
            if (file.includes('/dom/')) {
                assert.doesNotMatch(source, /Store\.request|\/api\//, file);
            }
        }
    }

    assert.match(read('Dockerfile'), /NGINX_ENVSUBST_OUTPUT_DIR=\/usr\/share\/nginx\/html\/assets\/js\/requisicoes\b/);
    assert.match(read('nginx.conf'), /location = \/assets\/js\/requisicoes\/config\.js/);
});
