const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext, createContext } = require('node:vm');
const storeSource = readFileSync(join(__dirname, '../assets/js/store.js'), 'utf8');
const mainSource = readFileSync(join(__dirname, '../assets/js/main.js'), 'utf8');
const flush = () => new Promise(setImmediate);

function setup(role = 'customer') {
  const listeners = {};
  const documentEvents = {};
  const writes = [];
  const product = { id: 1, name: 'Jogo no carrinho', image_path: '/images/game.svg', stock: 5, is_active: true, price: '10.00' };
  class Node {
    constructor() { this.children = []; this.events = {}; this.attributes = {}; this.dataset = {}; this.hidden = false; this.disabled = false; this.textContent = ''; }
    append(...children) { this.children.push(...children); }
    replaceChildren(...children) { this.children = children; }
    setAttribute(key, value) { this.attributes[key] = value; }
    addEventListener(name, callback) { this.events[name] = callback; }
  }
  const ids = Object.fromEntries(['global-status', 'guest-merge', 'merge-guest', 'discard-guest', 'merge-status', 'checkout-link'].map(id => [id, new Node()]));
  const account = new Node(); account.textContent = 'Entrar ou cadastrar';
  const cart = new Node(); cart.dataset.cartItems = 'full'; cart.textContent = 'Carregando carrinho...';
  const count = new Node(); const total = new Node();
  const ordersLink = new Node(); ordersLink.textContent = 'Meus pedidos';
  const selectors = { '[data-orders-link]': [ordersLink], '[data-account]': [account], '[data-cart-items]': [cart], '[data-cart-count]': [count], '[data-cart-total]': [total] };
  let failing = true;
  const context = createContext({
    Event, URL,

    localStorage: { getItem() { return null; } },
    window: { location: {pathname:'/carrinho.html',href:'http://localhost/carrinho.html'},
      dispatchEvent(event) { for (const callback of listeners[event.type] ?? []) callback(event); },
      addEventListener(name, callback) { (listeners[name] ??= []).push(callback); },
    },
    document: {
      getElementById(id) { return ids[id]; }, querySelectorAll(selector) { return selectors[selector] ?? []; },
      createElement() { return new Node(); }, addEventListener(name, callback) { documentEvents[name] = callback; },
    },
    axios: { async get(url) {
      if (url === '/api/me') return { data: { data: { id: 2, name: '<b>Cliente</b>', role } } };
      if (url === '/api/cart' && failing) throw { response: { status: 500 } };
      if (url === '/api/cart') return { data: { data: { items: [], subtotal:'0.00', shipping_cost:'0.00', total:'0.00' } } };
      if (url === '/sanctum/csrf-cookie') return { status: 204 };
      throw new Error('Requisição inesperada');
    }, async request(config) {
      writes.push(config);
      return { status: 200, data: { data: { subtotal:'10.00',shipping_cost:'0.00',total:'10.00',items: [{ id: 17, product_id: 1, quantity: 1, subtotal:'10.00', product }] } } };
    } },
  });
  runInNewContext(storeSource + '\nglobalThis.store = Store;', context);
  return { context, store: context.store, account, ordersLink, cart, count, total, ids, writes, documentEvents, recover() { failing = false; }, startMain() { runInNewContext(mainSource, context); } };
}

test('nome da conta aparece mesmo quando a consulta do carrinho falhou antes do main carregar', async () => {
  const result = setup(); await assert.rejects(result.store.ready);
  result.startMain(); await flush();
  assert.equal(result.account.textContent, 'Olá, <b>Cliente</b>');
  assert.equal(result.account.href, 'pedidos.html');
});

test('carrinho com falha mostra erro e recuperação, sem ficar em carregamento', async () => {
  const result = setup(); result.startMain(); await assert.rejects(result.store.ready); await flush();
  assert.equal(result.cart.attributes['aria-busy'], 'false');
  assert.ok(!result.cart.children.some(node => /Carregando/.test(node.textContent)));
  const retry = result.cart.children.find(node => node.textContent === 'Tentar novamente');
  assert.ok(retry, 'A consulta precisa oferecer nova tentativa');
  result.recover(); await retry.events.click(); await flush();
  assert.equal(result.cart.children[0].textContent, 'Seu carrinho está vazio.');
  assert.equal(result.count.textContent, 0);
  assert.equal(result.account.textContent, 'Olá, <b>Cliente</b>');
});

test('Comprar recupera a leitura e confirma o item sem abrir o minicarrinho', async () => {
  const result = setup(); result.startMain(); await assert.rejects(result.store.ready); await flush();
  result.recover();
  const buy = { disabled: false, dataset: { addProduct: '1' } };
  await result.documentEvents.click({ target: { closest() { return buy; } } });
  assert.equal(result.writes.length, 1);
  assert.equal(result.writes[0].url, '/api/cart/items');
  assert.equal(JSON.stringify(result.writes[0].data), '{"product_id":1,"quantity":1}');
  assert.equal(result.count.textContent, 1);
  assert.equal(result.total.textContent, 'R$ 10,00');
  assert.equal(result.cart.children[0].children[1].children[0].textContent, 'Jogo no carrinho');
  assert.equal(result.ids['global-status'].textContent, 'Produto adicionado ao carrinho.');
  assert.equal(buy.disabled, false);
});


test('somente o administrador vê Pedidos na navegação; cliente mantém Meus pedidos', async () => {
  for (const [role, label] of [['admin', 'Pedidos'], ['customer', 'Meus pedidos']]) {
    const result = setup(role); result.recover(); await result.store.ready;
    result.startMain(); await flush();
    assert.equal(result.ordersLink.textContent, label);
  }
});
