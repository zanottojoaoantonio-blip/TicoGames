const checkoutStatus = document.getElementById('checkout-status');
const addressForm = document.getElementById('address-form');
const addressFields = document.getElementById('address-fields');
const addressStatus = document.getElementById('address-status');
const createOrder = document.getElementById('create-order');
const addressNames = ['recipient_name', 'postal_code', 'street', 'number', 'neighborhood', 'city', 'state'];
let addresses = [];
let selectedAddress = null;
let editingAddress = null;
let orderUncertain = false;

function checkoutSummary() {
  const target = document.getElementById('checkout-summary');
  target.replaceChildren(...Store.cart.map(item => {
    const text = document.createElement('p'); text.className = 'small';
    text.textContent = `${item.quantity} × ${item.product?.name ?? 'Produto indisponível'} · ${item.product ? Store.money(Store.cents(item.product.price)) : '—'} cada · subtotal ${Store.money(Store.cents(item.subtotal))}`;
    return text;
  }));
  const shipping = document.createElement('p'); shipping.textContent = 'Frete: ' + Store.money(Store.cents(Store.shippingCost)) + ' (gratuito para todos os pedidos).'; target.append(shipping);
  document.getElementById('checkout-total').textContent = Store.cartLoaded ? Store.money(Store.total()) : '—';
  createOrder.disabled = orderUncertain || Store.busy || !Store.cartLoaded || !Store.cart.length || !selectedAddress
    || Store.cart.some(item => !item.product?.is_active || item.quantity > item.product.stock);
  addressFields.disabled = Store.busy;
}

function resetAddress() {
  editingAddress = null; addressForm.reset(); Store.fieldErrors(addressForm, {});
  addressStatus.textContent = ''; document.getElementById('address-title').textContent = 'Novo endereço';
}

async function loadAddresses() {
  const data = await Store.request('get', '/api/addresses');
  if (!Array.isArray(data) || data.some(address => !Store.id(address?.id) || addressNames.some(field => typeof address[field] !== 'string'))) throw new Error('Endereços inválidos.');
  addresses = data;
  if (!addresses.some(address => address.id === selectedAddress)) selectedAddress = null;
  renderAddresses(); checkoutSummary();
}

function renderAddresses() {
  const target = document.getElementById('address-list');
  target.replaceChildren(...addresses.map(address => {
    const row = document.createElement('div'); row.className = 'border rounded p-3';
    const label = document.createElement('label'); label.className = 'd-block mb-3';
    const radio = document.createElement('input'); radio.type = 'radio'; radio.name = 'selected-address'; radio.value = address.id;
    radio.className = 'form-check-input me-2'; radio.checked = selectedAddress === address.id; radio.disabled = Store.busy;
    radio.addEventListener('change', () => { selectedAddress = address.id; checkoutSummary(); });
    label.append(radio, document.createTextNode(`${address.recipient_name} — ${address.street}, ${address.number}${address.complement ? `, ${address.complement}` : ''} · ${address.neighborhood} · ${address.city}/${address.state} · ${address.postal_code}`));
    const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'btn btn-sm btn-outline-primary me-2'; edit.textContent = 'Editar'; edit.disabled = Store.busy;
    edit.addEventListener('click', async () => {
      try {
        await Store.run(async () => {
          const current = await Store.request('get', `/api/addresses/${address.id}`);
          if (current.id !== address.id) throw new Error('Endereço inesperado.');
          resetAddress(); editingAddress = current.id;
          for (const field of [...addressNames, 'complement']) document.getElementById(`address-${field}`).value = current[field] ?? '';
          document.getElementById('address-title').textContent = `Editar endereço #${current.id}`;
          addressForm.scrollIntoView({ block: 'center' });
        });
        document.getElementById('address-recipient_name').focus({ preventScroll: true });
      } catch (error) { addressStatus.textContent = Store.error(error); }
    });
    const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-sm btn-outline-danger'; remove.textContent = 'Excluir'; remove.disabled = Store.busy;
    remove.addEventListener('click', async () => {
      if (!await Store.confirm('Excluir endereço?', `${address.street}, ${address.number} será removido dos seus endereços. Pedidos anteriores preservam sua cópia.`, 'Excluir')) return;
      try {
        await Store.run(async () => {
          await Store.request('delete', `/api/addresses/${address.id}`);
          if (editingAddress === address.id) resetAddress();
          await loadAddresses();
        });
        addressStatus.textContent = 'Endereço excluído.';
      } catch (error) { addressStatus.textContent = Store.error(error); }
    });
    row.append(label, edit, remove); return row;
  }));
  if (!addresses.length) { const text = document.createElement('p'); text.textContent = 'Cadastre um endereço para continuar.'; target.append(text); }
}

addressForm.addEventListener('submit', async event => {
  event.preventDefault();
  if (Store.busy || addressFields.disabled || !addressForm.reportValidity()) return;
  const payload = Object.fromEntries([...new FormData(addressForm)].map(([name, value]) => [name, value.trim()]));
  payload.state = payload.state.toUpperCase();
  Store.fieldErrors(addressForm, {}); addressStatus.textContent = 'Salvando endereço...';
  let focus;
  try {
    await Store.run(async () => {
      const address = await Store.request(editingAddress ? 'patch' : 'post', editingAddress ? `/api/addresses/${editingAddress}` : '/api/addresses', payload);
      if (!Store.id(address?.id)) throw new Error('Endereço inválido.');
      selectedAddress = address.id; resetAddress(); await loadAddresses();
    });
    addressStatus.textContent = 'Endereço salvo e selecionado.';
  } catch (error) { addressStatus.textContent = Store.error(error); focus = Store.fieldErrors(addressForm, error); }
  finally { addressFields.disabled = false; focus?.focus(); }
});
document.getElementById('address-cancel').addEventListener('click', () => { if (!Store.busy) resetAddress(); });

createOrder.addEventListener('click', async () => {
  if (createOrder.disabled || Store.busy || !selectedAddress) return;
  try {
    await Store.run(async () => {
      const order = Store.order(await Store.request('post', '/api/orders', { address_id: selectedAddress }));
      if (order.status !== 'pending_payment') throw new Error('Estado inesperado do pedido criado.');
      window.location.assign(`pedidos.html?id=${order.id}`);
    });
  } catch (error) {
    // Resposta perdida pode significar pedido criado; consultar antes de repetir.
    orderUncertain = !error.response || error.response.status >= 500;
    checkoutStatus.textContent = Store.error(error) + (orderUncertain ? ' Confira Meus pedidos antes de continuar.' : '');
    checkoutSummary();
  }
});
window.addEventListener('store:change', () => { checkoutSummary(); renderAddresses(); });
(async () => {
  try {
    await Store.session;
    if (!Store.user) { window.location.replace('login.html?next=checkout.html'); return; }
    await Store.ready; await loadAddresses();
    document.getElementById('checkout-content').hidden = false;
    checkoutStatus.textContent = Store.cart.length ? 'Escolha um endereço e confira os produtos.' : 'Seu carrinho está vazio. Volte ao catálogo.';
    checkoutSummary();
  } catch (error) { checkoutStatus.textContent = Store.error(error); }
})();
