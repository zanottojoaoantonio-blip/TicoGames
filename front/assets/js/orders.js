const ordersStatus = document.getElementById('orders-status');
const orderDetail = document.getElementById('order-detail');
const orderLabels = { pending_payment: 'Aguardando pagamento simulado', paid: 'Pago (simulado)', shipped: 'Enviado', delivered: 'Entregue', cancelled: 'Cancelado' };
let currentOrder = null;
let selectingOrder = false;

function orderText(tag, text, className = '') {
  const element = document.createElement(tag); element.textContent = text; element.className = className; return element;
}

function renderOrder() {
  if (!currentOrder) return;
  const order = currentOrder;
  const items = document.createElement('ul'); items.className = 'list-group list-group-flush my-4';
  for (const item of order.items) items.append(orderText('li', `${item.quantity} × ${item.product_name} · ${Store.money(Store.cents(item.unit_price))} cada · ${Store.money(Store.cents(item.unit_price) * BigInt(item.quantity))}`, 'list-group-item px-0'));
  orderDetail.replaceChildren(orderText('h2', `Pedido #${order.id}`, 'h4'), orderText('p', orderLabels[order.status], 'fw-semibold'), items,
    orderText('p', `Total confirmado pelo backend: ${Store.money(Store.cents(order.total))}`, 'fw-bold'),
    orderText('h3', 'Endereço deste pedido', 'h6 mt-4'), orderText('p', `${order.shipping_recipient_name} · ${order.shipping_street}, ${order.shipping_number}${order.shipping_complement ? `, ${order.shipping_complement}` : ''} · ${order.shipping_neighborhood} · ${order.shipping_city}/${order.shipping_state} · ${order.shipping_postal_code}`));
  orderDetail.append(orderText('p', `Criado em ${new Date(order.created_at).toLocaleString('pt-BR')}${order.paid_at ? ' · Pago em ' + new Date(order.paid_at).toLocaleString('pt-BR') : ''}`));
  const actions = order.status === 'pending_payment' ? ['pay', 'cancel'] : Store.user?.role === 'admin' && order.status === 'paid' ? ['shipped'] : Store.user?.role === 'admin' && order.status === 'shipped' ? ['delivered'] : [];
  if (actions.length) {
    orderDetail.append(orderText('p', 'Pagamento somente simulado. O estoque foi reservado na criação do pedido; o cancelamento devolve as unidades.', 'small text-secondary'));
    for (const action of actions) {
      const button = orderText('button', ({ pay: 'Confirmar pagamento simulado', cancel: 'Cancelar pedido', shipped: 'Marcar como enviado', delivered: 'Marcar como entregue' })[action], `btn ${action === 'pay' ? 'btn-accent' : 'btn-outline-danger'} me-2 mb-2`);
      button.type = 'button'; button.disabled = Store.busy || selectingOrder;
      button.addEventListener('click', async () => {
        if (Store.busy || selectingOrder) return;
        const pay = action === 'pay';
        const advance = ['shipped', 'delivered'].includes(action);
        if (!await Store.confirm(advance ? 'Avançar pedido?' : pay ? 'Confirme a compra' : 'Você quer mesmo cancelar?', advance ? `O pedido será marcado como ${orderLabels[action]}.` : pay ? `Pedido #${order.id}, total ${Store.money(Store.cents(order.total))}. Esta é uma confirmação de pagamento simulado.` : `O pedido #${order.id} será cancelado e suas unidades voltarão ao estoque.`, advance ? 'Confirmar' : pay ? 'Confirmar compra' : 'Cancelar pedido')) return;
        try {
          await Store.run(async () => {
            const result = Store.order(await Store.request(advance ? 'patch' : 'post', advance ? `/api/orders/${order.id}/status` : `/api/orders/${order.id}/${action}`, advance ? { status: action } : {}));
            if (result.id !== order.id || result.status !== (advance ? action : pay ? 'paid' : 'cancelled')) throw new Error('Resultado inesperado do pedido.');
            currentOrder = result; renderOrder(); await loadOrders();
          });
          ordersStatus.textContent = advance ? 'Status atualizado.' : pay ? 'Pagamento simulado confirmado.' : 'Pedido cancelado.';
        } catch (error) { ordersStatus.textContent = Store.error(error) + ' Se necessário, selecione o pedido novamente para conferir o estado atual.'; }
      });
      orderDetail.append(button);
    }
  }
}

async function selectOrder(id) {
  if (selectingOrder || Store.busy) return;
  selectingOrder = true; currentOrder = null; orderDetail.replaceChildren(orderText('p', 'Carregando pedido...'));
  try {
    const order = Store.order(await Store.request('get', `/api/orders/${id}`));
    if (order.id !== id) throw new Error('Pedido inesperado.');
    currentOrder = order;
    const url = new URL(window.location.href); url.search = new URLSearchParams({ id }); window.history.replaceState(null, '', url);
    ordersStatus.textContent = '';
  } catch (error) { orderDetail.replaceChildren(orderText('p', Store.error(error), 'text-danger')); }
  finally { selectingOrder = false; renderOrder(); }
}

async function loadOrders() {
  const orders = await Store.request('get', `/api/orders${document.getElementById('orders-filter').value ? '?status=' + document.getElementById('orders-filter').value : ''}`);
  if (!Array.isArray(orders)) throw new Error('Lista de pedidos inválida.');
  for (const order of orders) Store.order(order);
  const target = document.getElementById('orders-list');
  target.replaceChildren(...orders.map(order => {
    const button = orderText('button', `Pedido #${order.id} · ${orderLabels[order.status]} · ${Store.money(Store.cents(order.total))} · ${new Date(order.created_at).toLocaleDateString('pt-BR')}${Store.user.role === 'admin' ? ' · Cliente #' + order.user_id : ''}`, 'list-group-item list-group-item-action');
    button.type = 'button'; button.addEventListener('click', () => selectOrder(order.id)); return button;
  }));
  if (!orders.length) target.append(orderText('p', 'Você ainda não tem pedidos.'));
}

window.addEventListener('store:change', renderOrder);
(async () => {
  try {
    await Store.session;
    if (!Store.user) { window.location.replace('login.html?next=pedidos.html'); return; }
    if (Store.user.role === 'admin') {
      document.querySelector('main h1').textContent = 'Pedidos';
      document.title = 'Pedidos | TicoGames';
    }
    await loadOrders(); document.getElementById('orders-content').hidden = false; ordersStatus.textContent = '';
    const id = new URLSearchParams(window.location.search).get('id');
    if (id !== null) {
      if (/^[1-9]\d*$/.test(id) && Store.id(Number(id))) await selectOrder(Number(id));
      else ordersStatus.textContent = 'Selecione um pedido válido na lista.';
    }
  } catch (error) { ordersStatus.textContent = Store.error(error); }
})();

document.getElementById('orders-filter').addEventListener('change', () => loadOrders().catch(error => { ordersStatus.textContent = Store.error(error); }));
