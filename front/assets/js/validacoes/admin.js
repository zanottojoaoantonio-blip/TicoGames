Object.assign(Validacoes, {
    admin(user) {
        return user?.role === 'admin';
    },

    adminProduct(product, id) {
        Validacoes.product(product);

        if (id && product.id !== id) {
            throw new Error('Produto inesperado.');
        }

        return product;
    },

    products(products) {
        if (!Array.isArray(products)) {
            throw new Error('Lista de produtos inválida.');
        }

        for (const product of products) {
            Validacoes.product(product);

            if (!Array.isArray(product.categories) || !Array.isArray(product.genres)) {
                throw new Error('Relacionamentos inválidos.');
            }
        }
    },
});
