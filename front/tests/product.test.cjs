const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');

const script = readFileSync(join(__dirname, '../assets/js/product.js'), 'utf8');
const product = {
  id: 27,
  name: '<img src=x onerror=alert(1)>',
  description: 'Descrição do jogo.\nSegunda linha com <script>texto</script>.',
  price: '3499.90',
  stock: 3,
  is_active: true,
  image_path: 'images/example.svg',
  platform: { name: 'PlayStation 5' },
  categories: [{ name: 'Jogos' }, { name: 'Coleções de teste' }],
  genres: [{ name: 'Ação' }, { name: 'RPG' }],
};

// Simula só o DOM necessário; a conferência real no navegador é separada.
function element() {
  return {
    textContent: '', attributes: {}, events: {},
    setAttribute(name, value) { this.attributes[name] = value; },
    addEventListener(name, callback, options) { this.events[name] = { callback, options }; },
  };
}

async function render(search, get = async () => ({ data: { data: product } })) {
  const ids = ['main-content', 'product-details', 'product-status', 'product-name', 'product-breadcrumb',
    'product-description', 'product-price', 'product-stock', 'product-platform', 'product-categories',
    'product-genres', 'product-image', 'product-availability', 'product-quantity', 'product-buy'];
  const nodes = Object.fromEntries(ids.map(id => [id, element()]));
  nodes['product-details'].hidden = true;
  nodes['product-quantity'].disabled = true;
  nodes['product-buy'].disabled = true;
  nodes['product-status'].textContent = 'Carregando produto...';
  nodes['main-content'].setAttribute('aria-busy', 'true');
  const document = { title: 'Produto | TicoGames', getElementById(id) { return nodes[id]; } };
  const calls = [];
  const errors = [];
  const axios = { async get(url, options) { calls.push({ url, options }); return get(url, options); } };
  const pending = runInNewContext(script, {
    document, window: { location: { search } }, URLSearchParams, axios, Intl,
    console: { error(...args) { errors.push(args); } },
  });
  if (calls.length) {
    assert.equal(nodes['product-details'].hidden, true);
    assert.equal(nodes['main-content'].attributes['aria-busy'], 'true');
    assert.equal(nodes['product-status'].textContent, 'Carregando produto...');
  }
  await pending;
  assert.equal(nodes['main-content'].attributes['aria-busy'], 'false');
  return { nodes, document, calls, errors };
}

test('consulta o ID da URL e mostra o produto real sem interpretar HTML', async () => {
  const result = await render('?id=27');
  assert.equal(result.calls.length, 1);
  assert.equal(result.calls[0].url, '/api/products/27');
  assert.equal(result.calls[0].options.headers.Accept, 'application/json');
  assert.equal(result.document.title, `${product.name} | TicoGames`);
  const expected = {
    'product-name': product.name, 'product-breadcrumb': product.name, 'product-description': product.description,
    'product-price': '3.499,90', 'product-stock': 3, 'product-platform': 'PlayStation 5',
    'product-categories': 'Jogos · Coleções de teste', 'product-genres': 'Ação · RPG',
    'product-availability': 'Em estoque', 'product-status': '',
  };
  for (const [id, value] of Object.entries(expected)) {
    assert.equal(result.nodes[id].textContent, value, id);
  }
  assert.equal(result.nodes['product-details'].hidden, false);
  assert.equal(result.nodes['product-quantity'].max, 3);
  assert.equal(result.nodes['product-quantity'].disabled, false);
  assert.equal(result.nodes['product-buy'].disabled, false);
  assert.equal(result.errors.length, 0);
  const image = result.nodes['product-image'];
  assert.equal(image.src, product.image_path);
  assert.equal(image.alt, product.name);
  assert.equal(image.events.error.options.once, true);
  image.events.error.callback();
  assert.equal(image.src, 'assets/images/product-placeholder.svg');
});

test('outro ID consulta outro produto e aceita gêneros vazios e preço zero', async () => {
  const other = { ...product, id: 28, name: 'Outro produto', description: 'Outra descrição', price: '0.00', genres: [] };
  const result = await render('?id=28', async () => ({ data: { data: other } }));
  assert.equal(result.calls[0].url, '/api/products/28');
  assert.equal(result.nodes['product-name'].textContent, other.name);
  assert.equal(result.nodes['product-description'].textContent, other.description);
  assert.equal(result.nodes['product-price'].textContent, '0,00');
  assert.equal(result.nodes['product-genres'].textContent, 'Não informado');
  assert.equal(result.document.title, 'Outro produto | TicoGames');
  assert.equal(result.nodes['product-details'].hidden, false);
});

test('IDs ausentes ou inválidos não enviam requisições', async () => {
  for (const search of ['', '?id=', '?id=0', '?id=-1', '?id=1.5', '?id=1abc', '?id=01', '?id=1e3',
    '?id=9007199254740992', '?id=%3Cscript%3E', '?id=%201', '?id=1%20', '?id=1%0A', '?id=1%0D%0A']) {
    const result = await render(search);
    assert.equal(result.calls.length, 0, search);
    assert.equal(result.nodes['product-details'].hidden, true, search);
    assert.match(result.nodes['product-status'].textContent, /Selecione um produto válido/, search);
    assert.equal(result.document.title, 'Produto | TicoGames');
    assert.equal(result.errors.length, 0);
  }
});

test('desabilita quantidade e compra para produto sem estoque ou inativo', async () => {
  for (const unavailable of [{ ...product, stock: 0 }, { ...product, is_active: false }]) {
    const result = await render('?id=27', async () => ({ data: { data: unavailable } }));
    assert.equal(result.nodes['product-details'].hidden, false);
    assert.equal(result.nodes['product-availability'].textContent, 'Indisponível');
    assert.equal(result.nodes['product-quantity'].max, unavailable.stock);
    assert.equal(result.nodes['product-quantity'].disabled, true);
    assert.equal(result.nodes['product-buy'].disabled, true);
  }
});

for (const status of [404, 500]) {
  test(`trata HTTP ${status} sem exibir conteúdo de outro produto`, async () => {
    const result = await render('?id=27', async () => { throw { response: { status } }; });
    assert.equal(result.nodes['product-details'].hidden, true);
    assert.equal(result.nodes['product-name'].textContent, '');
    assert.equal(result.document.title, 'Produto | TicoGames');
    assert.equal(result.nodes['product-status'].textContent, status === 404
      ? 'Produto não encontrado ou indisponível.'
      : 'Não foi possível carregar o produto. Atualize a página para tentar novamente.');
    assert.equal(result.errors.length, 1);
  });
}

test('trata falha de rede mantendo o conteúdo oculto e os controles desabilitados', async () => {
  const result = await render('?id=27', async () => { throw new Error('Sem conexão'); });
  assert.equal(result.nodes['product-details'].hidden, true);
  assert.equal(result.nodes['product-buy'].disabled, true);
  assert.equal(result.nodes['product-quantity'].disabled, true);
  assert.match(result.nodes['product-status'].textContent, /Não foi possível carregar o produto/);
  assert.equal(result.errors.length, 1);
});

test('rejeita resposta incompleta, JSON inválido ou produto de outro ID', async () => {
  for (const body of ['{invalido', {}, { data: null }, { data: [] },
    { data: { ...product, id: 28 } }, { data: { ...product, name: undefined } },
    { data: { ...product, description: null } }, { data: { ...product, image_path: null } },
    { data: { ...product, price: 'inválido' } }, { data: { ...product, price: '-1.00' } },
    { data: { ...product, stock: -1 } }, { data: { ...product, stock: 1.5 } },
    { data: { ...product, platform: null } }, { data: { ...product, categories: {} } },
    { data: { ...product, genres: [null] } }, { data: { ...product, is_active: 'true' } }]) {
    const result = await render('?id=27', async () => ({ data: body }));
    assert.equal(result.nodes['product-details'].hidden, true);
    assert.equal(result.nodes['product-name'].textContent, '');
    assert.equal(result.nodes['product-buy'].disabled, true);
    assert.match(result.nodes['product-status'].textContent, /Não foi possível carregar o produto/);
    assert.equal(result.errors.length, 1);
  }
});
