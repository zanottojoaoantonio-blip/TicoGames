const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');

const customer = { id: 2, name: 'Cliente', email: 'customer@ticogames.test', role: 'customer' };
const product = { id: 1, name: '<b>Jogo</b>', description: 'Real', image_path: '/images/game.svg', platform_id: 1, platform: {id:1,name:'PS5'}, categories: [{id:1,name:'Jogos'}], genres: [], price: '10.50', stock: 5, is_active: true };
const address = { id: 7, recipient_name: 'Cliente', postal_code: '01001-000', street: 'Rua de Teste', number: '123', complement: null, neighborhood: 'Centro', city: 'São Paulo', state: 'SP' };
const order = { id: 9, status: 'pending_payment', total: '21.00', items: [{id:1,product_id:1,product_name:'Jogo',quantity:2,unit_price:'10.50'}], ...Object.fromEntries(Object.entries(address).filter(([key])=>key!=='id').map(([key,value])=>['shipping_'+key,value])) };
const flush = () => new Promise(setImmediate);

function page(script, { user = customer, confirm = true, search = '', handler } = {}) {
  const htmlName = { admin:'admin', checkout:'checkout', orders:'pedidos', register:'login', profile:'perfil' }[script];
  const html = readFileSync(join(__dirname, `../${htmlName}.html`), 'utf8');
  const nodes = new Map(), calls = [], listeners = {}, navigations = [], focusErrors = [];
  const all = () => [...new Set([...nodes.values(), ...[...nodes.values()].flatMap(node => node.descendants())])];
  class Node {
    constructor(tag='div') { this.tagName=tag; this.children=[]; this.events={}; this.value=''; this.hidden=false; this.disabled=false; this.checked=false;this.files=[]; this.textContent=''; this.dataset={}; this.attrs={}; this.name=''; this.valid=true; this.id=''; this.classes=new Set(); this.classList={toggle:(key,on)=>on?this.classes.add(key):this.classes.delete(key),add:key=>this.classes.add(key),remove:key=>this.classes.delete(key)}; }
    descendants() { return this.children.filter(child=>child instanceof Node).flatMap(child=>[child,...child.descendants()]); }
    append(...children) { this.children.push(...children); }
    replaceChildren(...children) { this.children=[...children]; }
    setAttribute(name,value) { this.attrs[name]=value; }
    addEventListener(name,handler) { (this.events[name]??=[]).push(handler); }
    querySelectorAll(selector) {
      const source = this.tagName==='form' ? all().filter(node=>node.id.startsWith(this.id.split('-')[0]+'-')) : this.descendants();
      if (selector==='[name]') return source.filter(node=>node.name);
      const name=selector.match(/^\[name="(.*)"\]$/)?.[1];
      return source.filter(node=>node.name===name);
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0]; }
    reportValidity() { return this.valid; }
    scrollIntoView() {}
    focus() { if(this.disabled) focusErrors.push(this.id); this.focused=true; }
    reset() { for(const node of this.querySelectorAll('[name]')) { if(['checkbox','radio'].includes(node.type)) node.checked=node.defaultChecked??false; else if(node.tagName!=='select') node.value=''; } }
    async fire(name='click') { const event={currentTarget:this,preventDefault(){}}; for(const handler of this.events[name]??[]) await handler(event); }
  }
  for (const match of html.matchAll(/<([a-z][\w-]*)([^>]*)>/g)) {
    const id=match[2].match(/\bid="([^"]*)"/)?.[1]; if(!id)continue;
    const node=new Node(match[1]); node.id=id; node.name=match[2].match(/\bname="([^"]*)"/)?.[1]??''; node.type=match[2].match(/\btype="([^"]*)"/)?.[1]??'';
    node.hidden=/\shidden(?:\s|$)/.test(match[2]); node.disabled=/\sdisabled(?:\s|$)/.test(match[2]); node.checked=/\schecked(?:\s|$)/.test(match[2]); node.defaultChecked=node.checked; nodes.set(id,node);
  }
  const heading = new Node('h1'); heading.textContent = 'Meus pedidos';
  const document={querySelector:()=>heading,getElementById:id=>nodes.get(id),createElement:tag=>new Node(tag),createTextNode:text=>text};
  const emit=()=>{ for(const listener of listeners['store:change']??[])listener(); };
  const store={ user, session:Promise.resolve(), ready:Promise.resolve(), busy:false, cartLoaded:true, shippingCost:'0.00', cart:[{id:1,product_id:1,quantity:2,subtotal:'21.00',product}],
    id:value=>Number.isSafeInteger(value)&&value>0, product:value=>value, account:value=>value, order:value=>value,
    cents:value=>BigInt(value.replace('.','')), money:value=>`R$ ${value/100n},${String(value%100n).padStart(2,'0')}`, total:()=>2100n,
    error:error=>error.response?.status===422?'Dados inválidos.':'Falha na operação.',
    fieldErrors(form,error){ calls.push(['fields',form.id,error.response?.status]); return error.response?.status===422?form.querySelectorAll('[name]')[0]:undefined; },
    async confirm(...args){calls.push(['confirm',...args]);return confirm;},
    notice(...args){calls.push(['notice',...args]);},
    async acceptSession(account){calls.push(['accept',account.id]);},
    async run(action){if(store.busy)throw new Error('busy');store.busy=true;emit();try{return await action();}finally{store.busy=false;emit();}},
    async request(method,url,payload){
      calls.push([method,url,payload]); const custom=await handler?.(method,url,payload); if(custom!==undefined)return custom;
      if(method==='get') {
        if(url.startsWith('/api/products?page='))return {data:[structuredClone(product)],meta:{current_page:1,last_page:1}};
        if(url==='/api/products/1')return structuredClone(product);
        if(['/api/platforms','/api/categories','/api/genres'].includes(url))return [{id:1,name:url.split('/').at(-1)}];
        if(url==='/api/addresses')return [structuredClone(address)];
        if(url==='/api/addresses/7')return structuredClone(address);
        if(url==='/api/orders')return [structuredClone(order)];
        if(url==='/api/orders/9')return structuredClone(order);
      }
      throw new Error(`Unexpected: ${method} ${url}`);
    },
  };
  class FormData {
    constructor(form){this.entries=form.querySelectorAll('[name]').filter(node=>!node.disabled&&(!['checkbox','radio'].includes(node.type)||node.checked)).map(node=>[node.name,node.value]);}
    [Symbol.iterator](){return this.entries[Symbol.iterator]();}
    get(name){return this.entries.find(entry=>entry[0]===name)?.[1]??null;}
    getAll(name){return this.entries.filter(entry=>entry[0]===name).map(entry=>entry[1]);}
  }
  const window={location:{search,href:`http://localhost/${htmlName}.html${search}`,assign:url=>navigations.push(url),replace:url=>navigations.push(url)},history:{replaceState(){}},addEventListener:(name,handler)=>(listeners[name]??=[]).push(handler)};
  const context={Store:store,document,window,FormData,URL,URLSearchParams,Option:class extends Node{constructor(text,value){super('option');this.textContent=text;this.value=String(value);}},bootstrap:{Tab:{getOrCreateInstance(){return{show(){}}}}}};
  runInNewContext(readFileSync(join(__dirname,`../assets/js/${script}.js`),'utf8'),context);
  return {nodes,store,heading,document,calls,navigations,all,focusErrors,buttons:(root,text)=>nodes.get(root).descendants().filter(node=>node.tagName==='button'&&node.textContent===text)};
}

test('admin bloqueia visitante/cliente antes de consultar dados administrativos', async()=>{
  for(const user of [null,customer]){
    const p=page('admin',{user});await flush();
    assert.equal(p.nodes.get('admin-content').hidden,true);if(user)assert.equal(p.nodes.get('admin-access').hidden,false);else assert.deepEqual(p.navigations,['login.html?next=admin.html']);
    assert.equal(p.calls.filter(call=>call[0]==='get').length,0);
  }
});

test('admin carrega opções reais e GET de edição preenche dados e associações',async()=>{
  const p=page('admin',{user:{...customer,role:'admin'}});await flush();
  assert.equal(p.nodes.get('admin-content').hidden,false);
  await p.buttons('admin-products','Editar')[0].fire();
  assert.equal(p.nodes.get('admin-name').value,product.name);
  assert.equal(p.nodes.get('admin-price').value,'10.50');
  assert.equal(p.nodes.get('admin-form').querySelectorAll('[name="category_id"]')[0].checked,true);
  assert.equal(p.nodes.get('admin-form-title').textContent,'Editar produto #1');
});

test('admin bloqueia o formulário enquanto carrega a edição e só foca após reativar',async()=>{
  let resolve;
  const p=page('admin',{user:{...customer,role:'admin'},handler:async(method,url)=>{
    if(url==='/api/products/1') return new Promise(done=>{resolve=done;});
  }});await flush();
  const editing=p.buttons('admin-products','Editar')[0].fire();
  assert.equal(p.nodes.get('admin-fields').disabled,true);
  await p.nodes.get('admin-form').fire('submit');
  assert.equal(p.calls.some(call=>call[0]==='post'||call[0]==='patch'),false);
  resolve(structuredClone(product));await editing;
  assert.equal(p.nodes.get('admin-fields').disabled,false);assert.equal(p.nodes.get('admin-name').focused,true);
});

function fillProduct(p) {
  for(const [name,value] of Object.entries({name:'Novo',description:'Descrição',image_path:'/images/a.svg',platform_id:'1',price:'12.34',stock:'3'}))p.nodes.get('admin-'+name).value=value;
  p.nodes.get('admin-form').querySelectorAll('[name="category_id"]')[0].checked=true;
}

test('admin POST e PATCH enviam preço decimal e IDs, sem limpar na falha 422',async()=>{
  let fail=false;
  const p=page('admin',{user:{...customer,role:'admin'},handler:async(method,url,data)=>{
    if(['post','patch'].includes(method)){if(fail)throw{response:{status:422,data:{errors:{name:['Inválido']}}}};return {...product,...data};}
  }});await flush();fillProduct(p);await p.nodes.get('admin-form').fire('submit');
  const write=p.calls.find(call=>call[0]==='post');
  assert.equal(write[1],'/api/products');assert.equal(write[2].price,'12.34');assert.equal(write[2].platform_id,1);assert.equal(write[2].category_id,1);assert.equal(write[2].is_active,true);
  await p.buttons('admin-products','Editar')[0].fire();p.nodes.get('admin-name').value='Mantido';fail=true;
  await p.nodes.get('admin-form').fire('submit');
  assert.equal(p.calls.find(call=>call[0]==='patch')[1],'/api/products/1');
  assert.equal(p.nodes.get('admin-name').value,'Mantido');assert.equal(p.nodes.get('admin-fields').disabled,false);
  assert.equal(p.nodes.get('admin-form-status').textContent,'Dados inválidos.');
});

test('desativação cancelada não envia DELETE; confirmada atualiza o status após 204',async()=>{
  for(const confirm of [false,true]){
    const p=page('admin',{user:{...customer,role:'admin'},confirm,handler:async(method)=>method==='delete'?null:undefined});await flush();
    await p.buttons('admin-products','Desativar')[0].fire();
    assert.equal(p.calls.filter(call=>call[0]==='delete').length,confirm?1:0);
    assert.equal(p.buttons('admin-products','Desativar')[0].disabled,confirm);
  }
});

test('checkout exige sessão e escolha explícita de endereço antes de criar pedido',async()=>{
  const guest=page('checkout',{user:null});await flush();assert.equal(guest.nodes.get('checkout-content').hidden,true);assert.deepEqual(guest.navigations,['login.html?next=checkout.html']);
  const p=page('checkout');await flush();assert.equal(p.nodes.get('create-order').disabled,true);
  await p.nodes.get('create-order').fire();assert.equal(p.calls.some(call=>call[0]==='post'),false);
});

test('editar endereço carrega todos os campos e envia PATCH para o endereço correto',async()=>{
  const p=page('checkout',{handler:async(method,url,data)=>method==='patch'?{...address,...data}:undefined});await flush();
  await p.buttons('address-list','Editar')[0].fire();
  assert.equal(p.nodes.get('address-recipient_name').value,'Cliente');assert.equal(p.nodes.get('address-street').value,'Rua de Teste');
  assert.equal(p.nodes.get('address-title').textContent,'Editar endereço #7');
  p.nodes.get('address-number').value='321';await p.nodes.get('address-form').fire('submit');
  const write=p.calls.find(call=>call[0]==='patch');assert.equal(write[1],'/api/addresses/7');assert.equal(write[2].number,'321');assert.equal('user_id' in write[2],false);
  assert.equal(p.nodes.get('address-status').textContent,'Endereço salvo e selecionado.');assert.deepEqual(p.focusErrors,[]);
});

test('endereço excluído só após confirmação; escolha é removida depois do backend',async()=>{
  const p=page('checkout',{handler:async(method)=>method==='delete'?null:undefined});await flush();
  await p.buttons('address-list','Excluir')[0].fire();
  assert.ok(p.calls.some(call=>call[0]==='delete'&&call[1]==='/api/addresses/7'));
  assert.equal(p.nodes.get('create-order').disabled,true);
});

test('criação do pedido envia somente address_id e abre a confirmação do backend',async()=>{
  const p=page('checkout',{handler:async(method,url)=>method==='post'&&url==='/api/orders'?order:undefined});await flush();
  const radio=p.nodes.get('address-list').descendants().find(node=>node.type==='radio');await radio.fire('change');
  assert.equal(p.nodes.get('create-order').disabled,false);await p.nodes.get('create-order').fire();
  const write=p.calls.find(call=>call[0]==='post');assert.equal(JSON.stringify(write[2]),'{"address_id":7}');assert.deepEqual(p.navigations,['pedidos.html?id=9']);
});

test('resposta perdida ao criar pedido bloqueia repetição e orienta consultar pedidos',async()=>{
  const p=page('checkout',{handler:async(method)=>{if(method==='post')throw new Error('network');}});await flush();
  await p.nodes.get('address-list').descendants().find(node=>node.type==='radio').fire('change');await p.nodes.get('create-order').fire();await p.nodes.get('create-order').fire();
  assert.equal(p.calls.filter(call=>call[0]==='post').length,1);assert.equal(p.nodes.get('create-order').disabled,true);assert.match(p.nodes.get('checkout-status').textContent,/Meus pedidos/);
});

test('pedidos rejeitam ID inválido sem consultar a rota e mantêm a lista própria',async()=>{
  const p=page('orders',{search:'?id=1.5'});await flush();assert.equal(p.calls.filter(call=>call[1]?.startsWith('/api/orders/')).length,0);assert.match(p.nodes.get('orders-status').textContent,/válido/);
});

test('fechar confirmação não paga/cancela; pedido concluído não oferece nova decisão',async()=>{
  const cancelled=page('orders',{search:'?id=9',confirm:false});await flush();await cancelled.buttons('order-detail','Confirmar pagamento simulado')[0].fire();assert.equal(cancelled.calls.some(call=>call[0]==='post'),false);
  for(const action of ['pay','cancel']){
    const p=page('orders',{search:'?id=9',handler:async(method,url)=>method==='post'?{...order,status:action==='pay'?'paid':'cancelled'}:undefined});await flush();
    await p.buttons('order-detail',action==='pay'?'Confirmar pagamento simulado':'Cancelar pedido')[0].fire();
    assert.equal(p.calls.filter(call=>call[0]==='post').length,1);assert.equal(p.calls.find(call=>call[0]==='post')[1],`/api/orders/9/${action}`);
    assert.equal(p.buttons('order-detail','Confirmar pagamento simulado').length,0);assert.equal(p.buttons('order-detail','Cancelar pedido').length,0);
  }
});

test('cadastro confirma senha no cliente e envia somente os quatro campos públicos',async()=>{
  const p=page('register',{handler:async(method,url,data)=>method==='post'?{...customer,name:data.name}:undefined});
  for(const [name,value] of Object.entries({name:' Nome ',email:' cliente@example.test ',password:'12345678',password_confirmation:'12345679'}))p.nodes.get('register-'+name).value=value;
  await p.nodes.get('register-form').fire('submit');assert.equal(p.calls.some(call=>call[0]==='post'),false);
  p.nodes.get('register-password_confirmation').value='12345678';await p.nodes.get('register-form').fire('submit');
  const write=p.calls.find(call=>call[0]==='post');assert.deepEqual(Object.keys(write[2]).sort(),['email','name','password','password_confirmation']);assert.equal(write[2].name,'Nome');assert.equal(write[2].password,'12345678');
  assert.equal(p.nodes.get('register-password').value,'');assert.equal(p.nodes.get('register-form').hidden,true);assert.ok(p.calls.some(call=>call[0]==='accept'));
});

test('cadastro com 422 preserva preenchimento e reativa os controles',async()=>{
  const p=page('register',{handler:async(method)=>{if(method==='post')throw{response:{status:422,data:{errors:{email:['Repetido']}}}};}});
  for(const [name,value] of Object.entries({name:'Cliente',email:'cliente@example.test',password:'12345678',password_confirmation:'12345678'}))p.nodes.get('register-'+name).value=value;
  await p.nodes.get('register-form').fire('submit');
  assert.equal(p.nodes.get('register-name').value,'Cliente');assert.equal(p.nodes.get('register-fields').disabled,false);
  assert.equal(p.nodes.get('register-form').hidden,false);assert.equal(p.calls.some(call=>call[0]==='accept'),false);
});

test('cadastro não inicia outra autenticação enquanto o login está em andamento',async()=>{
  const p=page('register');p.store.authenticating=true;
  await p.nodes.get('register-form').fire('submit');assert.equal(p.calls.some(call=>call[0]==='post'),false);
});

test('pagamento rejeitado mantém o pedido pendente e não anuncia sucesso',async()=>{
  const p=page('orders',{search:'?id=9',handler:async(method)=>{if(method==='post')throw{response:{status:422,data:{errors:{items:['Sem estoque']}}}};}});await flush();
  await p.buttons('order-detail','Confirmar pagamento simulado')[0].fire();
  assert.equal(p.buttons('order-detail','Confirmar pagamento simulado').length,1);assert.match(p.nodes.get('orders-status').textContent,/Dados inválidos/);
  assert.equal(p.calls.filter(call=>call[0]==='post').length,1);
});

test('404 de pedido alheio/inexistente não mostra detalhes de outro pedido',async()=>{
  const p=page('orders',{search:'?id=9',handler:async(_,url)=>{if(url==='/api/orders/9')throw{response:{status:404}};}});await flush();
  assert.equal(p.nodes.get('order-detail').children[0].textContent,'Falha na operação.');
  assert.equal(p.buttons('order-detail','Confirmar pagamento simulado').length,0);
});


test('perfil redireciona visitante e salva somente os próprios dados sem senha vazia',async()=>{
  const guest=page('profile',{user:null});await flush();assert.deepEqual(guest.navigations,['login.html?next=perfil.html']);
  const p=page('profile',{handler:async(method,url,data)=>method==='patch'?{...customer,...data}:undefined});await flush();
  p.nodes.get('profile-name').value='Novo nome';p.nodes.get('profile-email').value='new@example.test';await p.nodes.get('profile-form').fire('submit');
  const write=p.calls.find(call=>call[0]==='patch');assert.equal(write[1],'/api/user');assert.equal(write[2].name,'Novo nome');assert.equal('password' in write[2],false);assert.equal(p.nodes.get('profile-status').textContent,'Dados atualizados.');
});

test('perfil preserva formulário em erro e não envia senhas diferentes',async()=>{
  const p=page('profile',{handler:async(method)=>{if(method==='patch')throw{response:{status:422,data:{errors:{email:['Repetido']}}}};}});await flush();
  p.nodes.get('profile-password').value='password123';p.nodes.get('profile-password_confirmation').value='different';await p.nodes.get('profile-form').fire('submit');assert.equal(p.calls.some(call=>call[0]==='patch'),false);
  p.nodes.get('profile-password_confirmation').value='password123';await p.nodes.get('profile-form').fire('submit');assert.equal(p.nodes.get('profile-fields').disabled,false);assert.equal(p.nodes.get('profile-password').value,'password123');assert.equal(p.nodes.get('profile-status').textContent,'Dados inválidos.');
});

test('admin envia transição de envio por PATCH e pede confirmação',async()=>{
  const p=page('orders',{user:{...customer,role:'admin'},search:'?id=9',handler:async(method,url,data)=>{
    if(url==='/api/orders/9')return {...order,status:'paid'};
    if(method==='patch')return {...order,status:data.status};
  }});await flush();assert.equal(p.buttons('order-detail','Cancelar pedido').length,0);await p.buttons('order-detail','Marcar como enviado')[0].fire();
  const write=p.calls.find(call=>call[0]==='patch');assert.equal(write[1],'/api/orders/9/status');assert.equal(write[2].status,'shipped');assert.equal(p.buttons('order-detail','Marcar como entregue').length,1);
});

test('resumo do checkout mostra preço unitário e regra de frete',async()=>{
  const p=page('checkout');await flush();const text=p.nodes.get('checkout-summary').children.map(node=>node.textContent).join(' ');assert.match(text,/R\$ 10,50 cada/);assert.match(text,/subtotal R\$ 21,00/);assert.match(text,/gratuito para todos os pedidos/);
});

test('categoria nova usa POST e mantém opções administrativas atualizadas',async()=>{
  const p=page('admin',{user:{...customer,role:'admin'},handler:async(method,url,data)=>method==='post'&&url==='/api/categories'?{id:2,name:data.name}:undefined});await flush();
  p.nodes.get('admin-category-name').value='Categoria QA';await p.nodes.get('admin-category-form').fire('submit');const write=p.calls.find(call=>call[0]==='post');assert.equal(write[1],'/api/categories');assert.equal(write[2].name,'Categoria QA');
  assert.equal(p.nodes.get('admin-status').textContent,'Categoria criada.');
});


test('título de pedidos muda somente para a conta administrativa', async () => {
  const admin = page('orders', {user:{...customer,role:'admin'}}); await flush();
  assert.equal(admin.heading.textContent, 'Pedidos'); assert.equal(admin.document.title, 'Pedidos | TicoGames');
  const client = page('orders'); await flush(); assert.equal(client.heading.textContent, 'Meus pedidos');
});
