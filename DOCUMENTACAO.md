# Documentação do TicoGames

Este documento reúne a implementação de frontend, backend, banco e API. O [README](README.md) contém a instalação rápida, os acessos e as contas de demonstração.

## Estrutura e execução

| Caminho | Responsabilidade |
| --- | --- |
| api/app/Http/Controllers/Api | Recebe requisições e delega operações |
| api/app/Http/Requests | Valida os campos aceitos |
| api/app/Services | Regras de negócio, transações e acesso aos dados |
| api/app/Models | Entidades e relacionamentos Eloquent |
| api/app/Http/Resources | Campos e relações das respostas JSON |
| api/database/migrations | Estrutura e evolução do banco |
| api/database/seeders | Carga de demonstração |
| api/routes | Rotas web e da API |
| api/openapi.yaml | Contrato OpenAPI do Swagger |
| front | Oito páginas HTML, CSS, imagens e JavaScript |
| proxy | Nginx de entrada da aplicação |
| compose.yaml | Definição dos quatro serviços da loja |

A aplicação usa Laravel 12, Sanctum por sessão, BCMath e MySQL 8.4. O frontend usa Bootstrap e Axios e é servido diretamente pelo Nginx, sem compilação ou npm.

| Serviço | Função e persistência |
| --- | --- |
| mysql | Banco no volume mysql_data; porta local 127.0.0.1:3306 |
| api | Laravel/FrankenPHP; código montado em /app, dependências em api_vendor, storage em api_storage e uploads em api_uploads |
| frontend | HTML/JS/CSS copiados para a imagem Nginx |
| proxy | Entrada em http://localhost, encaminhando API, Sanctum, imagens e frontend |

O Compose não possui perfil QA, serviços paralelos ou porta 8081. Todas as operações da loja usam os serviços padrão.

## Configuração

O arquivo `.env` da raiz é injetado na API pelo Compose. Não é necessário outro arquivo em api/. Preserve APP_KEY em um ambiente existente.

| Variável | Uso local |
| --- | --- |
| APP_URL | http://localhost |
| FRONTEND_URL | http://localhost |
| SANCTUM_STATEFUL_DOMAINS | localhost |
| DB_CONNECTION / DB_HOST | mysql / mysql |
| API_BASE_URL | Vazio para usar o proxy da mesma origem |
| SESSION_DRIVER / CACHE_STORE | file |
| SESSION_SECURE_COOKIE | false no HTTP local |
| QUEUE_CONNECTION | sync |
| MAIL_MAILER | log |

Sessões/cache ficam em arquivos no volume api_storage, junto com logs, páginas compiladas e arquivos privados. Filas executam na própria requisição e emails são registrados em log. Deixe SESSION_DOMAIN vazio no ambiente HTTP local. Em HTTPS, use cookie seguro.

Após mudar configuração da API, execute config:clear e recrie o serviço api, conforme o README. Alterações no frontend ou em API_BASE_URL exigem reconstruir/recriar frontend.

O Nginx gera assets/js/config.js no início do container usando API_BASE_URL. Outra origem exige CORS com credenciais, FRONTEND_URL, host/porta do Sanctum e cookies correspondentes. Em hospedagem estática sem Docker, ajuste front/assets/js/config.js diretamente.

## Modelo de dados

| Tabela | Dados e relações |
| --- | --- |
| users | Nome, email único, senha com hash e role customer/admin |
| addresses | Endereços de uma conta; CEP e número como textos |
| categories | Categoria principal de cada produto |
| platforms | Plataforma da versão do produto |
| genres | Gêneros opcionais dos produtos |
| products | Categoria/plataforma obrigatórias, nome, descrição, imagem, preço, estoque e atividade |
| genre_product | Relação de vários gêneros por produto |
| carts | Carrinho da conta ou visitante, com user_id nulo para visitante |
| cart_items | Produto/quantidade; combinação carrinho/produto única |
| orders | Dono, status, total, paid_at e cópia shipping_* do endereço |
| order_items | Produto e cópia histórica de nome, preço e quantidade |
| category_product | Arquivo do modelo antigo, sem uso pela aplicação |

Preços/totais usam decimal(10,2) e são expostos como textos com duas casas. Quantidades/estoque são inteiros. Produtos são desativados para preservar referências; classificações em uso e contas com pedidos não podem ser excluídas.

## Autenticação, sessão e permissões

A API usa cookies de sessão do Laravel com Sanctum. O cookie HttpOnly identifica a conta. XSRF-TOKEN fornece o valor decodificado para o header X-XSRF-TOKEN nas escritas; não há Bearer token.

1. Consulte GET /sanctum/csrf-cookie pela origem configurada.
2. Envie POST /api/login com email/password ou POST /api/register com name/email/password/password_confirmation.
3. RequireFrontendSession exige sessão stateful; os FormRequests validam os campos.
4. AuthService valida as credenciais e funde o carrinho visitante antes de concluir o login. No cadastro, criação da conta e fusão pertencem à mesma transação.
5. Após sucesso, a sessão é regenerada e guest_cart_token é removido.
6. GET /api/me consulta a conta; POST /api/logout invalida a sessão e regenera CSRF.

Falha de credenciais não consome o visitante. Falha na fusão preserva os carrinhos e não inicia a nova autenticação; no cadastro, desfaz também a conta. O cadastro público sempre cria customer. Role não pode ser escolhido para promover a conta. Senhas e remember_token não são expostos.

Login/cadastro são limitados a seis tentativas por minuto. Login, cadastro e consulta da conta usam Cache-Control: no-store. Escritas do catálogo e gestão de usuários exigem admin; dados privados são limitados ao proprietário.

No Swagger, prepare primeiro CSRF e depois faça login. A interface envia cookies e X-XSRF-TOKEN. No Thunder Client, mantenha o cookie jar, envie Origin: http://localhost e copie o valor decodificado do cookie XSRF-TOKEN para o header nas escritas.

## Rotas e respostas

Consulte o [Swagger](http://localhost/api/docs) ou [OpenAPI](api/openapi.yaml) para todas as operações, campos e exemplos.

| Rotas | Uso |
| --- | --- |
| /api/register, /api/login, /api/me, /api/logout | Cadastro e sessão |
| /api/user | Edição/exclusão da própria conta |
| /api/users | CRUD administrativo de contas |
| /api/addresses | CRUD dos próprios endereços |
| /api/products | Catálogo e CRUD administrativo de produtos |
| /api/categories, /api/platforms, /api/genres | Classificações públicas; gravação administrativa |
| /api/product-images | Upload administrativo de imagem |
| /api/cart, /api/cart/items | Carrinho visitante ou autenticado |
| /api/cart/merge | Fusão explícita por compatibilidade |
| /api/orders | Criação, listagem e consulta de pedidos |
| /api/orders/{id}/pay, /cancel, /status | Pagamento simulado, cancelamento e transições |

FormRequests limitam os dados usados por validated(). O corpo não escolhe proprietário, perfil administrativo, total ou status inicial. Resources selecionam a saída: objeto/lista em data; produtos paginados também incluem links/meta. Exclusões, limpeza e logout retornam 204 sem corpo.

| HTTP | Significado |
| --- | --- |
| 200 / 201 / 204 | Consulta/alteração, criação ou sucesso sem corpo |
| 401 | Autenticação necessária |
| 403 | Perfil sem permissão ou decisão em pedido alheio |
| 404 | ID inexistente ou consulta privada sem acesso |
| 409 | Estoque, limite do carrinho ou transição em conflito |
| 419 | Sessão/CSRF inválido |
| 422 | Validação, carrinho vazio ou restrição de exclusão |
| 429 | Limite de tentativas de autenticação |

Erros usam message e, para validação de campos, errors. Carrinho vazio pode trazer apenas message. Rotas /api respondem erros em JSON independentemente de Accept.

## Catálogo e produtos

Produtos pertencem a uma categoria e plataforma e podem ter vários gêneros. Escritas usam category_id; respostas mantêm category, category_id e categories=[categoria] por compatibilidade.

Campos de criação: name, description, image_path, platform_id, category_id, price e stock. is_active e genre_ids são opcionais. Nome/imagem têm limite de 255 caracteres, descrição de 10000, preço de 0 a 99999999.99 com até duas casas e estoque de 0 a 4294967295. IDs precisam existir; gêneros não se repetem.

PATCH preserva campos omitidos. genre_ids=[] remove gêneros; omitir preserva. Cadastro/edição de produto e gêneros são transacionais. DELETE desativa; PATCH com is_active=true reativa.

Visitantes/clientes só consultam produtos ativos; admin também recebe inativos. Produto sem estoque continua visível com compra desabilitada.

| Filtro | Uso |
| --- | --- |
| q | Pesquisa no nome |
| category_id / platform_id / genre_id | Classificação combinada |
| sort | id, price_asc, price_desc ou newest |
| page | Página solicitada |
| per_page | 1 a 100, padrão 12 |

O servidor filtra/ordena antes de paginar. Categorias, plataformas e gêneros vêm de consultas próprias. Nome deve ser único em cada classificação; exclusão de classificação com produtos, inclusive inativos, retorna 422. O painel gerencia categorias/produtos; plataformas/gêneros podem ser administrados pela API.

Upload: POST /api/product-images com multipart/form-data e image JPEG/PNG/WebP até 5 MB. O retorno image_path pode ser usado no produto. Arquivos recebem nomes aleatórios em api_uploads e são servidos em /storage/products/{arquivo} com nosniff. URLs externas também são aceitas. Arquivos antigos são mantidos para preservar referências.

## Conta e endereços

GET /api/me consulta a conta. PATCH /api/user altera nome, email e senha; DELETE /api/user encerra a sessão e exclui somente conta sem pedidos. Com pedidos, retorna 422 para preservar histórico.

O dono do endereço vem da sessão; user_id no corpo não transfere propriedade. Endereços de outra conta retornam 404. Campos: recipient_name, postal_code, street, number, complement opcional, neighborhood, city e state com dois caracteres. Atualizações podem ser parciais.

## Carrinho e fusão

O carrinho visitante fica no banco com user_id nulo. A API gera um token aleatório guardado na sessão e persiste somente o hash em carts.session_id. O frontend não escolhe esse identificador. O carrinho da conta permanece após logout.

| Operação | Comportamento |
| --- | --- |
| GET /api/cart | Consulta/prepara carrinho da sessão ou conta |
| POST /api/cart/items | Soma quantity para product_id |
| PATCH /api/cart/items/{id} | Define quantidade absoluta; zero remove |
| DELETE /api/cart/items/{id} | Remove item próprio |
| DELETE /api/cart | Limpa os itens |

AuthService executa a fusão no login/cadastro. A próxima consulta já retorna o carrinho da conta: quantidades são somadas por produto até o estoque disponível, indisponíveis são removidos e o visitante é consumido. Ajustes aparecem em warnings na primeira leitura, depois são retirados da sessão.

POST /api/cart/merge permanece por compatibilidade, sem corpo de itens ou identificador visitante. Repetir não duplica um visitante consumido. O frontend usa apenas GET para ler o resultado da autenticação.

Há transações e bloqueios de usuário/carrinho/produtos. Falhas preservam os carrinhos. O limite é 100 produtos distintos; excedê-lo retorna 409. Subtotais, frete gratuito e total são calculados no backend com BCMath. Carrinhos antigos em localStorage não são importados automaticamente.

## Checkout e pedidos

POST /api/orders recebe somente address_id próprio. Exige carrinho não vazio, produtos ativos e quantidades disponíveis. Carrinho vazio retorna 422; estoque/atividade em conflito retorna 409.

A criação bloqueia os produtos em ordem de ID, calcula o total, copia nomes/preços/endereço, baixa todas as unidades e limpa o carrinho na mesma transação. Falha não cria pedido nem consome os itens. O total respeita decimal(10,2).

Pagamento simulado marca paid/paid_at sem nova baixa. Mudanças posteriores nos cadastros não reescrevem o histórico.

| Estado atual | Próximo estado permitido |
| --- | --- |
| pending_payment | paid ou cancelled, pelo dono ou admin |
| paid | shipped, somente admin |
| shipped | delivered, somente admin |
| delivered / cancelled | Nenhum |

Somente pedidos pendentes podem ser cancelados, inclusive pelo admin. O cancelamento repõe unidades uma vez. POST /cancel e PATCH /status rejeitam cancelar pedido pago com 409, sem mudar paid_at, estoque ou histórico.

Cliente lista/consulta somente pedidos próprios; admin vê todos e pode filtrar por status. Decidir pedido alheio como cliente retorna 403; consultar retorna 404. Frete é gratuito. Não há gateway real, reembolso ou expiração automática.

## Frontend

| Página | Função |
| --- | --- |
| index.html | Catálogo, filtros, ordenação e paginação |
| produto.html?id=... | Detalhes do produto e inclusão no carrinho |
| login.html | Login/cadastro e continuação para páginas privadas |
| carrinho.html | Itens, quantidades, remoção, limpeza e checkout |
| checkout.html | Endereços, escolha e criação de pedido |
| pedidos.html?id=... | Histórico, pagamento/cancelamento pendente e transições administrativas |
| perfil.html | Nome, email e senha da conta atual |
| admin.html | Produtos, categorias, upload e acesso aos pedidos |

Ordem de scripts: bibliotecas, config.js, store.js, main.js e script da página. Perfil/Pedidos aparecem nos menus desktop/móvel dos autenticados; Administração depende de admin. Admin vê “Pedidos”; cliente “Meus pedidos”. A autorização efetiva continua na API.

Store centraliza Axios, validação de respostas, sessão, carrinho e dinheiro. main.js atualiza navegação/minicarrinho e mensagens/confirmações. Store.initialize consulta /api/me e /api/cart; somente 401 significa visitante. Uma falha de carrinho não impede apresentar a conta conectada. A tela encerra o carregamento e oferece nova tentativa.

Após autenticar, Store.acceptSession consulta o carrinho já fundido. Escritas preparam CSRF; busy bloqueia envios simultâneos. Operações não são repetidas automaticamente após resposta perdida. Checkout orienta consultar pedidos antes de repetir uma criação sem resposta.

Textos recebidos usam textContent. Valores monetários textuais são formatados com BigInt. Imagens possuem alternativa local. Produto inativo/sem estoque não oferece compra. Formulários preservam preenchimento em falhas, mostram erros por campo e controlam foco/aria-busy.

Catálogo guarda filtros/página na URL e descarta respostas atrasadas. Detalhes validam o ID antes de consultar. Destinos de continuação no login são limitados a páginas locais conhecidas. Bootstrap, Axios, ícones, fontes e Swagger dependem de CDN.

## Carga de demonstração

DatabaseSeeder executa em transação: UserSeeder, CategorySeeder, PlatformSeeder, GenreSeeder, ProductSeeder, AddressSeeder e StoreScenarioSeeder.

A carga cria um admin, três clientes, três categorias (Jogos, Consoles, Acessórios), três plataformas, quatro gêneros e 30 produtos. Há três versões inativas de Resident Evil 4 e três versões sem estoque de Hogwarts Legacy. Em uma base nova, visitantes consultam 27 produtos ativos.

Produtos são identificados por nome/plataforma; valores padrão só são aplicados na criação. Gêneros são acrescentados sem remover vínculos existentes. Contas preservam nome, senha e perfil. Endereços são identificados por dono/rua/número.

Cenários usam uma conta fictícia com carrinho vazio, preservando compras em andamento. Se todas as três contas estiverem com itens, a carga falha e a transação é desfeita. Hades precisa estar ativo com pelo menos cinco unidades. Os Services criam pedidos nos cinco estados e carrinhos visitante/cliente populados.

O marcador demo-guest-cart, guardado como hash, impede recriar pedidos ao repetir a carga. Não remova esse marcador para reexecutar. A carga da apresentação em 08/11/2026 preservou os registros antigos e passou de sete para 30 produtos, duas para três categorias e um para três clientes; o pedido pago anterior permaneceu entre os seis pedidos finais.

## Migrations, backups e manutenção

Use migrate para aplicar somente arquivos pendentes e db:seed para acrescentar exemplos. Faça backup primeiro; não use migrate:fresh, refresh ou down -v em uma base que precise preservar.

A migration 2026_11_07_000001_complete_store_workflow.php copia a categoria antiga para products.category_id, permite visitante no carrinho, acrescenta estados/paid_at e reserva estoque de pendentes antigos. Antes de alterar, exige uma categoria por produto e estoque suficiente para as reservas.

category_product permanece como arquivo. Pagamentos antigos sem data precisa mantêm paid_at nulo. Reverter essa evolução exige restaurar backup, pois desfazer reservas automaticamente pode interferir em compras posteriores.

Backups locais anteriores à carga ficam em .local-backups/, ignorada pelo Git. O backup SQL anterior à demonstração é pre-demo-20261108.sql. Preserve também os volumes api_storage e api_uploads; reconstruir imagens mantém os volumes, mas down -v os apaga.

O storage ativo é /app/storage no volume api_storage. /app/storage/app/public continua montado separadamente em api_uploads, preservando as imagens públicas existentes. Os arquivos gerados não são mais gravados na pasta local api/storage, que mantém somente a estrutura inicial. A imagem de desenvolvimento inclui os diretórios necessários para inicializar um volume vazio.

Na migração de 08/11/2026, sessões, cache, logs e arquivos privados foram copiados com a API parada e conferidos por hash antes de reiniciar. A antiga storage/ da raiz, sem uso pela API, foi arquivada e removida. Os arquivos TAR anteriores à mudança ficam em .local-backups/.

```powershell
docker compose config --quiet
docker compose ps
docker compose exec -T api php artisan migrate:status
docker compose exec -T api php artisan db:show
docker compose logs --tail 50 api
```

## Conferência manual

O projeto não mantém arquivos de testes automatizados, PHPUnit ou ambiente QA. As verificações de comportamento devem ser feitas pela interface/Swagger nos serviços padrão. Cadastros, alterações e compras realizados nesse ambiente modificam os dados da loja.

Para conferir o fluxo: adicione um item como visitante, entre na conta, confirme o carrinho, escolha endereço, crie um pedido e confirme o pagamento simulado. Confira também que somente pendentes oferecem cancelamento e que o admin avança pago para enviado/entregue.
