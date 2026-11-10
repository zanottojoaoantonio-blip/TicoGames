Object.assign(Validacoes, {
    addressFields: [
        'recipient_name', 'postal_code', 'street', 'number', 'neighborhood', 'city', 'state',
    ],

    addresses(data) {
        if (!Array.isArray(data)) {
            throw new Error('Endereços inválidos.');
        }

        for (const address of data) {
            if (!Validacoes.id(address?.id)) {
                throw new Error('Endereços inválidos.');
            }

            for (const field of Validacoes.addressFields) {
                if (typeof address[field] !== 'string') {
                    throw new Error('Endereços inválidos.');
                }
            }
        }
    },

    selectedAddress(address, id) {
        if (address.id !== id) {
            throw new Error('Endereço inesperado.');
        }

        return address;
    },

    savedAddress(address) {
        if (!Validacoes.id(address?.id)) {
            throw new Error('Endereço inválido.');
        }

        return address;
    },
});
