// Este arquivo é carregado somente na página de detalhes.
async function loadProduct() {
  const main = document.getElementById('main-content');
  const details = document.getElementById('product-details');
  const status = document.getElementById('product-status');
  const id = new URLSearchParams(window.location.search).get('id');

  try {
    // A URL é editável: não consultar IDs ausentes, fracionários ou fora do intervalo seguro.
    if (!id || id.trim() !== id || !/^[1-9]\d*$/.test(id) || !Number.isSafeInteger(Number(id))) {
      status.textContent = 'Selecione um produto válido no catálogo.';
      return;
    }

    const response = await axios.get(`/api/products/${id}`, {
      headers: { Accept: 'application/json' },
    });
    // O primeiro data pertence ao Axios; o segundo é o objeto devolvido pelo Laravel.
    const product = response.data.data;

    // Conferir os campos usados antes de apresentar uma resposta incompleta como sucesso.
    if (!product || product.id !== Number(id)
      || typeof product.name !== 'string' || typeof product.description !== 'string'
      || typeof product.image_path !== 'string' || typeof product.price !== 'string'
      || !/^\d{1,8}\.\d{2}$/.test(product.price)
      || !Number.isInteger(product.stock) || product.stock < 0
      || typeof product.is_active !== 'boolean' || typeof product.platform?.name !== 'string'
      || ![product.categories, product.genres].every(items => Array.isArray(items)
        && items.every(item => typeof item?.name === 'string'))) {
      throw new Error('A API não retornou os dados esperados do produto.');
    }

    const priceFormatter = new Intl.NumberFormat('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
    // Os textos recebidos não são interpretados como HTML.
    document.getElementById('product-name').textContent = product.name;
    document.getElementById('product-breadcrumb').textContent = product.name;
    document.getElementById('product-description').textContent = product.description;
    document.getElementById('product-price').textContent = priceFormatter.format(product.price);
    document.getElementById('product-stock').textContent = product.stock;
    document.getElementById('product-platform').textContent = product.platform.name;
    document.getElementById('product-categories').textContent = product.categories.map(category => category.name).join(' · ');
    document.getElementById('product-genres').textContent = product.genres.map(genre => genre.name).join(' · ') || 'Não informado';

    const image = document.getElementById('product-image');
    image.alt = product.name;
    image.addEventListener('error', () => {
      image.src = 'assets/images/product-placeholder.svg';
    }, { once: true });
    image.src = product.image_path;

    const available = product.is_active && product.stock > 0;
    document.getElementById('product-availability').textContent = available ? 'Em estoque' : 'Indisponível';
    const quantity = document.getElementById('product-quantity');
    quantity.max = product.stock;
    quantity.disabled = !available;
    document.getElementById('product-buy').disabled = !available;
    document.getElementById('product-buy').setAttribute('data-add-product', product.id);

    document.title = `${product.name} | TicoGames`;
    status.textContent = '';
    details.hidden = false;
  } catch (error) {
    console.error('Não foi possível carregar o produto:', error);
    status.textContent = error.response?.status === 404
      ? 'Produto não encontrado ou indisponível.'
      : 'Não foi possível carregar o produto. Atualize a página para tentar novamente.';
  } finally {
    main.setAttribute('aria-busy', 'false');
  }
}

loadProduct();
