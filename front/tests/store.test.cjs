const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');
const source = readFileSync(join(__dirname, '../assets/js/store.js'), 'utf8');
const product = { id: 1, name: 'Jogo', price: '0.10', stock: 8, is_active: true, image_path: '/images/a.png' };
const user = { id: 2, name: 'Cliente', role: 'customer' };
const empty = { items: [], total: '0.00', subtotal: '0.00', shipping_cost: '0.00' };
const populated = { items: [{ id: 7, product_id: 1, quantity: 2, product, subtotal: '0.20' }], total: '0.20', subtotal: '0.20', shipping_cost: '0.00' };
const response = data => ({ status: 200, data: { data } });
const error = status => ({ response: { status, data: { message: 'Estado mudou.' } } });
function setup({ account = null, handler, base = '' } = {}) {
  const calls = [];
  const request = async (method, url, data) => {
    calls.push({ method, url, data });
    const result = await handler?.(method, url, data);
    if (result !== undefined) return result;
    if (url === '/api/me') { if (!account) throw error(401); return response(account); }
    if (url === '/sanctum/csrf-cookie') return { status: 204 };
    if (url === '/api/cart' || url === '/api/cart/merge') return response(empty);
    if (url === '/api/cart/items' || url === '/api/cart/items/7') return response(populated);
    throw new Error('Requisição inesperada: ' + url);
  };
  const axios = { get: url => request('get', url), request: ({method,url,data}) => request(method,url,data) };
  const context = { Event, window: { TICOGAMES_CONFIG: { apiBaseUrl: base }, dispatchEvent() {} }, axios,
    localStorage: { getItem() { throw new Error('Não usar armazenamento local.'); }, setItem() { throw new Error('Não usar armazenamento local.'); } } };
  runInNewContext(source + '\nglobalThis.store = Store;', context);
  return { store: context.store, calls, axios };
}

test('visitante e conta consultam carrinho sem disparar fusão no frontend', async () => {
  const guest = setup(); await guest.store.ready;
  assert.equal(guest.store.cartLoaded, true); assert.equal(guest.store.user, null);
  assert.deepEqual(guest.calls.map(call => call.url), ['/api/me', '/api/cart']);
  const account = setup({ account: user }); await account.store.ready;
  assert.deepEqual(account.calls.map(call => call.url), ['/api/me', '/api/cart']);
});

test('após autenticar consulta a fusão já concluída e apresenta seus avisos', async () => {
  const {store,calls} = setup({handler:async (_,url) => url==='/api/cart'?response({...populated,warnings:['Quantidade ajustada.']}):undefined});
  await store.ready;
  const notices=[];store.notice=message=>notices.push(message);
  await store.acceptSession(user);
  assert.equal(store.cart[0].quantity,2);
  assert.deepEqual(calls.slice(-1).map(call=>[call.method,call.url]),[['get','/api/cart']]);
  assert.deepEqual(notices,['Quantidade ajustada.']);
  assert.equal(store.error({response:{status:422,data:{message:'O carrinho está vazio.'}}}),'O carrinho está vazio.');
});
test('configuração aplica origem, cookies e CSRF também em outra origem', async () => {
  const {store,axios} = setup({base:'https://api.example.test'}); await store.ready;
  assert.equal(axios.defaults.baseURL, 'https://api.example.test');
  assert.equal(axios.defaults.withCredentials, true); assert.equal(axios.defaults.withXSRFToken, true);
});
test('escritas do visitante passam pela API após CSRF e quantidade zero é enviada', async () => {
  const {store,calls} = setup(); await store.ready; await store.add(1, 2); await store.update(7, 0);
  const writes = calls.filter(call => call.method !== 'get');
  assert.equal(JSON.stringify(writes[0].data), '{"product_id":1,"quantity":2}');
  assert.equal(JSON.stringify(writes[1].data), '{"quantity":0}');
  for (const write of writes) assert.equal(calls[calls.indexOf(write)-1].url, '/sanctum/csrf-cookie');
});
test('total e subtotal são exatamente os valores retornados pelo servidor', async () => {
  const {store} = setup(); await store.ready;
  store.setCart({...populated,total:'1.23',subtotal:'1.23'});
  assert.equal(store.total(),123n); assert.equal(store.cart[0].subtotal,'0.20');
  assert.equal(store.money(store.cents('1234567890123456.78')), 'R$ 1.234.567.890.123.456,78');
});
test('validação de ID/quantidade não envia escrita nem anuncia sucesso', async () => {
  const {store,calls} = setup(); await store.ready;
  for (const value of [0,-1,1.5,NaN,4294967296]) await assert.rejects(store.add(1,value));
  await assert.rejects(store.update(7,-1)); await assert.rejects(store.add('1',1));
  assert.equal(calls.some(call => call.method !== 'get'),false);
});
test('resposta incompleta não substitui carrinho confirmado', async () => {
  const {store} = setup(); await store.ready; store.setCart(populated);
  assert.throws(() => store.setCart({items:[]})); assert.equal(store.total(),20n);
  assert.throws(() => store.setCart({...populated,items:[{...populated.items[0],subtotal:'NaN'}]}));
});
test('falha de sessão não é convertida em visitante', async () => {
  const {store,calls} = setup({handler:async (_,url) => { if(url==='/api/me') throw error(500); }});
  await assert.rejects(store.ready); await assert.rejects(store.add(1,1));
  assert.equal(calls.some(call => call.url==='/api/cart/items'),false); assert.ok(store.sessionError);
});
test('conflito ou resposta perdida não repete a escrita nem altera o estado confirmado', async () => {
  for (const failure of [error(409), new Error('rede')]) {
    const {store,calls} = setup({handler:async (method,url) => { if(method==='post' && url==='/api/cart/items') throw failure; }});
    await store.ready; await assert.rejects(store.add(1,1));
    assert.equal(calls.filter(call => call.method==='post').length,1); assert.equal(store.cart.length,0); assert.equal(store.busy,false);
  }
});
test('falha de leitura termina carregamento e uma nova ação pode recuperar', async () => {
  let fail = true;
  const {store} = setup({handler:async (_,url) => { if(url==='/api/cart' && fail)throw error(500); }});
  await assert.rejects(store.ready); assert.equal(store.cartLoading,false); assert.ok(store.cartError);
  fail=false; await store.add(1,2); assert.equal(store.cartLoaded,true); assert.equal(store.total(),20n);
});
test('remoção confirma DELETE e consulta novamente; não faz soma no navegador', async () => {
  const {store,calls} = setup({handler:async method => method==='delete'?{status:204}:undefined});
  await store.ready; await store.remove(7);
  assert.deepEqual(calls.slice(-3).map(call=>[call.method,call.url]), [['get','/sanctum/csrf-cookie'],['delete','/api/cart/items/7'],['get','/api/cart']]);
});
test('bloqueia duas escritas simultâneas', async () => {
  let release;
  const {store,calls} = setup({handler:async (method,url) => {if(method==='post'&&url==='/api/cart/items') return new Promise(resolve=>{release=()=>resolve(response(populated));});}});
  await store.ready; const first=store.add(1,1); await new Promise(setImmediate);
  await assert.rejects(store.add(1,1)); release(); await first;
  assert.equal(calls.filter(call=>call.url==='/api/cart/items').length,1);
});
