# TicoGames

Loja de demonstração com Laravel 12, MySQL 8.4 e frontend HTML, JavaScript e Bootstrap. Pagamento somente simulado.

## Executar no Docker

Na raiz, copie `.env.example` para `.env` somente se o arquivo ainda não existir. Preserve a chave e as credenciais de um ambiente existente.

```powershell
docker compose build
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose run --rm --no-deps api php artisan key:generate --show
# Em uma instalação nova, salve a chave gerada em APP_KEY no .env.
docker compose up -d --wait
docker compose exec -T api php artisan migrate --force
docker compose exec -T api php artisan db:seed --force
```

Em uma instalação já configurada, pule a geração da chave. Acesse [loja](http://localhost), [Swagger](http://localhost/api/docs), [OpenAPI](http://localhost/api/openapi.yaml) e [saúde da API](http://localhost/up). Consulte [ambiente e documentação](docs/README.md).

## Contas e carga de demonstração

| Perfil | E-mail | Senha inicial |
| --- | --- | --- |
| Administrador | admin@ticogames.test | password |
| Cliente | customer@ticogames.test | password |
| Cliente | ana@ticogames.test | password |
| Cliente | bruno@ticogames.test | password |

A carga inclui 30 produtos, três categorias (Jogos, Consoles, Acessórios), três plataformas, quatro gêneros, três clientes e um administrador. Há produtos inativos e sem estoque, carrinhos visitante/cliente e pedidos nos cinco estados. Contas preexistentes mantêm nome, senha e perfil; produtos mantêm preço, estoque e atividade.

Em **08/11/2026**, a carga foi aplicada ao MySQL principal: 30 produtos, três categorias, três clientes e seis pedidos, incluindo o pedido pago anterior. Todos os registros anteriores foram comparados e preservados. A segunda execução não alterou os dados. O backup anterior está em `.local-backups/pre-demo-20261108.sql`, ignorado pelo Git. Veja [seeders](docs/seeders.md).

## Autenticação e fusão do carrinho

A API usa cookies de sessão com Sanctum. Antes de gravar, consulte `GET /sanctum/csrf-cookie` e envie os cookies e `X-XSRF-TOKEN`. Não há Bearer token.

O carrinho visitante fica no banco com `user_id` nulo. A API guarda um token aleatório na sessão HttpOnly e somente seu hash em `carts.session_id`. O cliente não escolhe o identificador.

**Login e cadastro executam a fusão no AuthService.** Após autenticar, `GET /api/cart` já retorna o carrinho da conta, sem chamada adicional de fusão. Somam-se quantidades por produto até o estoque disponível; produtos inativos/sem estoque são removidos. O visitante é consumido e seu token é removido. Ajustes aparecem em `warnings` na primeira consulta ao carrinho. O cadastro e a fusão são transacionais; falhas preservam os carrinhos e não iniciam a nova autenticação.

`POST /api/cart/merge` permanece por compatibilidade, sem receber itens ou identificadores no corpo. Repetir não duplica o visitante consumido. O frontend apenas consulta `GET /api/cart`. O limite é 100 produtos distintos; excedê-lo gera 409.

`PATCH /api/cart/items/{id}` define a quantidade absoluta; zero remove o item. Subtotais, frete e total são calculados pelo backend com BCMath. Frete gratuito em todos os pedidos. Carrinhos antigos em localStorage não são importados automaticamente.

## Checkout e pedidos

Criar pedido exige endereço próprio e carrinho válido. **Carrinho vazio retorna 422**. Produto inativo ou estoque insuficiente retorna 409. A criação copia nomes, preços e endereço, baixa o estoque e limpa o carrinho na mesma transação. Pagar registra `paid` e `paid_at`, sem baixar estoque novamente.

| Estado atual | Próxima ação permitida |
| --- | --- |
| `pending_payment` | Pagar ou cancelar, pelo dono ou administrador |
| `paid` | Marcar enviado, somente administrador |
| `shipped` | Marcar entregue, somente administrador |
| `delivered` / `cancelled` | Nenhuma |

**Somente pedidos pendentes podem ser cancelados, inclusive pelo administrador.** Cancelar repõe o estoque uma única vez. Transições inválidas retornam 409 sem alterar estoque ou histórico. Decidir pedido alheio como cliente retorna 403; consultar pedido alheio retorna 404.

## Catálogo, frontend e imagens

Cada produto pertence a uma categoria e uma plataforma e pode ter vários gêneros. Escritas recebem `category_id`; respostas mantêm `category`, `category_id` e `categories: [categoria]`. Pesquisa, filtros, ordenação e paginação ocorrem no servidor. `sort`: `id`, `price_asc`, `price_desc`, `newest`; `per_page`: 1 a 100, padrão 12.

As oito páginas carregam `config.js` antes de `store.js`, compartilham links desktop/móvel para Perfil e Pedidos e exibem Administração somente para admin. Visitantes são encaminhados ao login nas páginas privadas. O painel administra produtos, categorias e pedidos; o perfil edita os próprios dados.

`API_BASE_URL` vazio usa o proxy da mesma origem. Ao mudar essa variável, recrie o frontend: `docker compose up -d --build --no-deps --wait frontend`. Para outra origem, configure também CORS, `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS` e cookies. Em hospedagem estática, ajuste `front/assets/js/config.js`.

Upload administrativo: `POST /api/product-images`, JPEG/PNG/WebP até 5 MB. Arquivos recebem nomes aleatórios no volume `api_uploads`; a API os serve em `/storage/products/{arquivo}`. Faça backup do banco e desse volume. `docker compose down -v` apaga os volumes.

## Atualizar uma base existente

Faça backup antes das migrations e da carga. Use `migrate` e `db:seed`, sem recriar tabelas. A migration de evolução copia a categoria antiga e reserva o estoque de pedidos pendentes antigos; interrompe antes de alterar os dados se faltar categoria única ou estoque. `category_product` permanece como arquivo sem uso pela aplicação. Pagamentos antigos sem data precisa mantêm `paid_at` nulo. A reversão dessa migration exige restaurar o backup.

## Verificar

```powershell
node --test front/tests/*.test.cjs
docker compose exec -T api php artisan test
docker compose --profile qa up -d --build qa-api qa-frontend qa-proxy
# QA separado: http://localhost:8081
docker compose --profile qa stop qa-proxy qa-frontend qa-api
```

PHPUnit usa SQLite em memória e bloqueia a execução contra o banco principal. O perfil QA usa SQLite separado, rede própria e armazenamento temporário. Não use `down` para encerrar apenas QA. Resultados e limites estão em [verificação](docs/frontend-verification.md) e [correções](docs/correcoes-projeto.md).

Commit, envio ao GitHub e hospedagem foram adiados por orientação do usuário.
