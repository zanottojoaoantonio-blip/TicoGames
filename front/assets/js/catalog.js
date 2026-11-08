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

function catalogCard(product) {
  Store.product(product);
  const card = document.getElementById('product-card-template').content.cloneNode(true);
  card.querySelector('.product-title a').textContent = product.name;
  for (const link of card.querySelectorAll('[data-product-link]')) {
    link.href = `produto.html?id=${product.id}`;
    link.setAttribute('aria-label', `Ver ${product.name} para ${product.platform.name}`);
  }
  card.querySelector('.product-category').textContent = product.category.name;
  card.querySelector('.platform-name').textContent = product.platform.name;
  card.querySelector('.product-price strong').textContent = Store.money(Store.cents(product.price)).replace('R$ ', '');
  const image = card.querySelector('img'); image.alt = product.name;
  image.addEventListener('error', () => { image.src = 'assets/images/product-placeholder.svg'; }, { once: true });
  image.src = product.image_path;
  const available = product.is_active && product.stock > 0;
  card.querySelector('.stock-tag').textContent = available ? 'Em estoque' : 'Indisponível';
  const button = card.querySelector('button'); button.disabled = !available;
  button.setAttribute('data-add-product', product.id);
  button.setAttribute('aria-label', `Comprar ${product.name} para ${product.platform.name}`);
  return card;
}

async function loadProducts() {
  const sequence = ++catalogSequence;
  const params = new URLSearchParams({ page: catalogPage, sort: catalogSort.value || 'id' });
  for (const [key, value] of [['q', catalogSearch.value.trim()], ['category_id', catalogCategory.value], ['platform_id', catalogPlatform.value], ['genre_id', catalogGenre.value]]) {
    if (value) params.set(key, value);
  }
  catalogGrid.setAttribute('aria-busy', 'true');
  catalogStatus.textContent = 'Carregando produtos...';
  try {
    const { data, meta } = await Store.request('get', `/api/products?${params}`, undefined, true);
    if (sequence !== catalogSequence) return;
    if (!Array.isArray(data) || !Number.isInteger(meta?.last_page)) throw new Error('Página inválida da API.');
    const cards = data.map(catalogCard);
    catalogGrid.replaceChildren(...cards);
    document.getElementById('product-count').textContent = `${meta.total} produtos`;
    document.getElementById('catalog-page').textContent = `Página ${meta.current_page} de ${meta.last_page}`;
    document.getElementById('catalog-prev').disabled = meta.current_page <= 1;
    document.getElementById('catalog-next').disabled = meta.current_page >= meta.last_page;
    document.getElementById('catalog-title').textContent = catalogCategory.selectedOptions[0]?.textContent || 'Catálogo';
    catalogStatus.textContent = data.length ? '' : 'Nenhum produto encontrado para estes filtros.';
    const url = new URL(window.location.href); url.search = params.toString(); url.hash = 'catalogo';
    window.history.replaceState(null, '', url);
    for (const link of document.querySelectorAll('[data-category]')) {
      link.setAttribute('aria-current', link.dataset.category === (catalogCategory.selectedOptions[0]?.textContent || '') ? 'page' : 'false');
    }
  } catch (error) {
    if (sequence === catalogSequence) {
      catalogGrid.replaceChildren(); catalogStatus.textContent = Store.error(error);
      document.getElementById('catalog-prev').disabled = true; document.getElementById('catalog-next').disabled = true;
    }
  } finally { if (sequence === catalogSequence) catalogGrid.setAttribute('aria-busy', 'false'); }
}

(async () => {
  try {
    const values = await Promise.all(['categories', 'platforms', 'genres'].map(name => Store.request('get', `/api/${name}`)));
    for (const [index, select] of [catalogCategory, catalogPlatform, catalogGenre].entries()) {
      if (!Array.isArray(values[index])) throw new Error('Filtros inválidos.');
      for (const item of values[index]) select.append(new Option(item.name, item.id));
    }
    catalogSearch.value = catalogParams.get('q') || '';
    catalogCategory.value = catalogParams.get('category_id') || values[0].find(item => item.name === catalogParams.get('category'))?.id || '';
    catalogPlatform.value = catalogParams.get('platform_id') || catalogParams.get('platform') || '';
    catalogGenre.value = catalogParams.get('genre_id') || catalogParams.get('genre') || '';
    catalogSort.value = catalogParams.get('sort') || 'id';
    catalogFilters.disabled = false;
    await loadProducts();
    const changed = () => { catalogPage = 1; loadProducts(); };
    for (const select of [catalogCategory, catalogPlatform, catalogGenre, catalogSort]) select.addEventListener('change', changed);
    catalogSearch.addEventListener('input', () => { clearTimeout(catalogTimer); catalogTimer = setTimeout(changed, 250); });
    document.getElementById('site-search-form').addEventListener('submit', event => { event.preventDefault(); clearTimeout(catalogTimer); changed(); });
    document.getElementById('clear-filters').addEventListener('click', () => { catalogSearch.value = ''; for (const select of [catalogCategory, catalogPlatform, catalogGenre]) select.value = ''; catalogSort.value = 'id'; changed(); });
    for (const [id, direction] of [['catalog-prev', -1], ['catalog-next', 1]]) document.getElementById(id).addEventListener('click', () => { catalogPage += direction; loadProducts(); });
  } catch (error) { catalogStatus.textContent = Store.error(error); }
})();
