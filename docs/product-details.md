# Detalhes do produto

produto.html lê id da URL e product.js consulta GET /api/products/{id}. O script rejeita ID ausente, fracionário, negativo ou fora do intervalo seguro antes da chamada.

## Percurso

1. URLSearchParams lê o ID e o frontend valida sua forma.
2. Axios consulta a API pela origem de config.js.
3. ProductController delega a ProductService, carregando categoria, plataforma e gêneros.
4. Visitantes/clientes não acessam produto inativo; ausência retorna 404.
5. ProductResource retorna o produto em data e o script confere os campos/ID usados na tela.

Nome, descrição e relações usam texto, sem interpretar HTML recebido. Preço decimal é formatado em centavos. Uma imagem local substitui a original em falha.

## Comprar

A quantidade precisa ser inteira e positiva. Produto inativo ou sem estoque mantém os controles desabilitados. A inclusão chama Store.add, que prepara CSRF e envia product_id/quantity à API. O backend decide se a quantidade ainda está disponível.

O carrinho é persistido no servidor para visitante ou conta; a tela e o minicarrinho mostram a resposta confirmada. Login/cadastro incorporam os itens automaticamente pelo AuthService.

Falhas de rede, resposta inválida ou 404 mantêm detalhes/compra indisponíveis e mostram uma mensagem. Não há compra ilustrativa local.

Testes: `node --test front/tests/product.test.cjs`. Para o fluxo completo, consulte [integração](frontend-integration.md).
