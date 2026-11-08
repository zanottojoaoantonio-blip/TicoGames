const adminStatus = document.getElementById('admin-status');
const adminContent = document.getElementById('admin-content');
const adminForm = document.getElementById('admin-form');
const adminFields = document.getElementById('admin-fields');
const adminFormStatus = document.getElementById('admin-form-status');
let editingProduct = null;
let adminProducts = [];
let adminPage = 1;

function adminReset() {
  editingProduct = null;
  adminForm.reset();
  Store.fieldErrors(adminForm, {});
  for (const name of ['category_id', 'genre_ids']) document.getElementById(`admin-${name}-error`).textContent = '';
  document.getElementById('admin-form-title').textContent = 'Novo produto';
  adminFormStatus.textContent = '';
}

function adminOptions(name, items) {
  if (!Array.isArray(items) || items.some(item => !Store.id(item?.id) || typeof item.name !== 'string')) throw new Error('Opções inválidas da API.');
  if (name === 'platforms') {
    const select = document.getElementById('admin-platform_id');
    select.replaceChildren(new Option('Selecione', ''), ...items.map(item => new Option(item.name, item.id)));
    return;
  }
  const target = document.getElementById(`admin-${name}`);
  const field = name === 'categories' ? 'category_id' : 'genre_ids[]';
  target.replaceChildren(...items.map(item => {
    const label = document.createElement('label'); label.className = 'form-check';
    const input = document.createElement('input'); input.type = name === 'categories' ? 'radio' : 'checkbox'; input.name = field;
    input.value = item.id; input.id = `admin-${name}-${item.id}`; input.className = 'form-check-input';
    input.setAttribute('aria-describedby', `admin-${field.replace('[]', '')}-error`);
    label.append(input, document.createTextNode(` ${item.name}`)); return label;
  }));
}

function adminRows() {
  const target = document.getElementById('admin-products');
  target.replaceChildren(...adminProducts.map(product => {
    const row = document.createElement('tr');
    for (const text of [product.name, product.platform?.name ?? '', product.categories.map(item => item.name).join(' · '),
      Store.money(Store.cents(product.price)), product.stock, product.is_active ? 'Ativo' : 'Inativo']) {
      const cell = document.createElement('td'); cell.textContent = text; row.append(cell);
    }
    const actions = document.createElement('td'); actions.className = 'text-nowrap';
    const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'btn btn-sm btn-outline-primary me-2'; edit.textContent = 'Editar';
    const deactivate = document.createElement('button'); deactivate.type = 'button'; deactivate.className = 'btn btn-sm btn-outline-danger'; deactivate.textContent = 'Desativar';
    edit.disabled = Store.busy; deactivate.disabled = Store.busy || !product.is_active;
    edit.setAttribute('aria-label', `Editar ${product.name}, ${product.platform?.name ?? ''}`);
    deactivate.setAttribute('aria-label', `Desativar ${product.name}, ${product.platform?.name ?? ''}`);
    edit.addEventListener('click', () => adminEdit(product.id));
    deactivate.addEventListener('click', async () => {
      if (!await Store.confirm('Desativar produto?', `${product.name} será retirado do catálogo público. O histórico será preservado.`, 'Desativar')) return;
      try {
        await Store.run(() => Store.request('delete', `/api/products/${product.id}`));
        product.is_active = false; adminRows();
        if (editingProduct === product.id) adminReset();
        adminStatus.textContent = 'Produto desativado.';
      } catch (error) { adminStatus.textContent = Store.error(error); }
    });
    actions.append(edit, deactivate); row.append(actions); return row;
  }));
  if (!adminProducts.length) adminStatus.textContent = 'Nenhum produto cadastrado.';
}

async function adminList() {
  const { data: products, meta } = await Store.request('get', `/api/products?page=${adminPage}`, undefined, true);
  document.getElementById('admin-page').textContent = `Página ${meta.current_page} de ${meta.last_page}`;
  document.getElementById('admin-prev').disabled = meta.current_page <= 1;
  document.getElementById('admin-next').disabled = meta.current_page >= meta.last_page;
  if (!Array.isArray(products)) throw new Error('Lista de produtos inválida.');
  for (const product of products) {
    Store.product(product);
    if (!Array.isArray(product.categories) || !Array.isArray(product.genres)) throw new Error('Relacionamentos inválidos.');
  }
  adminProducts = products; adminRows();
}

async function adminEdit(id) {
  try {
    await Store.run(async () => {
      const product = Store.product(await Store.request('get', `/api/products/${id}`));
      if (product.id !== id) throw new Error('Produto inesperado.');
      adminReset(); editingProduct = id;
      for (const name of ['name', 'description', 'image_path', 'platform_id', 'price', 'stock']) document.getElementById(`admin-${name}`).value = product[name];
      document.getElementById('admin-is_active').checked = product.is_active;
      for (const [field, values] of [['category_id', product.categories], ['genre_ids[]', product.genres]]) {
        for (const input of adminForm.querySelectorAll(`[name="${field}"]`)) input.checked = values.some(value => value.id === Number(input.value));
      }
      document.getElementById('admin-form-title').textContent = `Editar produto #${id}`;
      adminForm.scrollIntoView({ block: 'start' });
    });
    document.getElementById('admin-name').focus({ preventScroll: true });
  } catch (error) { adminStatus.textContent = Store.error(error); }
}

adminForm.addEventListener('submit', async event => {
  event.preventDefault();
  if (adminFields.disabled || Store.busy || !adminForm.reportValidity()) return;
  const values = new FormData(adminForm);
  const payload = {
    name: values.get('name').trim(), description: values.get('description').trim(), image_path: values.get('image_path').trim(),
    platform_id: Number(values.get('platform_id')), price: values.get('price'), stock: Number(values.get('stock')),
    is_active: document.getElementById('admin-is_active').checked,
    category_id: Number(values.get('category_id')), genre_ids: values.getAll('genre_ids[]').map(Number),
  };
  Store.fieldErrors(adminForm, {});
  for (const name of ['category_id', 'genre_ids']) document.getElementById(`admin-${name}-error`).textContent = '';
  if (!payload.category_id) {
    document.getElementById('admin-category_id-error').textContent = 'Selecione uma categoria.';
    adminForm.querySelector('[name="category_id"]')?.focus(); return;
  }
  adminFields.disabled = true; adminForm.setAttribute('aria-busy', 'true'); adminFormStatus.textContent = 'Salvando...';
  let focus;
  try {
    await Store.run(async () => {
      const image = document.getElementById('admin-image').files[0];
      if (image) {
        const upload = new FormData(); upload.append('image', image);
        payload.image_path = (await Store.request('post', '/api/product-images', upload)).image_path;
      }
      const product = Store.product(await Store.request(editingProduct ? 'patch' : 'post', editingProduct ? `/api/products/${editingProduct}` : '/api/products', payload));
      if (editingProduct && product.id !== editingProduct) throw new Error('Produto inesperado.');
      const index = adminProducts.findIndex(item => item.id === product.id);
      if (index < 0) adminProducts.push(product); else adminProducts[index] = product;
      adminReset(); await adminList(); adminFormStatus.textContent = 'Produto salvo no backend.';
    });
    focus = document.getElementById('admin-name');
  } catch (error) {
    adminFormStatus.textContent = Store.error(error); focus = Store.fieldErrors(adminForm, error);
    for (const field of ['category_id', 'genre_ids']) {
      const messages = Object.entries(error.response?.data?.errors ?? {}).filter(([key]) => key === field || key.startsWith(`${field}.`)).flatMap(([, value]) => value);
      document.getElementById(`admin-${field}-error`).textContent = messages.filter(value => typeof value === 'string').join(' ');
    }
  } finally {
    adminFields.disabled = false; adminForm.setAttribute('aria-busy', 'false'); focus?.focus();
  }
});
for (const id of ['admin-new', 'admin-cancel']) document.getElementById(id).addEventListener('click', () => {
  if (Store.busy) return;
  adminReset(); adminForm.scrollIntoView({ block: 'start' }); document.getElementById('admin-name').focus({ preventScroll: true });
});
window.addEventListener('store:change', () => {
  if (!adminContent.hidden) {
    adminFields.disabled = Store.busy;
    adminForm.setAttribute('aria-busy', Store.busy ? 'true' : 'false');
    adminRows();
  }
});

(async () => {
  try {
    await Store.session;
    if (Store.user?.role !== 'admin') {
      if (!Store.user) { window.location.replace('login.html?next=admin.html'); return; }
      adminStatus.textContent = Store.user ? 'Somente administradores podem gerenciar produtos.' : 'Entre com uma conta administrativa.';
      document.getElementById('admin-access').hidden = false; return;
    }
    const [platforms, categories, genres] = await Promise.all(['platforms', 'categories', 'genres'].map(name => Store.request('get', `/api/${name}`)));
    adminOptions('platforms', platforms); adminOptions('categories', categories); adminOptions('genres', genres);
    await loadAdminCategories(); await adminList(); adminStatus.textContent = 'Produtos carregados.'; adminContent.hidden = false; adminFields.disabled = false;
  } catch (error) { adminStatus.textContent = Store.error(error); }
})();


async function loadAdminCategories() {
  const categories = await Store.request('get', '/api/categories');
  const selected = adminForm.querySelector('[name="category_id"]:checked')?.value;
  adminOptions('categories', categories);
  const selectedInput = selected && document.getElementById(`admin-categories-${selected}`);
  if (selectedInput) selectedInput.checked = true;
  const target = document.getElementById('admin-category-list');
  target.replaceChildren(...categories.map(category => {
    const form = document.createElement('form'); form.className = 'd-flex gap-2 my-2';
    const input = document.createElement('input'); input.className = 'form-control'; input.value = category.name; input.required = true; input.maxLength = 255;
    input.setAttribute('aria-label', `Nome da categoria ${category.name}`);
    const save = document.createElement('button'); save.type = 'submit'; save.className = 'btn btn-outline-primary'; save.textContent = 'Salvar';
    const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-outline-danger'; remove.textContent = 'Excluir';
    form.append(input, save, remove);
    form.addEventListener('submit', async event => {
      event.preventDefault(); if (Store.busy || !form.reportValidity()) return;
      try { await Store.run(async () => { await Store.request('patch', `/api/categories/${category.id}`, { name: input.value.trim() }); await loadAdminCategories(); await adminList(); }); adminStatus.textContent = 'Categoria atualizada.'; }
      catch (error) { adminStatus.textContent = Store.error(error); }
    });
    remove.addEventListener('click', async () => {
      if (Store.busy || !await Store.confirm('Excluir categoria?', category.name, 'Excluir')) return;
      try { await Store.run(async () => { await Store.request('delete', `/api/categories/${category.id}`); await loadAdminCategories(); }); adminStatus.textContent = 'Categoria excluída.'; }
      catch (error) { adminStatus.textContent = Store.error(error); }
    });
    return form;
  }));
}

document.getElementById('admin-category-form').addEventListener('submit', async event => {
  event.preventDefault(); const form = event.currentTarget;
  if (Store.busy || !form.reportValidity()) return;
  try { await Store.run(async () => { await Store.request('post', '/api/categories', { name: document.getElementById('admin-category-name').value.trim() }); await loadAdminCategories(); form.reset(); }); adminStatus.textContent = 'Categoria criada.'; }
  catch (error) { adminStatus.textContent = Store.error(error); }
});
for (const [id, direction] of [['admin-prev', -1], ['admin-next', 1]]) document.getElementById(id).addEventListener('click', async () => {
  if (Store.busy) return;
  adminPage += direction;
  try { await Store.run(adminList); } catch (error) { adminStatus.textContent = Store.error(error); }
});
