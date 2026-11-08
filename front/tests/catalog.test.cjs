const {test}=require('node:test');
const assert=require('node:assert/strict');
const {readFileSync}=require('node:fs');
const {join}=require('node:path');
const {runInNewContext}=require('node:vm');
const source=readFileSync(join(__dirname,'../assets/js/catalog.js'),'utf8');
const product={id:1,name:'<b>Jogo</b>',price:'10.00',image_path:'/a.png',stock:1,is_active:true,platform:{id:1,name:'PS5'},category:{id:3,name:'Nova categoria'}};
const flush=()=>new Promise(setImmediate);
function setup({search='',handler}={}) {
  const calls=[],nodes=new Map();
  class Node {
    constructor(){this.children=[];this.events={};this.attrs={};this.value='';this.textContent='';this.disabled=false;this.dataset={};}
    append(...items){this.children.push(...items);}
    replaceChildren(...items){this.children=items;}
    setAttribute(k,v){this.attrs[k]=v;}
    addEventListener(k,fn){this.events[k]=fn;}
    get selectedOptions(){return this.children.filter(node=>String(node.value)===String(this.value));}
  }
  const node=id=>{if(!nodes.has(id))nodes.set(id,new Node());return nodes.get(id);};
  node('product-card-template').content={cloneNode(){const children=new Map();const get=k=>{if(!children.has(k))children.set(k,new Node());return children.get(k);};return {querySelector:get,querySelectorAll:k=>[get(k)],children};}};
  for(const id of ['filter-category','filter-platform','filter-genre'])node(id).append(Object.assign(new Node(),{value:'',textContent:'Todos'}));
  let url='';
  const context={URL,URLSearchParams,setTimeout,clearTimeout,window:{location:{search,href:'http://localhost/index.html'+search},history:{replaceState(_,__,value){url=String(value);}}},
    document:{getElementById:node,querySelectorAll:()=>[]},Option:class extends Node{constructor(name,id){super();this.textContent=name;this.value=String(id);}},
    Store:{product:value=>value,cents:value=>BigInt(value.replace('.','')),money:()=> 'R$ 10,00',error:()=> 'Falha no catálogo.',async request(method,path){calls.push(path);const custom=await handler?.(path);if(custom!==undefined)return custom;if(path==='/api/categories')return [{id:3,name:'Nova categoria'}];if(['/api/platforms','/api/genres'].includes(path))return [{id:1,name:'Opção'}];return {data:[product],meta:{current_page:1,last_page:2,total:13}};}}};
  runInNewContext(source,context);return {nodes,calls,url:()=>url};
}
test('carrega categoria nova da API, monta cards como texto e mostra paginação',async()=>{
  const p=setup();await flush();assert.equal(p.nodes.get('filter-category').children[1].textContent,'Nova categoria');
  const card=p.nodes.get('product-grid').children[0];assert.equal(card.querySelector('.product-title a').textContent,product.name);
  assert.equal(p.nodes.get('catalog-page').textContent,'Página 1 de 2');assert.equal(p.nodes.get('catalog-prev').disabled,true);assert.equal(p.nodes.get('catalog-next').disabled,false);
});
test('filtros e ordenação são enviados à API e preservados na URL',async()=>{
  const p=setup({search:'?category_id=3&sort=price_desc&q=Jogo'});await flush();
  assert.match(p.calls.at(-1),/category_id=3/);assert.match(p.calls.at(-1),/sort=price_desc/);assert.match(p.url(),/q=Jogo/);
  p.nodes.get('catalog-sort').value='newest';p.nodes.get('catalog-sort').events.change();await flush();assert.match(p.calls.at(-1),/sort=newest/);
});
test('próxima página consulta a API em vez de filtrar somente os cards carregados',async()=>{
  const p=setup();await flush();p.nodes.get('catalog-next').events.click();await flush();assert.match(p.calls.at(-1),/page=2/);
});
test('resposta inválida termina carregamento e impede navegar com estado antigo',async()=>{
  const p=setup({handler:async path=>path.startsWith('/api/products?')?{data:{},meta:{}}:undefined});await flush();
  assert.equal(p.nodes.get('catalog-status').textContent,'Falha no catálogo.');assert.equal(p.nodes.get('product-grid').attrs['aria-busy'],'false');assert.equal(p.nodes.get('product-grid').children.length,0);
});
test('limpar filtros reinicia página e seleções',async()=>{
  const p=setup({search:'?category_id=3&q=Jogo&page=2'});await flush();p.nodes.get('clear-filters').events.click();await flush();
  assert.match(p.calls.at(-1),/page=1/);assert.doesNotMatch(p.calls.at(-1),/category_id=|q=/);
});
