const UsuarioAPI = {
    me() {
        return Store.request('get', '/api/me');
    },

    login(credentials) {
        return Store.request('post', '/api/login', credentials);
    },

    register(data) {
        return Store.request('post', '/api/register', data);
    },

    update(data) {
        return Store.request('patch', '/api/user', data);
    },

    logout() {
        return Store.request('post', '/api/logout');
    },

    cart() {
        return Store.request('get', '/api/cart');
    },

    addCartItem(productId, quantity) {
        return Store.request('post', '/api/cart/items', { product_id: productId, quantity });
    },

    updateCartItem(id, quantity) {
        return Store.request('patch', `/api/cart/items/${id}`, { quantity });
    },

    removeCartItem(id) {
        return Store.request('delete', `/api/cart/items/${id}`);
    },

    clearCart() {
        return Store.request('delete', '/api/cart');
    },

    addresses() {
        return Store.request('get', '/api/addresses');
    },

    address(id) {
        return Store.request('get', `/api/addresses/${id}`);
    },

    saveAddress(id, data) {
        return Store.request(id ? 'patch' : 'post', id ? `/api/addresses/${id}` : '/api/addresses', data);
    },

    removeAddress(id) {
        return Store.request('delete', `/api/addresses/${id}`);
    },

    orders(status) {
        const query = status ? `?${new URLSearchParams({ status })}` : '';
        return Store.request('get', `/api/orders${query}`);
    },

    order(id) {
        return Store.request('get', `/api/orders/${id}`);
    },

    createOrder(addressId) {
        return Store.request('post', '/api/orders', { address_id: addressId });
    },

    orderAction(id, action) {
        return Store.request('post', `/api/orders/${id}/${action}`, {});
    },
};
