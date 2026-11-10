Object.assign(Validacoes, {
    order(order) {
        const statuses = ['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'];

        if (
            !Validacoes.id(order?.id)
            || !Validacoes.id(order.customer_number)
            || !statuses.includes(order.status)
            || !Array.isArray(order.items)
            || !order.items.length
        ) {
            throw new Error('Pedido inválido recebido da API.');
        }

        for (const item of order.items) {
            Validacoes.orderItem(item);
        }

        Validacoes.cents(order.total);

        for (const field of Validacoes.addressFields) {
            if (typeof order[`shipping_${field}`] !== 'string') {
                throw new Error('Endereço histórico inválido.');
            }
        }

        return order;
    },

    orders(orders) {
        if (!Array.isArray(orders)) {
            throw new Error('Lista de pedidos inválida.');
        }

        for (const order of orders) {
            Validacoes.order(order);
        }
    },

    selectedOrder(order, id) {
        Validacoes.order(order);

        if (order.id !== id) {
            throw new Error('Pedido inesperado.');
        }

        return order;
    },

    orderResult(order, id, status) {
        Validacoes.order(order);

        if (order.id !== id || order.status !== status) {
            throw new Error('Resultado inesperado do pedido.');
        }

        return order;
    },

    createdOrder(order) {
        Validacoes.order(order);

        if (order.status !== 'pending_payment') {
            throw new Error('Estado inesperado do pedido criado.');
        }

        return order;
    },
});
