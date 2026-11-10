Object.assign(Validacoes, {
    platform(item) {
        if (!Validacoes.id(item?.id) || typeof item.name !== 'string') {
            throw new Error('Opções inválidas da API.');
        }

        return item;
    },

    platforms(items) {
        return Validacoes.list(items, Validacoes.platform, 'Opções inválidas da API.');
    },
});
