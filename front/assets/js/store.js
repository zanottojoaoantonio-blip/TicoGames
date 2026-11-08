axios.defaults ??= {};
axios.defaults.baseURL = window.TICOGAMES_CONFIG?.apiBaseUrl || '';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// Compartilhado pelas páginas; a autenticação permanece nos cookies do Laravel.
const Store = {
  user: null, cart: [], busy: false, cartLoaded: false,
  sessionLoaded: false, sessionError: null, cartLoading: true, cartError: null,
  cartTotal: '0.00', cartSubtotal: '0.00', shippingCost: '0.00', cartWarnings: [],
  headers: { Accept: 'application/json' },
  id(value) { return Number.isSafeInteger(value) && value > 0; },
  quantity(value) { return Store.id(value) && value <= 4294967295; },
  cents(value) {
    if (typeof value !== 'string' || !/^\d{1,20}\.\d{2}$/.test(value)) throw new Error('Preço inválido recebido da API.');
    return BigInt(value.replace('.', ''));
  },
  money(cents) {
    return `R$ ${new Intl.NumberFormat('pt-BR').format(cents / 100n)},${String(cents % 100n).padStart(2, '0')}`;
  },
  total() { return Store.cents(Store.cartTotal); },
  product(product) {
    if (!Store.id(product?.id) || typeof product.name !== 'string' || typeof product.image_path !== 'string'
      || !Number.isInteger(product.stock) || product.stock < 0 || typeof product.is_active !== 'boolean') throw new Error('Produto inválido recebido da API.');
    Store.cents(product.price);
    return product;
  },
  account(user) {
    if (!Store.id(user?.id) || typeof user.name !== 'string' || !['customer', 'admin'].includes(user.role)) throw new Error('Conta inválida recebida da API.');
    return user;
  },
  order(order) {
    if (!Store.id(order?.id) || !['pending_payment', 'paid', 'shipped', 'delivered', 'cancelled'].includes(order.status)
      || !Array.isArray(order.items) || !order.items.length || order.items.some(item => !Store.id(item?.id)
        || typeof item.product_name !== 'string' || !Store.quantity(item.quantity))) throw new Error('Pedido inválido recebido da API.');
    Store.cents(order.total);
    for (const item of order.items) Store.cents(item.unit_price);
    for (const field of ['recipient_name', 'postal_code', 'street', 'number', 'neighborhood', 'city', 'state']) {
      if (typeof order[`shipping_${field}`] !== 'string') throw new Error('Endereço histórico inválido.');
    }
    return order;
  },
  changed() { window.dispatchEvent(new Event('store:change')); },
  error(error) {
    const status = error.response?.status;
    if (status === 401) return 'Sua sessão terminou. Entre novamente para continuar.';
    if (status === 403) return 'Sua conta não tem permissão para esta ação.';
    if (status === 419) return 'A sessão de acesso expirou. Atualize a página e tente novamente.';
    if (status === 429) return 'Muitas tentativas. Aguarde um minuto antes de tentar novamente.';
    if (status === 404) return 'O registro não foi encontrado ou não está disponível para sua conta.';
    if (status === 409) return error.response.data?.message || 'O estado dos dados mudou. Atualize e tente novamente.';
    if (status === 422) {
      const messages = Object.values(error.response.data?.errors ?? {}).flat().filter(value => typeof value === 'string');
      return messages.join(' ') || error.response.data?.message || 'Confira os dados informados.';
    }
    return 'Não foi possível confirmar a operação. Atualize ou consulte os dados antes de repetir.';
  },
  async request(method, url, data, envelope = false) {
    const { headers } = Store;
    if (method === 'get') {
      const response = await axios.get(url, { headers, timeout: 15000 });
      return envelope ? response.data : response.data.data;
    }
    // Sem repetição automática: inclusão e incorporação somam quantidades no servidor.
    await axios.get('/sanctum/csrf-cookie', { headers, timeout: 15000 });
    const response = await axios.request({ method, url, data, headers, timeout: 15000 });
    return response.status === 204 ? null : response.data.data;
  },
  setCart(cart) {
    if (!Array.isArray(cart?.items) || cart.items.some(item => !Store.id(item?.id) || !Store.id(item.product_id)
      || !Store.quantity(item.quantity) || Store.product(item.product).id !== item.product_id)) throw new Error('Carrinho inválido recebido da API.');
    Store.cents(cart.total); Store.cents(cart.subtotal); Store.cents(cart.shipping_cost);
    for (const item of cart.items) Store.cents(item.subtotal);
    Store.cartTotal = cart.total; Store.cartSubtotal = cart.subtotal; Store.shippingCost = cart.shipping_cost;
    Store.cartWarnings = Array.isArray(cart.warnings) ? cart.warnings : [];
    Store.cart = cart.items;
    Store.cartLoaded = true;
    Store.cartLoading = false;
    Store.cartError = null;
    Store.changed();
  },
  async loadCart() {
    Store.cartLoaded = false;
    Store.cartLoading = true;
    Store.cartError = null;
    Store.cart = [];
    Store.changed();
    try {
      Store.setCart(await Store.request('get', '/api/cart'));
    } catch (error) {
      Store.cartError = error;
      throw error;
    } finally {
      Store.cartLoading = false;
      Store.changed();
    }
  },
  initialize() {
    Store.user = null;
    Store.sessionLoaded = false;
    Store.sessionError = null;
    Store.cart = [];
    Store.cartLoaded = false;
    Store.cartLoading = true;
    Store.cartError = null;
    Store.changed();
    Store.session = (async () => {
      try { Store.user = Store.account(await Store.request('get', '/api/me')); }
      catch (error) {
        if (error.response?.status !== 401) { Store.sessionError = error; throw error; }
      } finally {
        Store.sessionLoaded = true;
        Store.changed();
      }
    })();
    Store.ready = Store.session.then(() => Store.loadCart(), error => {
      Store.cartError = error;
      Store.cartLoading = false;
      Store.changed();
      throw error;
    });
    Store.ready.catch(() => {}); // A tela apresenta a falha, sem rejeição não tratada.
    return Store.ready;
  },
  async ensureReady() {
    try { await Store.ready; }
    catch { await Store.initialize(); } // Repetir somente a leitura, antes de uma nova ação explícita.
  },
  async run(action) {
    if (Store.busy) throw new Error('Aguarde a operação em andamento.');
    Store.busy = true; Store.changed();
    try { return await action(); }
    finally { Store.busy = false; Store.changed(); }
  },
  async add(productId, quantity) {
    await Store.ensureReady();
    if (!Store.id(productId) || !Store.quantity(quantity)) throw new Error('Informe uma quantidade inteira e positiva.');
    return Store.run(async () => Store.setCart(await Store.request('post', '/api/cart/items', { product_id: productId, quantity })));
  },
  async update(itemId, quantity) {
    await Store.ensureReady();
    if (!Store.id(itemId) || !(quantity === 0 || Store.quantity(quantity))) throw new Error('Informe uma quantidade inteira a partir de zero.');
    return Store.run(async () => Store.setCart(await Store.request('patch', `/api/cart/items/${itemId}`, { quantity })));
  },
  async remove(itemId) {
    await Store.ensureReady();
    if (!Store.id(itemId)) throw new Error('Item inválido.');
    return Store.run(async () => {
      await Store.request('delete', `/api/cart/items/${itemId}`);
      await Store.loadCart();
    });
  },
  async clear() {
    await Store.session;
    return Store.run(async () => {
      await Store.request('delete', '/api/cart');
      await Store.loadCart();
      Store.ready = Promise.resolve();
    });
  },
  async acceptSession(user) {
    Store.user = Store.account(user);
    Store.sessionLoaded = true; Store.sessionError = null; Store.session = Promise.resolve();
    Store.ready = Store.loadCart();
    Store.ready.catch(() => {});
    try { await Store.ready; } catch (error) { Store.notice?.(Store.error(error), true); }
    if (Store.cartWarnings.length) Store.notice?.(Store.cartWarnings.join(' '), true);
  },
};

Store.initialize();
