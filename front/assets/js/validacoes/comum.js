const Validacoes = {
    id(value) {
        return Number.isSafeInteger(value) && value > 0;
    },

    quantity(value) {
        return Validacoes.id(value) && value <= 4294967295;
    },

    cents(value) {
        if (typeof value !== 'string' || !/^\d{1,20}\.\d{2}$/.test(value)) {
            throw new Error('Preço inválido recebido da API.');
        }

        return BigInt(value.replace('.', ''));
    },

    urlId(value) {
        return typeof value === 'string' && /^[1-9]\d*$/.test(value) && Validacoes.id(Number(value));
    },

    list(items, validate, message) {
        if (!Array.isArray(items)) {
            throw new Error(message);
        }

        for (const item of items) {
            validate(item);
        }

        return items;
    },
};
