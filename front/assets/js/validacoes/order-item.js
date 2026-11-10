Object.assign(Validacoes, {
    orderItem(item) {
        if (
            !Validacoes.id(item?.id)
            || typeof item.product_name !== 'string'
            || !Validacoes.quantity(item.quantity)
        ) {
            throw new Error('Pedido inválido recebido da API.');
        }

        Validacoes.cents(item.unit_price);
        return item;
    },
});
