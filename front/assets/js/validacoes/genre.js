Object.assign(Validacoes, {
    genre(item) {
        if (!Validacoes.id(item?.id) || typeof item.name !== 'string') {
            throw new Error('Opções inválidas da API.');
        }

        return item;
    },

    genres(items) {
        return Validacoes.list(items, Validacoes.genre, 'Opções inválidas da API.');
    },
});
