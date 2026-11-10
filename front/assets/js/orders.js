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

    if (Store.user?.role === 'admin') {
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
        const price = Store.cents(item.unit_price);
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
        createOrderText('h2', `Pedido #${order.id}`, 'h4'),
        createOrderText('p', orderLabels[order.status], 'fw-semibold'),
        items,
        createOrderText('p', `Total confirmado pelo backend: ${Store.money(Store.cents(order.total))}`, 'fw-bold'),
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
        message = `Pedido #${order.id}, total ${Store.money(Store.cents(order.total))}.`
            + ' Esta é uma confirmação de pagamento simulado.';
        confirmLabel = 'Confirmar compra';
        expectedStatus = 'paid';
        successMessage = 'Pagamento simulado confirmado.';
    } else {
        title = 'Você quer mesmo cancelar?';
        message = `O pedido #${order.id} será cancelado e suas unidades voltarão ao estoque.`;
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
            const method = isAdvance ? 'patch' : 'post';
            const url = isAdvance
                ? `/api/orders/${order.id}/status`
                : `/api/orders/${order.id}/${action}`;
            const payload = isAdvance ? { status: action } : {};
            const response = await Store.request(method, url, payload);
            const result = Store.order(response);

            if (result.id !== order.id || result.status !== expectedStatus) {
                throw new Error('Resultado inesperado do pedido.');
            }

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
        const response = await Store.request('get', `/api/orders/${id}`);
        const order = Store.order(response);

        if (order.id !== id) {
            throw new Error('Pedido inesperado.');
        }

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
    const total = Store.money(Store.cents(order.total));
    const date = new Date(order.created_at).toLocaleDateString('pt-BR');
    let text = `Pedido #${order.id} · ${orderLabels[order.status]} · ${total} · ${date}`;

    if (Store.user.role === 'admin') {
        text += ` · Cliente #${order.user_id}`;
    }

    const button = createOrderText('button', text, 'list-group-item list-group-item-action');
    button.type = 'button';
    button.addEventListener('click', () => selectOrder(order.id));
    return button;
}

async function loadOrders() {
    let url = '/api/orders';

    if (ordersFilter.value) {
        url += `?status=${ordersFilter.value}`;
    }

    const orders = await Store.request('get', url);

    if (!Array.isArray(orders)) {
        throw new Error('Lista de pedidos inválida.');
    }

    for (const order of orders) {
        Store.order(order);
    }

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

        if (Store.user.role === 'admin') {
            document.querySelector('main h1').textContent = 'Pedidos';
            document.title = 'Pedidos | TicoGames';
        }

        await loadOrders();
        document.getElementById('orders-content').hidden = false;
        ordersStatus.textContent = '';

        const id = new URLSearchParams(window.location.search).get('id');

        if (id !== null) {
            if (/^[1-9]\d*$/.test(id) && Store.id(Number(id))) {
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
