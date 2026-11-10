const adminStatus = document.getElementById('admin-status');
const adminContent = document.getElementById('admin-content');
const adminForm = document.getElementById('admin-form');
const adminFields = document.getElementById('admin-fields');
const adminFormStatus = document.getElementById('admin-form-status');

let editingProduct = null;
let adminProducts = [];
let adminPage = 1;

function clearAdminErrors() {
    Store.fieldErrors(adminForm, {});

    for (const name of ['category_id', 'genre_ids']) {
        document.getElementById(`admin-${name}-error`).textContent = '';
    }
}

function resetAdminForm() {
    editingProduct = null;
    adminForm.reset();
    clearAdminErrors();
    document.getElementById('admin-form-title').textContent = 'Novo produto';
    adminFormStatus.textContent = '';
}

function renderAdminOptions(name, items) {
    const validate = { platforms: Validacoes.platforms, categories: Validacoes.categories, genres: Validacoes.genres };
    validate[name](items);

    if (name === 'platforms') {
        const select = document.getElementById('admin-platform_id');
        const options = items.map(item => new Option(item.name, item.id));
        select.replaceChildren(new Option('Selecione', ''), ...options);
        return;
    }

    const target = document.getElementById(`admin-${name}`);
    const field = name === 'categories' ? 'category_id' : 'genre_ids[]';
    const options = items.map(item => {
        const label = document.createElement('label');
        label.className = 'form-check';

        const input = document.createElement('input');
        input.type = name === 'categories' ? 'radio' : 'checkbox';
        input.name = field;
        input.value = item.id;
        input.id = `admin-${name}-${item.id}`;
        input.className = 'form-check-input';
        input.setAttribute('aria-describedby', `admin-${field.replace('[]', '')}-error`);

        label.append(input, document.createTextNode(` ${item.name}`));
        return label;
    });

    target.replaceChildren(...options);
}

function createAdminProductRow(product) {
    const row = document.createElement('tr');
    const values = [
        product.name,
        product.platform?.name ?? '',
        product.categories.map(category => category.name).join(' · '),
        Store.money(Validacoes.cents(product.price)),
        product.stock,
        product.is_active ? 'Ativo' : 'Inativo',
    ];

    for (const value of values) {
        const cell = document.createElement('td');
        cell.textContent = value;
        row.append(cell);
    }

    const actions = document.createElement('td');
    actions.className = 'text-nowrap';

    const editButton = document.createElement('button');
    editButton.type = 'button';
    editButton.className = 'btn btn-sm btn-outline-primary me-2';
    editButton.textContent = 'Editar';
    editButton.disabled = Store.busy;
    editButton.setAttribute('aria-label', `Editar ${product.name}, ${product.platform?.name ?? ''}`);
    editButton.addEventListener('click', () => editAdminProduct(product.id));

    const deactivateButton = document.createElement('button');
    deactivateButton.type = 'button';
    deactivateButton.className = 'btn btn-sm btn-outline-danger';
    deactivateButton.textContent = 'Desativar';
    deactivateButton.disabled = Store.busy || !product.is_active;
    deactivateButton.setAttribute('aria-label', `Desativar ${product.name}, ${product.platform?.name ?? ''}`);
    deactivateButton.addEventListener('click', () => deactivateAdminProduct(product));

    actions.append(editButton, deactivateButton);
    row.append(actions);
    return row;
}

function renderAdminProducts() {
    const rows = adminProducts.map(createAdminProductRow);
    document.getElementById('admin-products').replaceChildren(...rows);

    if (!adminProducts.length) {
        adminStatus.textContent = 'Nenhum produto cadastrado.';
    }
}

async function loadAdminProducts() {
    const { data: products, meta } = await ProdutoAPI.list({ page: adminPage });

    document.getElementById('admin-page').textContent = `Página ${meta.current_page} de ${meta.last_page}`;
    document.getElementById('admin-prev').disabled = meta.current_page <= 1;
    document.getElementById('admin-next').disabled = meta.current_page >= meta.last_page;

    Validacoes.products(products);

    adminProducts = products;
    renderAdminProducts();
}

function fillAdminProductForm(product) {
    resetAdminForm();
    editingProduct = product.id;

    const fields = ['name', 'description', 'image_path', 'platform_id', 'price', 'stock'];

    for (const name of fields) {
        document.getElementById(`admin-${name}`).value = product[name];
    }

    document.getElementById('admin-is_active').checked = product.is_active;

    const relationships = [
        ['category_id', product.categories],
        ['genre_ids[]', product.genres],
    ];

    for (const [field, values] of relationships) {
        for (const input of adminForm.querySelectorAll(`[name="${field}"]`)) {
            input.checked = values.some(value => value.id === Number(input.value));
        }
    }

    document.getElementById('admin-form-title').textContent = `Editar produto #${product.id}`;
    adminForm.scrollIntoView({ block: 'start' });
}

async function editAdminProduct(id) {
    try {
        await Store.run(async () => {
            const response = await ProdutoAPI.get(id);
            const product = Validacoes.adminProduct(response, id);

            fillAdminProductForm(product);
        });

        document.getElementById('admin-name').focus({ preventScroll: true });
    } catch (error) {
        adminStatus.textContent = Store.error(error);
    }
}

async function deactivateAdminProduct(product) {
    const confirmed = await Store.confirm(
        'Desativar produto?',
        `${product.name} será retirado do catálogo público. O histórico será preservado.`,
        'Desativar',
    );

    if (!confirmed) {
        return;
    }

    try {
        await Store.run(() => AdminAPI.deactivateProduct(product.id));
        product.is_active = false;
        renderAdminProducts();

        if (editingProduct === product.id) {
            resetAdminForm();
        }

        adminStatus.textContent = 'Produto desativado.';
    } catch (error) {
        adminStatus.textContent = Store.error(error);
    }
}

async function saveAdminProduct(event) {
    event.preventDefault();

    if (adminFields.disabled || Store.busy) {
        return;
    }

    const values = new FormData(adminForm);
    const payload = {
        name: values.get('name').trim(),
        description: values.get('description').trim(),
        image_path: values.get('image_path').trim(),
        platform_id: Number(values.get('platform_id')),
        price: values.get('price'),
        stock: Number(values.get('stock')),
        is_active: document.getElementById('admin-is_active').checked,
        category_id: Number(values.get('category_id')),
        genre_ids: values.getAll('genre_ids[]').map(Number),
    };

    clearAdminErrors();

    if (!payload.category_id) {
        document.getElementById('admin-category_id-error').textContent = 'Selecione uma categoria.';
        adminForm.querySelector('[name="category_id"]')?.focus();
        return;
    }

    adminFields.disabled = true;
    adminForm.setAttribute('aria-busy', 'true');
    adminFormStatus.textContent = 'Salvando...';
    let focusTarget;

    try {
        await Store.run(async () => {
            const image = document.getElementById('admin-image').files[0];

            const response = await AdminAPI.saveProduct(editingProduct, payload, image);
            const product = Validacoes.adminProduct(response, editingProduct);

            const index = adminProducts.findIndex(item => item.id === product.id);

            if (index < 0) {
                adminProducts.push(product);
            } else {
                adminProducts[index] = product;
            }

            resetAdminForm();
            await loadAdminProducts();
            adminFormStatus.textContent = 'Produto salvo no backend.';
        });

        focusTarget = document.getElementById('admin-name');
    } catch (error) {
        adminFormStatus.textContent = Store.error(error);
        focusTarget = Store.fieldErrors(adminForm, error);

        for (const field of ['category_id', 'genre_ids']) {
            const messages = Object.entries(error.response?.data?.errors ?? {})
                .filter(([key]) => key === field || key.startsWith(`${field}.`))
                .flatMap(([, value]) => value)
                .filter(value => typeof value === 'string');

            document.getElementById(`admin-${field}-error`).textContent = messages.join(' ');
        }
    } finally {
        adminFields.disabled = false;
        adminForm.setAttribute('aria-busy', 'false');
        focusTarget?.focus();
    }
}

function createAdminCategoryForm(category) {
    const form = document.createElement('form');
    form.className = 'd-flex gap-2 my-2';

    const input = document.createElement('input');
    input.className = 'form-control';
    input.value = category.name;
    input.required = true;
    input.maxLength = 255;
    input.setAttribute('aria-label', `Nome da categoria ${category.name}`);

    const saveButton = document.createElement('button');
    saveButton.type = 'submit';
    saveButton.className = 'btn btn-outline-primary';
    saveButton.textContent = 'Salvar';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-outline-danger';
    removeButton.textContent = 'Excluir';

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (Store.busy) {
            return;
        }

        try {
            await Store.run(async () => {
                await AdminAPI.saveCategory(category.id, {
                    name: input.value.trim(),
                });
                await loadAdminCategories();
                await loadAdminProducts();
            });

            adminStatus.textContent = 'Categoria atualizada.';
        } catch (error) {
            adminStatus.textContent = Store.error(error);
        }
    });

    removeButton.addEventListener('click', () => removeAdminCategory(category));
    form.append(input, saveButton, removeButton);
    return form;
}

async function loadAdminCategories() {
    const categories = await ProdutoAPI.categories();
    const selected = adminForm.querySelector('[name="category_id"]:checked')?.value;
    renderAdminOptions('categories', categories);

    if (selected) {
        const selectedInput = document.getElementById(`admin-categories-${selected}`);

        if (selectedInput) {
            selectedInput.checked = true;
        }
    }

    const forms = categories.map(createAdminCategoryForm);
    document.getElementById('admin-category-list').replaceChildren(...forms);
}

async function removeAdminCategory(category) {
    if (Store.busy) {
        return;
    }

    const confirmed = await Store.confirm('Excluir categoria?', category.name, 'Excluir');

    if (!confirmed) {
        return;
    }

    try {
        await Store.run(async () => {
            await AdminAPI.removeCategory(category.id);
            await loadAdminCategories();
        });

        adminStatus.textContent = 'Categoria excluída.';
    } catch (error) {
        adminStatus.textContent = Store.error(error);
    }
}

async function createAdminCategory(event) {
    event.preventDefault();
    const form = event.currentTarget;

    if (Store.busy) {
        return;
    }

    try {
        await Store.run(async () => {
            await AdminAPI.saveCategory(null, {
                name: document.getElementById('admin-category-name').value.trim(),
            });
            await loadAdminCategories();
            form.reset();
        });

        adminStatus.textContent = 'Categoria criada.';
    } catch (error) {
        adminStatus.textContent = Store.error(error);
    }
}

async function changeAdminPage(direction) {
    if (Store.busy) {
        return;
    }

    adminPage += direction;

    try {
        await Store.run(loadAdminProducts);
    } catch (error) {
        adminStatus.textContent = Store.error(error);
    }
}

async function initializeAdmin() {
    try {
        await Store.session;

        if (!Store.user) {
            window.location.replace('login.html?next=admin.html');
            return;
        }

        if (!Validacoes.admin(Store.user)) {
            adminStatus.textContent = 'Somente administradores podem gerenciar produtos.';
            document.getElementById('admin-access').hidden = false;
            return;
        }

        const [platforms, categories, genres] = await Promise.all([
            ProdutoAPI.platforms(),
            ProdutoAPI.categories(),
            ProdutoAPI.genres(),
        ]);

        renderAdminOptions('platforms', platforms);
        renderAdminOptions('categories', categories);
        renderAdminOptions('genres', genres);
        await loadAdminCategories();
        await loadAdminProducts();
        adminStatus.textContent = 'Produtos carregados.';
        adminContent.hidden = false;
        adminFields.disabled = false;
    } catch (error) {
        adminStatus.textContent = Store.error(error);
    }
}

adminForm.addEventListener('submit', saveAdminProduct);

document.getElementById('admin-category-form').addEventListener('submit', createAdminCategory);

for (const id of ['admin-new', 'admin-cancel']) {
    document.getElementById(id).addEventListener('click', () => {
        if (Store.busy) {
            return;
        }

        resetAdminForm();
        adminForm.scrollIntoView({ block: 'start' });
        document.getElementById('admin-name').focus({ preventScroll: true });
    });
}

document.getElementById('admin-prev').addEventListener('click', () => changeAdminPage(-1));
document.getElementById('admin-next').addEventListener('click', () => changeAdminPage(1));

window.addEventListener('store:change', () => {
    if (!adminContent.hidden) {
        adminFields.disabled = Store.busy;
        adminForm.setAttribute('aria-busy', Store.busy ? 'true' : 'false');
        renderAdminProducts();
    }
});

initializeAdmin();
