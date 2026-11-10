const ProdutoAPI = {
    list(params) {
        return Store.request('get', `/api/products?${new URLSearchParams(params)}`, undefined, true);
    },

    get(id) {
        return Store.request('get', `/api/products/${id}`);
    },

    categories() {
        return Store.request('get', '/api/categories');
    },

    platforms() {
        return Store.request('get', '/api/platforms');
    },

    genres() {
        return Store.request('get', '/api/genres');
    },
};
