# Frontend TicoGames

HTML, CSS e JavaScript com Bootstrap e Axios. Nginx serve os arquivos diretamente, sem npm ou compilação. O pagamento é simulado.

## Abrir e atualizar

Prepare o ambiente pelo [guia](../docs/README.md) e abra [http://localhost](http://localhost). Após editar arquivos:

```powershell
docker compose up -d --build --no-deps --wait frontend
```

Não abra HTML como arquivo local: cookies e chamadas relativas dependem da origem HTTP. A reconstrução não altera o banco.

## Páginas

| Página | Função |
| --- | --- |
| index.html | Catálogo com pesquisa, categorias dinâmicas, plataforma, gênero, ordenação e paginação |
| produto.html?id=... | Detalhes e inclusão de produto disponível |
| login.html | Login e cadastro; fusão automática pelo AuthService |
| carrinho.html | Carrinho visitante/conta, quantidades, remoção, limpeza e checkout |
| checkout.html | Endereços próprios, escolha e criação de pedido |
| pedidos.html?id=... | Lista/detalhes; pagar/cancelar pendentes; admin envia/entrega |
| perfil.html | Edição de nome, email e senha próprios |
| admin.html | CRUD de produtos/categorias, upload e acesso a pedidos |

As oito páginas compartilham navegação desktop/móvel, Perfil/Pedidos para autenticados, Administração para admin, minicarrinho e rodapé. Admin vê “Pedidos”; cliente vê “Meus pedidos”. A autorização efetiva é da API.

## Scripts e configuração

Ordem: bibliotecas, `config.js`, `store.js`, `main.js` e script da página. `config.js` precisa vir antes do Store em todas as páginas, inclusive carrinho.

`API_BASE_URL` vazio usa a mesma origem. O container gera `assets/js/config.js` no início; recrie-o após mudar a variável. Outra origem exige CORS/cookies/Sanctum correspondentes na API. Em hospedagem estática, ajuste o arquivo diretamente.

Store consulta sessão e carrinho por GET. Após login/cadastro, apenas lê o carrinho já fundido pelo backend e apresenta warnings de ajustes. Nenhum fluxo depende de POST /api/cart/merge ou carrinho em localStorage. Escritas preparam CSRF; não há repetição automática de operações após resposta perdida.

Totais decimais são recebidos do servidor; BigInt permite formatá-los sem float. Textos recebidos usam textContent. Erros preservam formulários e permitem recuperação. Produto inativo/sem estoque não oferece compra.

## Verificar

```powershell
node --test front/tests/*.test.cjs
```

Os testes cobrem scripts/configuração/links de todas as páginas, erros, formulários, chamadas e ausência de cancelamento de pedido pago como admin. Os recursos de Bootstrap, Axios, ícones e fontes dependem de CDN.

Regras: [README](../README.md). Resultados e limites: [verificação](../docs/frontend-verification.md). Explicações: [integração](../docs/frontend-integration.md).
