Object.assign(Validacoes, {
    category(item) {
        if (!Validacoes.id(item?.id) || typeof item.name !== 'string') {
            throw new Error('Opções inválidas da API.');
        }

        return item;
    },

    categories(items) {
        return Validacoes.list(items, Validacoes.category, 'Opções inválidas da API.');
    },
});
