const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');

const script = readFileSync(join(__dirname, '../assets/js/login.js'), 'utf8');
const user = { id: 12, name: '<img src=x onerror=alert(1)>', email: 'customer@ticogames.test', role: 'customer' };

function element() {
  const classes = new Set();
  return {
    value: '', textContent: '', hidden: false, disabled: false, attributes: {}, events: {},
    classList: { add(name) { classes.add(name); }, remove(name) { classes.delete(name); }, contains(name) { return classes.has(name); } },
    setAttribute(name, value) { this.attributes[name] = value; },
    removeAttribute(name) { delete this.attributes[name]; },
    addEventListener(name, callback) { this.events[name] = callback; },
    focus() { this.focused = true; },
    reportValidity() { return this.valid; },
  };
}

function setup({ get = async () => ({ status: 204 }), post = async () => ({ data: { data: user } }), acceptSession = async account => { assert.equal(account.id, user.id); } } = {}) {
  const ids = ['login-form', 'login-fields', 'login-submit', 'login-status', 'login-email',
    'login-password', 'login-email-error', 'login-password-error', 'login-success', 'login-continue'];
  const nodes = Object.fromEntries(ids.map(id => [id, element()]));
  nodes['login-form'].valid = true;
  nodes['login-form'].setAttribute('aria-busy', 'false');
  nodes['login-submit'].disabled = true;
  nodes['login-submit'].textContent = 'Entrar';
  nodes['login-success'].hidden = true;
  nodes['login-email'].value = ' customer@ticogames.test ';
  nodes['login-password'].value = ' password ';
  for (const id of ['login-email', 'login-password', 'login-submit']) {
    nodes[id].focus = () => {
      assert.equal(nodes['login-fields'].disabled, false, 'O foco precisa aguardar a reativação dos campos.');
      assert.equal(nodes['login-submit'].disabled, false);
      nodes[id].focused = true;
    };
  }
  const calls = [];
  const logs = [];
  const record = (...args) => logs.push(args);
  const axios = {
    get(url, config) { calls.push({ method: 'GET', url, config }); return get(url, config); },
    post(url, body, config) { calls.push({ method: 'POST', url, body, config }); return post(url, body, config); },
  };
  runInNewContext(script, {
    window: { location: { search: '' } }, URLSearchParams,
    Store: { ready: Promise.resolve(), acceptSession },
    document: { getElementById(id) { return nodes[id]; } }, axios,
    console: { log: record, warn: record, error: record },
  });
  assert.equal(nodes['login-submit'].disabled, false);
  const submit = () => {
    const event = { prevented: false, preventDefault() { this.prevented = true; } };
    const pending = nodes['login-form'].events.submit(event);
    assert.equal(event.prevented, true);
    return pending;
  };
  return { nodes, calls, logs, submit };
}

function assertFinished(result) {
  assert.equal(result.nodes['login-form'].attributes['aria-busy'], 'false');
  assert.equal(result.nodes['login-fields'].disabled, false);
  assert.equal(result.nodes['login-submit'].disabled, false);
  assert.equal(result.nodes['login-submit'].textContent, 'Entrar');
  assert.equal(result.logs.length, 0);
}

test('prepara CSRF antes do POST e confirma a conta sem interpretar HTML', async () => {
  const result = setup();
  const pending = result.submit();
  assert.equal(result.nodes['login-fields'].disabled, true);
  assert.equal(result.nodes['login-submit'].disabled, true);
  assert.equal(result.nodes['login-form'].attributes['aria-busy'], 'true');
  assert.equal(result.nodes['login-status'].textContent, 'Verificando seus dados...');
  await Promise.resolve();
  await pending;
  assert.deepEqual(result.calls.map(call => [call.method, call.url]), [['GET', '/sanctum/csrf-cookie'], ['POST', '/api/login']]);
  for (const call of result.calls) assert.equal(call.config.headers.Accept, 'application/json');
  assert.equal(result.calls[1].body.email, 'customer@ticogames.test');
  assert.equal(result.calls[1].body.password, ' password ');
  assert.deepEqual(Object.keys(result.calls[1].body).sort(), ['email', 'password']);
  assert.equal(result.nodes['login-password'].value, '');
  assert.equal(result.nodes['login-status'].textContent, `Login realizado. Olá, ${user.name}.`);
  assert.equal(result.nodes['login-form'].hidden, true);
  assert.equal(result.nodes['login-success'].hidden, false);
  assert.equal(result.nodes['login-continue'].focused, true);
  assertFinished(result);
  await result.submit();
  assert.equal(result.calls.length, 2);
});

test('validação nativa impede enviar dados inválidos', async () => {
  const result = setup();
  result.nodes['login-form'].valid = false;
  await result.submit();
  assert.equal(result.calls.length, 0);
  assert.equal(result.nodes['login-form'].hidden, false);
  assertFinished(result);
});

test('não duplica envios enquanto a preparação CSRF está pendente', async () => {
  let resolve;
  const result = setup({ get: () => new Promise(done => { resolve = done; }) });
  const pending = result.submit();
  await result.submit();
  assert.equal(result.calls.length, 1);
  assert.equal(result.nodes['login-submit'].disabled, true);
  resolve({ status: 204 });
  await pending;
  assert.equal(result.calls.length, 2);
  assertFinished(result);
});

test('falha no CSRF não envia a senha e permite tentar novamente', async () => {
  const result = setup({ get: async () => { throw new Error('Sem conexão'); } });
  await result.submit();
  assert.equal(result.calls.length, 1);
  assert.equal(result.calls[0].method, 'GET');
  assert.match(result.nodes['login-status'].textContent, /Não foi possível entrar agora/);
  assert.equal(result.nodes['login-form'].hidden, false);
  assert.equal(result.nodes['login-success'].hidden, true);
  assertFinished(result);
});

test('422 mostra os erros nos campos, preserva o preenchimento e limpa os erros na nova tentativa', async () => {
  let attempts = 0;
  const result = setup({ post: async () => {
    if (attempts++ === 0) throw {
      config: { data: { password: 'segredo que não deve ir para o console' } },
      response: { status: 422, data: { errors: { email: ['E-mail ou senha inválidos.'], password: ['<b>Campo inválido</b>'] } } },
    };
    return { data: { data: user } };
  } });
  await result.submit();
  assert.equal(result.nodes['login-form'].hidden, false);
  assert.equal(result.nodes['login-email-error'].textContent, 'E-mail ou senha inválidos.');
  assert.equal(result.nodes['login-password-error'].textContent, '<b>Campo inválido</b>');
  assert.equal(result.nodes['login-email'].attributes['aria-invalid'], 'true');
  assert.equal(result.nodes['login-password'].classList.contains('is-invalid'), true);
  assert.equal(result.nodes['login-email'].focused, true);
  assert.equal(result.nodes['login-password'].value, ' password ');
  assertFinished(result);
  const pending = result.submit();
  assert.equal(result.nodes['login-email-error'].textContent, '');
  assert.equal(result.nodes['login-password-error'].textContent, '');
  assert.equal(result.nodes['login-email'].attributes['aria-invalid'], undefined);
  assert.equal(result.nodes['login-password'].classList.contains('is-invalid'), false);
  await pending;
  assert.equal(result.calls.filter(call => call.method === 'GET').length, 2);
  assert.equal(result.nodes['login-form'].hidden, true);
  assertFinished(result);
});

for (const [status, message] of [[419, /sessão de acesso expirou/], [429, /Muitas tentativas/], [500, /Não foi possível entrar agora/]]) {
  test(`trata HTTP ${status} sem reenvio automático nem registro de credenciais`, async () => {
    const result = setup({ post: async () => { throw { response: { status, data: { message: 'Detalhes internos' } }, config: { data: { password: 'segredo' } } }; } });
    await result.submit();
    assert.equal(result.calls.length, 2);
    assert.equal(result.nodes['login-form'].hidden, false);
    assert.equal(result.nodes['login-success'].hidden, true);
    assert.match(result.nodes['login-status'].textContent, message);
    assertFinished(result);
  });
}

test('trata falha de rede no POST sem mostrar sucesso', async () => {
  const result = setup({ post: async () => { throw new Error('Sem conexão'); } });
  await result.submit();
  assert.equal(result.nodes['login-form'].hidden, false);
  assert.equal(result.nodes['login-success'].hidden, true);
  assert.match(result.nodes['login-status'].textContent, /Não foi possível entrar agora/);
  assertFinished(result);
});

test('resposta inesperada não é apresentada como login confirmado', async () => {
  for (const body of ['JSON inválido', {}, { data: null }, { data: { name: 'Sem ID' } }, { data: { id: 12, name: null } }]) {
    const result = setup({ post: async () => ({ data: body }) });
    await result.submit();
    assert.equal(result.nodes['login-form'].hidden, false);
    assert.equal(result.nodes['login-success'].hidden, true);
    assert.match(result.nodes['login-status'].textContent, /Não foi possível entrar agora/);
    assertFinished(result);
  }
});

test('falha na validação da sessão mantém o formulário disponível sem anunciar sucesso', async () => {
  const result = setup({ acceptSession: async () => { throw new Error('Conta inválida'); } });
  await result.submit();
  assert.equal(result.nodes['login-form'].hidden, false);
  assert.equal(result.nodes['login-success'].hidden, true);
  assert.match(result.nodes['login-status'].textContent, /Não foi possível/);
  assertFinished(result);
});
