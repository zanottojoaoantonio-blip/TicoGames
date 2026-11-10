const AdminAPI = {
    async saveProduct(id, data, image) {
        if (image) {
            const upload = new FormData();
            upload.append('image', image);
            const result = await Store.request('post', '/api/product-images', upload);
            data.image_path = result.image_path;
        }

        return Store.request(id ? 'patch' : 'post', id ? `/api/products/${id}` : '/api/products', data);
    },

    deactivateProduct(id) {
        return Store.request('delete', `/api/products/${id}`);
    },

    saveCategory(id, data) {
        return Store.request(id ? 'patch' : 'post', id ? `/api/categories/${id}` : '/api/categories', data);
    },

    removeCategory(id) {
        return Store.request('delete', `/api/categories/${id}`);
    },

    updateOrderStatus(id, status) {
        return Store.request('patch', `/api/orders/${id}/status`, { status });
    },
};
