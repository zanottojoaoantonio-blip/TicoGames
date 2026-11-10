const productMain = document.getElementById('main-content');
const productDetails = document.getElementById('product-details');
const productStatus = document.getElementById('product-status');

function renderProductDetails(product) {
    document.getElementById('product-name').textContent = product.name;
    document.getElementById('product-breadcrumb').textContent = product.name;
    document.getElementById('product-description').textContent = product.description;
    document.getElementById('product-price').textContent = Store.money(Validacoes.cents(product.price))
        .replace('R$ ', '');
    document.getElementById('product-stock').textContent = product.stock;
    document.getElementById('product-platform').textContent = product.platform.name;
    document.getElementById('product-categories').textContent = product.categories
        .map(category => category.name)
        .join(' · ');
    document.getElementById('product-genres').textContent = product.genres
        .map(genre => genre.name)
        .join(' · ') || 'Não informado';

    const image = document.getElementById('product-image');
    image.alt = product.name;
    image.addEventListener('error', () => {
        image.src = 'assets/images/product-placeholder.svg';
    }, { once: true });
    image.src = product.image_path;

    const available = product.is_active && product.stock > 0;
    document.getElementById('product-availability').textContent = available
        ? 'Em estoque'
        : 'Indisponível';

    const quantity = document.getElementById('product-quantity');
    quantity.max = product.stock;
    quantity.disabled = !available;

    const buyButton = document.getElementById('product-buy');
    buyButton.disabled = !available;
    buyButton.setAttribute('data-add-product', product.id);

    document.title = `${product.name} | TicoGames`;
    productStatus.textContent = '';
    productDetails.hidden = false;
}

async function initializeProduct() {
    const id = new URLSearchParams(window.location.search).get('id');

    try {
        // A URL é editável: conferir o ID antes de consultar a API.
        if (!Validacoes.urlId(id)) {
            productStatus.textContent = 'Selecione um produto válido no catálogo.';
            return;
        }

        const product = await ProdutoAPI.get(id);
        Validacoes.productDetails(product, Number(id));
        renderProductDetails(product);
    } catch (error) {
        console.error('Não foi possível carregar o produto:', error);
        productStatus.textContent = error.response?.status === 404
            ? 'Produto não encontrado ou indisponível.'
            : 'Não foi possível carregar o produto. Atualize a página para tentar novamente.';
    } finally {
        productMain.setAttribute('aria-busy', 'false');
    }
}

initializeProduct();
