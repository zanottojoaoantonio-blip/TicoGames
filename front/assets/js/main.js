Store.notice = (text, error = false) => {
    const status = document.getElementById('global-status');
    status.textContent = text;
    status.className = `container my-3 alert ${error ? 'alert-danger' : 'alert-success'}`;
    status.hidden = !text;
};

Store.confirm = (title, text, label = 'Confirmar') => new Promise(resolve => {
    const modal = document.getElementById('preview-modal');
    const button = document.getElementById('preview-modal-confirm');
    let confirmed = false;

    document.getElementById('preview-modal-title').textContent = title;
    document.getElementById('preview-modal-text').textContent = text;
    button.textContent = label;
    button.onclick = () => {
        confirmed = true;
    };

    modal.addEventListener('hidden.bs.modal', () => {
        button.onclick = null;
        resolve(confirmed);
    }, { once: true });

    bootstrap.Modal.getOrCreateInstance(modal).show();
});

Store.fieldErrors = (form, error) => {
    let errors = {};
    let firstInvalid;

    if (error.response?.status === 422) {
        errors = error.response.data?.errors ?? {};
    }

    for (const input of form.querySelectorAll('[name]')) {
        const messages = errors[input.name] ?? errors[input.name.replace('[]', '')];
        let message = '';

        if (Array.isArray(messages)) {
            message = messages.find(value => typeof value === 'string') || '';
        }

        input.classList.toggle('is-invalid', Boolean(message));
        input.setAttribute('aria-invalid', message ? 'true' : 'false');

        const feedback = document.getElementById(`${input.id}-error`);

        if (feedback) {
            feedback.textContent = message;
        }

        if (message && !firstInvalid) {
            firstInvalid = input;
        }
    }

    return firstInvalid;
};

function renderSession() {
    const user = Store.user;
    let accountText = 'Entrar ou cadastrar';

    if (Store.sessionError) {
        accountText = 'Verificar acesso';
    } else if (!Store.sessionLoaded) {
        accountText = 'Verificando conta...';
    } else if (user) {
        accountText = `Olá, ${user.name}`;
    }

    for (const link of document.querySelectorAll('[data-account]')) {
        link.textContent = accountText;
        link.href = user ? 'pedidos.html' : 'login.html';
        link.title = user ? user.name : '';
    }

    for (const link of document.querySelectorAll('[data-orders-link]')) {
        link.textContent = user?.role === 'admin' ? 'Pedidos' : 'Meus pedidos';
    }

    for (const element of document.querySelectorAll('[data-signed-in]')) {
        element.hidden = !user;
    }

    for (const button of document.querySelectorAll('[data-logout]')) {
        button.disabled = Store.busy;
    }

    for (const element of document.querySelectorAll('[data-admin-only]')) {
        element.hidden = user?.role !== 'admin';
    }

    const merge = document.getElementById('guest-merge');

    if (merge) {
        merge.hidden = true;
    }
}

function createCartForm(item) {
    const product = item.product;
    const form = document.createElement('form');
    form.className = 'd-flex flex-wrap gap-2 align-items-center';

    const input = document.createElement('input');
    input.type = 'number';
    input.min = '0';
    input.max = String(product?.stock || 1);
    input.step = '1';
    input.value = item.quantity;
    input.className = 'form-control quantity-input';
    input.setAttribute('aria-label', `Quantidade de ${product?.name ?? 'produto indisponível'}`);
    input.disabled = Store.busy || !product?.is_active || !product.stock;

    const updateButton = document.createElement('button');
    updateButton.type = 'submit';
    updateButton.className = 'btn btn-sm btn-outline-primary';
    updateButton.textContent = 'Atualizar';
    updateButton.disabled = input.disabled;

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-sm btn-outline-danger';
    removeButton.textContent = 'Remover';
    removeButton.disabled = Store.busy;

    form.addEventListener('submit', async event => {
        event.preventDefault();

        try {
            await Store.update(item.id, Number(input.value));
            Store.notice('Quantidade atualizada.');
        } catch (error) {
            const message = error.message && !error.response ? error.message : Store.error(error);
            Store.notice(message, true);
        }
    });

    removeButton.addEventListener('click', async () => {
        try {
            await Store.remove(item.id);
            Store.notice('Item removido.');
        } catch (error) {
            Store.notice(Store.error(error), true);
        }
    });

    form.append(input, updateButton, removeButton);
    return form;
}

function cartRow(item, mini) {
    const product = item.product;
    const row = document.createElement('article');
    row.className = mini ? 'mini-cart-item d-block' : 'cart-product';

    if (!mini) {
        const image = document.createElement('img');
        image.className = 'cart-product-art';
        image.width = 100;
        image.height = 100;
        image.alt = '';
        image.addEventListener('error', () => {
            image.src = 'assets/images/product-placeholder.svg';
        }, { once: true });
        image.src = product?.image_path || 'assets/images/product-placeholder.svg';
        row.append(image);
    }

    const info = document.createElement('div');
    info.className = 'cart-product-info';

    const name = document.createElement('a');
    name.className = 'fw-semibold d-block mb-2';
    name.textContent = product?.name ?? `Produto ${item.product_id} indisponível`;
    name.href = `produto.html?id=${item.product_id}`;

    const available = product?.is_active && product.stock >= item.quantity;
    const status = document.createElement('p');
    status.className = `small ${available ? 'text-secondary' : 'text-danger'}`;
    status.textContent = available
        ? `Estoque: ${product.stock}`
        : 'Indisponível nesta quantidade. Remova ou ajuste o item.';

    info.append(name, status, createCartForm(item));

    const price = document.createElement('strong');
    price.className = mini ? 'd-block mt-2' : 'cart-item-price';
    price.textContent = product ? Store.money(Store.cents(item.subtotal)) : 'Preço indisponível';

    row.append(info, price);
    return row;
}

function createCartPlaceholder() {
    const text = document.createElement('p');
    text.className = 'p-3 small text-secondary';

    if (Store.cartError) {
        text.textContent = 'Não foi possível carregar o carrinho. Tente novamente.';
    } else if (Store.cartLoaded) {
        text.textContent = 'Seu carrinho está vazio.';
    } else {
        text.textContent = 'Carregando carrinho...';
    }

    return text;
}

function createCartRetryButton() {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-primary m-3';
    button.textContent = 'Tentar novamente';
    button.disabled = Store.busy;
    button.addEventListener('click', async () => {
        try {
            await Store.initialize();
            Store.notice('Carrinho atualizado.');
        } catch (error) {
            const message = error.message && !error.response ? error.message : Store.error(error);
            Store.notice(message, true);
        }
    });

    return button;
}

function renderCart() {
    let count = '—';

    if (Store.cartLoaded) {
        count = Store.cart.reduce((sum, item) => sum + item.quantity, 0);
    } else if (Store.cartLoading) {
        count = '…';
    }

    for (const element of document.querySelectorAll('[data-cart-count]')) {
        element.textContent = count;
    }

    for (const element of document.querySelectorAll('[data-cart-total]')) {
        element.textContent = Store.cartLoaded ? Store.money(Store.total()) : '—';
    }

    for (const target of document.querySelectorAll('[data-cart-items]')) {
        const rows = [];

        if (Store.cartLoaded) {
            for (const item of Store.cart) {
                rows.push(cartRow(item, target.dataset.cartItems === 'mini'));
            }
        }

        if (!rows.length) {
            rows.push(createCartPlaceholder());

            if (Store.cartError) {
                rows.push(createCartRetryButton());
            }
        }

        target.replaceChildren(...rows);
        target.setAttribute('aria-busy', Store.busy || Store.cartLoading ? 'true' : 'false');
    }

    for (const button of document.querySelectorAll('[data-clear-cart]')) {
        button.disabled = Store.busy || Store.cartLoading;
    }

    const checkout = document.getElementById('checkout-link');

    if (checkout) {
        checkout.href = Store.user ? 'checkout.html' : 'login.html';
        checkout.textContent = Store.user ? 'Escolher endereço e continuar' : 'Entrar para finalizar';
        checkout.hidden = !Store.cartLoaded || !Store.cart.length;
    }
}

function renderMain() {
    renderSession();
    renderCart();
}

async function addProductToCart(event) {
    const button = event.target.closest('[data-add-product]');

    if (!button || button.disabled) {
        return;
    }

    button.disabled = true;

    try {
        const input = button.id === 'product-buy'
            ? document.getElementById('product-quantity')
            : null;

        if (input && !input.reportValidity()) {
            return;
        }

        const quantity = input ? Number(input.value) : 1;
        await Store.add(Number(button.dataset.addProduct), quantity);
        Store.notice('Produto adicionado ao carrinho.');
    } catch (error) {
        const message = error.message && !error.response ? error.message : Store.error(error);
        Store.notice(message, true);
    } finally {
        button.disabled = false;
    }
}

async function clearCart() {
    const confirmed = await Store.confirm(
        'Limpar carrinho?',
        'Todos os itens deste carrinho serão removidos.',
        'Limpar',
    );

    if (!confirmed) {
        return;
    }

    try {
        await Store.clear();
        Store.notice('Carrinho limpo.');
    } catch (error) {
        Store.notice(Store.error(error), true);
    }
}

async function logout() {
    try {
        await Store.run(() => Store.request('post', '/api/logout'));
        window.location.assign('index.html');
    } catch (error) {
        Store.notice(Store.error(error), true);
    }
}

async function initializeMain() {
    // A sessão pode responder antes de o script registrar o evento.
    renderMain();

    const activePage = window.location.pathname.split('/').pop() || 'index.html';

    for (const link of document.querySelectorAll('nav a[href], .offcanvas a[href]')) {
        const target = new URL(link.href, window.location.href);
        const targetPage = target.pathname.split('/').pop();

        if (targetPage === activePage && !target.search && !link.hasAttribute('data-category')) {
            link.setAttribute('aria-current', 'page');
        }
    }

    if (Store.cartWarnings.length) {
        Store.notice(Store.cartWarnings.join(' '), true);
    }

    try {
        await Store.ready;
        renderMain();

        if (Store.cartWarnings.length) {
            Store.notice(Store.cartWarnings.join(' '), true);
        }
    } catch (error) {
        renderMain();
        const message = error.message && !error.response ? error.message : Store.error(error);
        Store.notice(message, true);
    }
}

window.addEventListener('store:change', renderMain);

document.addEventListener('click', addProductToCart);

for (const button of document.querySelectorAll('[data-clear-cart]')) {
    button.addEventListener('click', clearCart);
}

for (const button of document.querySelectorAll('[data-logout]')) {
    button.addEventListener('click', logout);
}

window.addEventListener('pageshow', event => {
    // Conferir a sessão ao voltar pelo histórico, antes de reapresentar dados privados.
    if (event.persisted) {
        window.location.reload();
    }
});

initializeMain();
