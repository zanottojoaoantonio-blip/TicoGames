Object.assign(Validacoes, {
    cart(cart) {
        if (!Array.isArray(cart?.items)) {
            throw new Error('Carrinho inválido recebido da API.');
        }

        for (const item of cart.items) {
            Validacoes.cartItem(item);
        }

        Validacoes.cents(cart.total);
        Validacoes.cents(cart.subtotal);
        Validacoes.cents(cart.shipping_cost);
    },
});
