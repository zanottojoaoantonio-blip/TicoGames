const catalogParams = new URLSearchParams(window.location.search);
const catalogGrid = document.getElementById('product-grid');
const catalogStatus = document.getElementById('catalog-status');
const catalogFilters = document.getElementById('catalog-filters');
const catalogSearch = document.getElementById('site-search');
const catalogCategory = document.getElementById('filter-category');
const catalogPlatform = document.getElementById('filter-platform');
const catalogGenre = document.getElementById('filter-genre');
const catalogSort = document.getElementById('catalog-sort');

let catalogPage = Number(catalogParams.get('page')) || 1;
let catalogSequence = 0;
let catalogTimer;

function createCatalogCard(product) {
    Validacoes.product(product);
    const card = document.getElementById('product-card-template').content.cloneNode(true);
    card.querySelector('.product-title a').textContent = product.name;

    for (const link of card.querySelectorAll('[data-product-link]')) {
        link.href = `produto.html?id=${product.id}`;
        link.setAttribute('aria-label', `Ver ${product.name} para ${product.platform.name}`);
    }

    card.querySelector('.product-category').textContent = product.category.name;
    card.querySelector('.platform-name').textContent = product.platform.name;
    card.querySelector('.product-price strong').textContent = Store.money(Validacoes.cents(product.price))
        .replace('R$ ', '');

    const image = card.querySelector('img');
    image.alt = product.name;
    image.addEventListener('error', () => {
        image.src = 'assets/images/product-placeholder.svg';
    }, { once: true });
    image.src = product.image_path;

    const available = product.is_active && product.stock > 0;
    card.querySelector('.stock-tag').textContent = available ? 'Em estoque' : 'Indisponível';

    const button = card.querySelector('button');
    button.disabled = !available;
    button.setAttribute('data-add-product', product.id);
    button.setAttribute('aria-label', `Comprar ${product.name} para ${product.platform.name}`);
    return card;
}

async function loadCatalogProducts() {
    const sequence = ++catalogSequence;
    const params = new URLSearchParams({ page: catalogPage, sort: catalogSort.value || 'id' });
    const filters = {
        q: catalogSearch.value.trim(),
        category_id: catalogCategory.value,
        platform_id: catalogPlatform.value,
        genre_id: catalogGenre.value,
    };

    for (const [key, value] of Object.entries(filters)) {
        if (value) {
            params.set(key, value);
        }
    }

    catalogGrid.setAttribute('aria-busy', 'true');
    catalogStatus.textContent = 'Carregando produtos...';

    try {
        const { data, meta } = await ProdutoAPI.list(params);

        if (sequence !== catalogSequence) {
            return;
        }

        Validacoes.catalogPage(data, meta);

        const cards = data.map(createCatalogCard);
        catalogGrid.replaceChildren(...cards);
        document.getElementById('product-count').textContent = `${meta.total} produtos`;
        document.getElementById('catalog-page').textContent = `Página ${meta.current_page} de ${meta.last_page}`;
        document.getElementById('catalog-prev').disabled = meta.current_page <= 1;
        document.getElementById('catalog-next').disabled = meta.current_page >= meta.last_page;

        const categoryName = catalogCategory.selectedOptions[0]?.textContent || '';
        document.getElementById('catalog-title').textContent = categoryName || 'Catálogo';
        catalogStatus.textContent = data.length ? '' : 'Nenhum produto encontrado para estes filtros.';

        const url = new URL(window.location.href);
        url.search = params.toString();
        url.hash = 'catalogo';
        window.history.replaceState(null, '', url);

        for (const link of document.querySelectorAll('[data-category]')) {
            link.setAttribute('aria-current', link.dataset.category === categoryName ? 'page' : 'false');
        }
    } catch (error) {
        if (sequence === catalogSequence) {
            catalogGrid.replaceChildren();
            catalogStatus.textContent = Store.error(error);
            document.getElementById('catalog-prev').disabled = true;
            document.getElementById('catalog-next').disabled = true;
        }
    } finally {
        if (sequence === catalogSequence) {
            catalogGrid.setAttribute('aria-busy', 'false');
        }
    }
}

function applyCatalogFilters() {
    catalogPage = 1;
    loadCatalogProducts();
}

function registerCatalogEvents() {
    for (const select of [catalogCategory, catalogPlatform, catalogGenre, catalogSort]) {
        select.addEventListener('change', applyCatalogFilters);
    }

    catalogSearch.addEventListener('input', () => {
        clearTimeout(catalogTimer);
        catalogTimer = setTimeout(applyCatalogFilters, 250);
    });

    document.getElementById('site-search-form').addEventListener('submit', event => {
        event.preventDefault();
        clearTimeout(catalogTimer);
        applyCatalogFilters();
    });

    document.getElementById('clear-filters').addEventListener('click', () => {
        catalogSearch.value = '';

        for (const select of [catalogCategory, catalogPlatform, catalogGenre]) {
            select.value = '';
        }

        catalogSort.value = 'id';
        applyCatalogFilters();
    });

    document.getElementById('catalog-prev').addEventListener('click', () => {
        catalogPage -= 1;
        loadCatalogProducts();
    });

    document.getElementById('catalog-next').addEventListener('click', () => {
        catalogPage += 1;
        loadCatalogProducts();
    });
}

async function initializeCatalog() {
    try {
        const values = await Promise.all([
            ProdutoAPI.categories(),
            ProdutoAPI.platforms(),
            ProdutoAPI.genres(),
        ]);
        const selects = [catalogCategory, catalogPlatform, catalogGenre];
        const validate = [Validacoes.categories, Validacoes.platforms, Validacoes.genres];

        for (const [index, select] of selects.entries()) {
            validate[index](values[index]);

            for (const item of values[index]) {
                select.append(new Option(item.name, item.id));
            }
        }

        const categoryByName = values[0].find(item => item.name === catalogParams.get('category'));
        catalogSearch.value = catalogParams.get('q') || '';
        catalogCategory.value = catalogParams.get('category_id') || categoryByName?.id || '';
        catalogPlatform.value = catalogParams.get('platform_id') || catalogParams.get('platform') || '';
        catalogGenre.value = catalogParams.get('genre_id') || catalogParams.get('genre') || '';
        catalogSort.value = catalogParams.get('sort') || 'id';
        catalogFilters.disabled = false;

        await loadCatalogProducts();
        registerCatalogEvents();
    } catch (error) {
        catalogStatus.textContent = Store.error(error);
    }
}

initializeCatalog();
