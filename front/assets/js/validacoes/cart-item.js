Object.assign(Validacoes, {
    cartItem(item) {
        if (
            !Validacoes.id(item?.id)
            || !Validacoes.id(item.product_id)
            || !Validacoes.quantity(item.quantity)
            || Validacoes.product(item.product).id !== item.product_id
        ) {
            throw new Error('Carrinho inválido recebido da API.');
        }

        Validacoes.cents(item.subtotal);
        return item;
    },
});
