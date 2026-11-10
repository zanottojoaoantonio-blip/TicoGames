Object.assign(Validacoes, {
    product(product) {
        if (
            !Validacoes.id(product?.id)
            || typeof product.name !== 'string'
            || typeof product.image_path !== 'string'
            || !Number.isInteger(product.stock)
            || product.stock < 0
            || typeof product.is_active !== 'boolean'
        ) {
            throw new Error('Produto inválido recebido da API.');
        }

        Validacoes.cents(product.price);
        return product;
    },

    productDetails(product, id) {
        Validacoes.product(product);

        if (
            product.id !== id
            || typeof product.description !== 'string'
            || !/^\d{1,8}\.\d{2}$/.test(product.price)
            || typeof product.platform?.name !== 'string'
        ) {
            throw new Error('A API não retornou os dados esperados do produto.');
        }

        for (const items of [product.categories, product.genres]) {
            if (!Array.isArray(items) || items.some(item => typeof item?.name !== 'string')) {
                throw new Error('A API não retornou os dados esperados do produto.');
            }
        }
    },

    catalogPage(data, meta) {
        if (!Array.isArray(data) || !Number.isInteger(meta?.last_page)) {
            throw new Error('Página inválida da API.');
        }
    },
});
