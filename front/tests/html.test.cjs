const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync, existsSync } = require('node:fs');
const { resolve, dirname, join } = require('node:path');
const root = resolve(__dirname, '..');
const pages = ['index', 'produto', 'login', 'admin', 'carrinho', 'checkout', 'pedidos', 'perfil'];

for (const page of pages) test(`${page}.html tem IDs, destinos e scripts consistentes`, () => {
  const file = join(root, `${page}.html`);
  const html = readFileSync(file, 'utf8');
  const tags = [...html.matchAll(/<\/?([a-z][\w-]*)(?:[^"'>]|"[^"]*"|'[^']*')*>/gi)];
  const ids = [...html.matchAll(/\bid="([^"]+)"/g)].map(match => match[1]);
  assert.equal(ids.length, new Set(ids).size, 'IDs não podem se repetir');
  let forms = 0;
  for (const [tag, name] of tags) {
    if (name === 'form') {
      forms += tag.startsWith('</') ? -1 : 1;
      assert.ok(forms >= 0 && forms <= 1, 'Formulários precisam fechar e não podem ser aninhados');
    }
    if (tag.startsWith('</')) continue;
    for (const [, attr, value] of tag.matchAll(/\b(for|aria-describedby|aria-labelledby|aria-controls)="([^"]+)"/g)) {
      for (const id of value.split(/\s+/)) assert.ok(ids.includes(id), `${attr} aponta para ID ausente: ${id}`);
    }
    for (const [, attr, value] of tag.matchAll(/\b(src|href|data-bs-target)="([^"]+)"/g)) {
      if (/^(https?:|data:|mailto:)/.test(value)) continue;
      if (value.startsWith('#')) assert.ok(ids.includes(value.slice(1)), `Âncora ausente: ${value}`);
      else if (!value.startsWith('/')) {
        const local = value.split(/[?#]/)[0];
        assert.ok(existsSync(resolve(dirname(file), local)), `${attr} aponta para arquivo ausente: ${value}`);
      }
    }
  }
  assert.equal(forms, 0);
  const scripts = [...html.matchAll(/<script\b[^>]*\bsrc="([^"]+)"/g)].map(match => match[1].split('?')[0]);
  const axios = scripts.findIndex(src => src.includes('axios@'));
  const store = scripts.indexOf('assets/js/store.js');
  const config = scripts.indexOf('assets/js/config.js');
  assert.ok(config >= 0 && config < store, 'Configuração da API deve carregar antes do Store');
  assert.ok(axios >= 0 && store > axios && scripts.indexOf('assets/js/main.js') > store);
  assert.equal(scripts.filter(src => src.includes('axios@')).length, 1);
  assert.ok(!/data-preview-|itens ilustrativos|Compra demonstrativa/.test(html));
  for (const id of ['global-status', 'mini-cart', 'preview-modal']) assert.ok(ids.includes(id));
  for (const target of ['perfil.html', 'pedidos.html']) {
    assert.ok(html.includes(`href="${target}" class="category-link" data-signed-in hidden`), `Link desktop ausente: ${target}`);
    assert.ok(html.includes(`href="${target}" class="list-group-item p-3" data-signed-in hidden`), `Link móvel ausente: ${target}`);
  }
});

test('login/cadastro não registram credenciais no console nem usam fetch', () => {
  const login = readFileSync(join(root, 'login.html'), 'utf8');
  for (const tag of login.matchAll(/<form\b[^>]*>/g)) {
    if (/id="(?:login|register)-form"/.test(tag[0])) assert.match(tag[0], /method="post"/);
  }
  for (const name of ['login', 'register', 'store', 'main']) {
    const source = readFileSync(join(root, `assets/js/${name}.js`), 'utf8');
    assert.ok(!/console\.(log|warn|error)\s*\(/.test(source));
    assert.ok(!/\bfetch\s*\(/.test(source));
  }
});
