const ordersStatus = document.getElementById('orders-status');
const orderDetail = document.getElementById('order-detail');
const ordersFilter = document.getElementById('orders-filter');
const orderLabels = {
    pending_payment: 'Aguardando pagamento simulado',
    paid: 'Pago (simulado)',
    shipped: 'Enviado',
    delivered: 'Entregue',
    cancelled: 'Cancelado',
};
const orderActionLabels = {
    pay: 'Confirmar pagamento simulado',
    cancel: 'Cancelar pedido',
    shipped: 'Marcar como enviado',
    delivered: 'Marcar como entregue',
};

let currentOrder = null;
let selectingOrder = false;

function getOrderTitle(order) {
    return Validacoes.admin(Store.user) ? `Pedido #${order.id}` : `Pedido ${order.customer_number}`;
}

function createOrderText(tag, text, className = '') {
    const element = document.createElement(tag);
    element.textContent = text;
    element.className = className;
    return element;
}

function getOrderActions(order) {
    if (order.status === 'pending_payment') {
        return ['pay', 'cancel'];
    }

    if (Validacoes.admin(Store.user)) {
        if (order.status === 'paid') {
            return ['shipped'];
        }

        if (order.status === 'shipped') {
            return ['delivered'];
        }
    }

    return [];
}

function renderOrder() {
    if (!currentOrder) {
        return;
    }

    const order = currentOrder;
    const items = document.createElement('ul');
    items.className = 'list-group list-group-flush my-4';

    for (const item of order.items) {
        const price = Validacoes.cents(item.unit_price);
        const subtotal = price * BigInt(item.quantity);
        const description = `${item.quantity} × ${item.product_name}`
            + ` · ${Store.money(price)} cada · ${Store.money(subtotal)}`;

        items.append(createOrderText('li', description, 'list-group-item px-0'));
    }

    const complement = order.shipping_complement ? `, ${order.shipping_complement}` : '';
    const address = `${order.shipping_recipient_name} · ${order.shipping_street}, ${order.shipping_number}${complement}`
        + ` · ${order.shipping_neighborhood} · ${order.shipping_city}/${order.shipping_state}`
        + ` · ${order.shipping_postal_code}`;

    orderDetail.replaceChildren(
        createOrderText('h2', getOrderTitle(order), 'h4'),
        createOrderText('p', orderLabels[order.status], 'fw-semibold'),
        items,
        createOrderText('p', `Total confirmado pelo backend: ${Store.money(Validacoes.cents(order.total))}`, 'fw-bold'),
        createOrderText('h3', 'Endereço deste pedido', 'h6 mt-4'),
        createOrderText('p', address),
    );

    let dates = `Criado em ${new Date(order.created_at).toLocaleString('pt-BR')}`;

    if (order.paid_at) {
        dates += ` · Pago em ${new Date(order.paid_at).toLocaleString('pt-BR')}`;
    }

    orderDetail.append(createOrderText('p', dates));

    const actions = getOrderActions(order);

    if (!actions.length) {
        return;
    }

    orderDetail.append(createOrderText(
        'p',
        'Pagamento somente simulado. O estoque foi reservado na criação do pedido; o cancelamento devolve as unidades.',
        'small text-secondary',
    ));

    for (const action of actions) {
        const buttonClass = action === 'pay' ? 'btn-accent' : 'btn-outline-danger';
        const button = createOrderText(
            'button', orderActionLabels[action], `btn ${buttonClass} me-2 mb-2`,
        );

        button.type = 'button';
        button.disabled = Store.busy || selectingOrder;
        button.addEventListener('click', () => performOrderAction(order, action));
        orderDetail.append(button);
    }
}

async function performOrderAction(order, action) {
    if (Store.busy || selectingOrder) {
        return;
    }

    const isPayment = action === 'pay';
    const isAdvance = ['shipped', 'delivered'].includes(action);
    let title;
    let message;
    let confirmLabel;
    let expectedStatus;
    let successMessage;

    if (isAdvance) {
        title = 'Avançar pedido?';
        message = `O pedido será marcado como ${orderLabels[action]}.`;
        confirmLabel = 'Confirmar';
        expectedStatus = action;
        successMessage = 'Status atualizado.';
    } else if (isPayment) {
        title = 'Confirme a compra';
        message = `${getOrderTitle(order)}, total ${Store.money(Validacoes.cents(order.total))}.`
            + ' Esta é uma confirmação de pagamento simulado.';
        confirmLabel = 'Confirmar compra';
        expectedStatus = 'paid';
        successMessage = 'Pagamento simulado confirmado.';
    } else {
        title = 'Você quer mesmo cancelar?';
        message = `O ${getOrderTitle(order).toLowerCase()} será cancelado e suas unidades voltarão ao estoque.`;
        confirmLabel = 'Cancelar pedido';
        expectedStatus = 'cancelled';
        successMessage = 'Pedido cancelado.';
    }

    const confirmed = await Store.confirm(title, message, confirmLabel);

    if (!confirmed) {
        return;
    }

    try {
        await Store.run(async () => {
            const response = isAdvance
                ? await AdminAPI.updateOrderStatus(order.id, action)
                : await UsuarioAPI.orderAction(order.id, action);
            const result = Validacoes.orderResult(response, order.id, expectedStatus);

            currentOrder = result;
            renderOrder();
            await loadOrders();
        });

        ordersStatus.textContent = successMessage;
    } catch (error) {
        ordersStatus.textContent = Store.error(error)
            + ' Se necessário, selecione o pedido novamente para conferir o estado atual.';
    }
}

async function selectOrder(id) {
    if (selectingOrder || Store.busy) {
        return;
    }

    selectingOrder = true;
    currentOrder = null;
    orderDetail.replaceChildren(createOrderText('p', 'Carregando pedido...'));

    try {
        const response = await UsuarioAPI.order(id);
        const order = Validacoes.selectedOrder(response, id);

        currentOrder = order;
        const url = new URL(window.location.href);
        url.search = new URLSearchParams({ id });
        window.history.replaceState(null, '', url);
        ordersStatus.textContent = '';
    } catch (error) {
        orderDetail.replaceChildren(createOrderText('p', Store.error(error), 'text-danger'));
    } finally {
        selectingOrder = false;
        renderOrder();
    }
}

function createOrderListButton(order) {
    const total = Store.money(Validacoes.cents(order.total));
    const date = new Date(order.created_at).toLocaleDateString('pt-BR');
    let text = `${getOrderTitle(order)} · ${orderLabels[order.status]} · ${total} · ${date}`;

    if (Validacoes.admin(Store.user)) {
        text += ` · Cliente #${order.user_id}`;
    }

    const button = createOrderText('button', text, 'list-group-item list-group-item-action');
    button.type = 'button';
    button.addEventListener('click', () => selectOrder(order.id));
    return button;
}

async function loadOrders() {
    const orders = await UsuarioAPI.orders(ordersFilter.value);

    Validacoes.orders(orders);

    const target = document.getElementById('orders-list');
    target.replaceChildren(...orders.map(createOrderListButton));

    if (!orders.length) {
        target.append(createOrderText('p', 'Você ainda não tem pedidos.'));
    }
}

async function initializeOrders() {
    try {
        await Store.session;

        if (!Store.user) {
            window.location.replace('login.html?next=pedidos.html');
            return;
        }

        if (Validacoes.admin(Store.user)) {
            document.querySelector('main h1').textContent = 'Pedidos';
            document.title = 'Pedidos | TicoGames';
        }

        await loadOrders();
        document.getElementById('orders-content').hidden = false;
        ordersStatus.textContent = '';

        const id = new URLSearchParams(window.location.search).get('id');

        if (id !== null) {
            if (Validacoes.urlId(id)) {
                await selectOrder(Number(id));
            } else {
                ordersStatus.textContent = 'Selecione um pedido válido na lista.';
            }
        }
    } catch (error) {
        ordersStatus.textContent = Store.error(error);
    }
}

window.addEventListener('store:change', renderOrder);

ordersFilter.addEventListener('change', async () => {
    try {
        await loadOrders();
    } catch (error) {
        ordersStatus.textContent = Store.error(error);
    }
});

initializeOrders();
