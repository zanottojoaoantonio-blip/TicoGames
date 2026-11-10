Object.assign(Validacoes, {
    account(user) {
        if (
            !Validacoes.id(user?.id)
            || typeof user.name !== 'string'
            || !['customer', 'admin'].includes(user.role)
        ) {
            throw new Error('Conta inválida recebida da API.');
        }

        return user;
    },
});
