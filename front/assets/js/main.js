Store.notice = (text, error = false) => {
  const status = document.getElementById('global-status');
  status.textContent = text;
  status.className = `container my-3 alert ${error ? 'alert-danger' : 'alert-success'}`;
  status.hidden = !text;
};

Store.confirm = (title, text, label = 'Confirmar') => new Promise(resolve => {
  const node = document.getElementById('preview-modal');
  const button = document.getElementById('preview-modal-confirm');
  document.getElementById('preview-modal-title').textContent = title;
  document.getElementById('preview-modal-text').textContent = text;
  button.textContent = label;
  let confirmed = false;
  button.onclick = () => { confirmed = true; };
  node.addEventListener('hidden.bs.modal', () => { button.onclick = null; resolve(confirmed); }, { once: true });
  bootstrap.Modal.getOrCreateInstance(node).show();
});

Store.fieldErrors = (form, error) => {
  const errors = error.response?.status === 422 ? error.response.data?.errors ?? {} : {};
  let first;
  for (const input of form.querySelectorAll('[name]')) {
    const messages = errors[input.name] ?? errors[input.name.replace('[]', '')];
    const message = Array.isArray(messages) ? messages.find(value => typeof value === 'string') : '';
    input.classList.toggle('is-invalid', Boolean(message));
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
    const feedback = document.getElementById(`${input.id}-error`);
    if (feedback) feedback.textContent = message || '';
    if (message && !first) first = input;
  }
  return first;
};

function renderSession() {
  const user = Store.user;
  for (const link of document.querySelectorAll('[data-account]')) {
    link.textContent = Store.sessionError ? 'Verificar acesso' : !Store.sessionLoaded ? 'Verificando conta...'
      : user ? `Olá, ${user.name}` : 'Entrar ou cadastrar';
    link.href = user ? 'pedidos.html' : 'login.html';
    link.title = user ? user.name : '';
  }
  for (const link of document.querySelectorAll('[data-orders-link]')) link.textContent = user?.role === 'admin' ? 'Pedidos' : 'Meus pedidos';
  for (const node of document.querySelectorAll('[data-signed-in]')) node.hidden = !user;
  for (const button of document.querySelectorAll('[data-logout]')) button.disabled = Store.busy;
  for (const node of document.querySelectorAll('[data-admin-only]')) node.hidden = user?.role !== 'admin';
  const merge = document.getElementById('guest-merge');
  if (merge) merge.hidden = true;
}

function cartRow(item, mini) {
  const product = item.product;
  const row = document.createElement('article');
  row.className = mini ? 'mini-cart-item d-block' : 'cart-product';
  if (!mini) {
    const image = document.createElement('img');
    image.className = 'cart-product-art'; image.width = 100; image.height = 100; image.alt = '';
    image.addEventListener('error', () => { image.src = 'assets/images/product-placeholder.svg'; }, { once: true });
    image.src = product?.image_path || 'assets/images/product-placeholder.svg'; row.append(image);
  }
  const info = document.createElement('div'); info.className = 'cart-product-info';
  const name = document.createElement('a'); name.className = 'fw-semibold d-block mb-2';
  name.textContent = product?.name ?? `Produto ${item.product_id} indisponível`;
  name.href = `produto.html?id=${item.product_id}`; info.append(name);
  const available = product?.is_active && product.stock >= item.quantity;
  const status = document.createElement('p'); status.className = `small ${available ? 'text-secondary' : 'text-danger'}`;
  status.textContent = available ? `Estoque: ${product.stock}` : 'Indisponível nesta quantidade. Remova ou ajuste o item.';
  info.append(status);
  const form = document.createElement('form'); form.className = 'd-flex flex-wrap gap-2 align-items-center';
  const input = document.createElement('input');
  input.type = 'number'; input.min = '0'; input.max = String(product?.stock || 1); input.step = '1';
  input.value = item.quantity; input.className = 'form-control quantity-input';
  input.setAttribute('aria-label', `Quantidade de ${product?.name ?? 'produto indisponível'}`);
  input.disabled = Store.busy || !product?.is_active || !product.stock;
  const update = document.createElement('button');
  update.type = 'submit'; update.className = 'btn btn-sm btn-outline-primary'; update.textContent = 'Atualizar'; update.disabled = input.disabled;
  const remove = document.createElement('button');
  remove.type = 'button'; remove.className = 'btn btn-sm btn-outline-danger'; remove.textContent = 'Remover'; remove.disabled = Store.busy;
  form.append(input, update, remove);
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    try { await Store.update(item.id, Number(input.value)); Store.notice('Quantidade atualizada.'); }
    catch (error) { Store.notice(error.message && !error.response ? error.message : Store.error(error), true); }
  });
  remove.addEventListener('click', async () => {
    try { await Store.remove(item.id); Store.notice('Item removido.'); }
    catch (error) { Store.notice(Store.error(error), true); }
  });
  info.append(form);
  const price = document.createElement('strong'); price.className = mini ? 'd-block mt-2' : 'cart-item-price';
  price.textContent = product ? Store.money(Store.cents(item.subtotal)) : 'Preço indisponível';
  row.append(info, price); return row;
}

function renderCart() {
  const count = Store.cart.reduce((sum, item) => sum + item.quantity, 0);
  for (const node of document.querySelectorAll('[data-cart-count]')) node.textContent = Store.cartLoaded ? count : Store.cartLoading ? '…' : '—';
  for (const node of document.querySelectorAll('[data-cart-total]')) node.textContent = Store.cartLoaded ? Store.money(Store.total()) : '—';
  for (const target of document.querySelectorAll('[data-cart-items]')) {
    const rows = Store.cartLoaded ? Store.cart.map(item => cartRow(item, target.dataset.cartItems === 'mini')) : [];
    if (!rows.length) {
      const text = document.createElement('p'); text.className = 'p-3 small text-secondary';
      text.textContent = Store.cartError ? 'Não foi possível carregar o carrinho. Tente novamente.'
        : Store.cartLoaded ? 'Seu carrinho está vazio.' : 'Carregando carrinho...';
      rows.push(text);
      if (Store.cartError) {
        const retry = document.createElement('button');
        retry.type = 'button'; retry.className = 'btn btn-outline-primary m-3'; retry.textContent = 'Tentar novamente';
        retry.disabled = Store.busy;
        retry.addEventListener('click', async () => {
          try { await Store.initialize(); Store.notice('Carrinho atualizado.'); }
          catch (error) { Store.notice(error.message && !error.response ? error.message : Store.error(error), true); }
        });
        rows.push(retry);
      }
    }
    target.replaceChildren(...rows); target.setAttribute('aria-busy', Store.busy || Store.cartLoading ? 'true' : 'false');
  }
  for (const button of document.querySelectorAll('[data-clear-cart]')) button.disabled = Store.busy || Store.cartLoading;
  const checkout = document.getElementById('checkout-link');
  if (checkout) {
    checkout.href = Store.user ? 'checkout.html' : 'login.html';
    checkout.textContent = Store.user ? 'Escolher endereço e continuar' : 'Entrar para finalizar';
    checkout.hidden = !Store.cartLoaded || !Store.cart.length;
  }
}

window.addEventListener('store:change', () => { renderSession(); renderCart(); });
// A sessão pode ter respondido antes deste script registrar o ouvinte.
renderSession();
renderCart();
Store.ready.then(() => { renderSession(); renderCart(); }).catch(error => {
  renderSession(); renderCart();
  Store.notice(error.message && !error.response ? error.message : Store.error(error), true);
});

document.addEventListener('click', async event => {
  const buy = event.target.closest('[data-add-product]');
  if (!buy || buy.disabled) return;
  buy.disabled = true;
  try {
    const input = buy.id === 'product-buy' ? document.getElementById('product-quantity') : null;
    if (input && !input.reportValidity()) return;
    await Store.add(Number(buy.dataset.addProduct), input ? Number(input.value) : 1);
    Store.notice('Produto adicionado ao carrinho.');
  } catch (error) { Store.notice(error.message && !error.response ? error.message : Store.error(error), true); }
  finally { buy.disabled = false; }
});
for (const button of document.querySelectorAll('[data-clear-cart]')) button.addEventListener('click', async () => {
  if (!await Store.confirm('Limpar carrinho?', 'Todos os itens deste carrinho serão removidos.', 'Limpar')) return;
  try { await Store.clear(); Store.notice('Carrinho limpo.'); }
  catch (error) { Store.notice(Store.error(error), true); }
});
for (const button of document.querySelectorAll('[data-logout]')) button.addEventListener('click', async () => {
  try { await Store.run(() => Store.request('post', '/api/logout')); window.location.assign('index.html'); }
  catch (error) { Store.notice(Store.error(error), true); }
});
// Ao voltar pelo histórico, conferir a sessão novamente antes de reapresentar dados privados.
window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });

const activePage = window.location.pathname.split('/').pop() || 'index.html';
for (const link of document.querySelectorAll('nav a[href], .offcanvas a[href]')) {
  const target = new URL(link.href, window.location.href);
  if (target.pathname.split('/').pop() === activePage && !target.search && !link.hasAttribute('data-category')) link.setAttribute('aria-current', 'page');
}
if (Store.cartWarnings.length) Store.notice(Store.cartWarnings.join(' '), true);
Store.ready.then(() => { if (Store.cartWarnings.length) Store.notice(Store.cartWarnings.join(' '), true); }).catch(() => {});
