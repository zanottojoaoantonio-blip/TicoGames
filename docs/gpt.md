# TicoGames — contexto para continuar o projeto

Última atualização: 29/10/2026.

## Leia isto primeiro

Este arquivo registra o contexto para continuar em outro computador ou conversa. Leia também `docs/data-model.md`, `docs/migrations.md`, `docs/seeders.md`, `docs/crud-catalog.md`, `docs/crud-products.md` e `docs/README.md`. Confira os arquivos atuais antes de alterar código. Os caminhos são relativos à raiz.

**As etapas 1 a 5 estão concluídas. Na etapa 6, os quatro CRUDs do catálogo estão concluídos: produtos, categorias, plataformas e gêneros, com 20 rotas em `/api`. A suíte passou com 20 testes e 336 asserções. O usuário autorizou implementar os CRUDs juntos e explicar um deles por completo, revogando a preferência anterior de um por vez. O percurso de todas as requisições de produtos está em `docs/crud-products.md`. Não iniciar outra etapa sem “próximo”. Os fluxos de contas, endereços, carrinho e pedidos ainda não estão implementados.**

O usuário pediu: “pode fazer todos os crud e me explique os depois pode ser somente 1 mas todos as requisições e suas respectivas respostas desde onde ela inicia até onde termina”. Foram completados os quatro CRUDs do catálogo definidos nesta conversa e explicado produtos do início ao fim. Ele usa Thunder Client; a imagem anterior tinha `www.localhost` e um header Accept incorreto. Orientar `http://localhost:8000/api/products`, sem `www`, e `Accept: application/json`. Não alterar o backend para compensar erros de preenchimento.

O usuário detalhou o fluxo visual do carrinho, mas esclareceu em seguida: “Não, vamos seguir os passos ja delimitados”. Esse fluxo fica planejado para a etapa 9; não implementar o frontend agora. A preparação iniciada não criou telas. O usuário pediu manter `front-end/package-lock.json`; o arquivo foi restaurado e conferido com `package.json`.

Ao concluir uma etapa, atualize este arquivo com o que mudou, o que foi verificado e qual é o próximo passo. Registre limitações sem apresentar funcionalidades planejadas como já implementadas.

## Contexto do estudante e forma de trabalhar

- O usuário se descreve como estudante intermediário e está fazendo o projeto final do curso.
- O prazo informado anteriormente foi de duas semanas para fazer, entender e explicar o projeto para uma banca; o prazo não foi reconfirmado nesta revisão.
- Priorize soluções simples e fáceis de explicar. Evite ferramentas, abstrações e funcionalidades extras sem necessidade.
- Faça somente uma etapa por vez e espere o usuário dizer “próximo”.
- Na etapa 3, o usuário pediu um Model por vez e depois autorizou `Order` e `OrderItem` juntos. Na etapa 6, começou pedindo um CRUD por vez, mas alterou a preferência ao autorizar os CRUDs do catálogo juntos e pedir a explicação completa de um exemplo, com todas as requisições e respostas. Continua valendo uma etapa por vez.
- Nas migrations, o usuário pediu explicar bem os conceitos novos e executar diretamente os casos que repetem a mesma estrutura. O guia está em `docs/migrations.md`.
- O usuário reforçou que toda a configuração de autenticação será feita somente na etapa 8, com Sanctum. Na etapa 3, focar em campos, tipos e relacionamentos para fazer os dados e o sistema funcionarem. Registro, login, sessão, regras de acesso e verificações específicas de segurança ficam para a etapa 8; não ampliar a revisão dos Models com esse trabalho. Os recursos padrão do Laravel já presentes no `User` podem permanecer.
- Usar somente seeders para os dados iniciais e de demonstração, sem factories, a pedido do usuário. Todos os Models estão sem `HasFactory`, e o `User` está sem a referência à `UserFactory`. A factory padrão ainda existe como arquivo sem uso. Os seeders da etapa 5 usam os Models diretamente; o `DatabaseSeeder` não chama factories.
- Explique o conceito antes de apresentar ou escrever código.
- Comente cada parte do código para ajudar no aprendizado.
- Explique em português; use nomes de tabelas, classes, métodos e campos em inglês.
- Quando houver alternativas relevantes, apresente as opções e justifique a escolha.
- Termine cada etapa com um checklist do que testar. Diferencie verificações executadas de testes ainda pendentes.

## Projeto e tecnologias

E-commerce de jogos físicos e consoles chamado **TicoGames**.

- Backend: Laravel 12; a instalação verificada está na versão **12.70.2**.
- Banco: MySQL 8.4.
- Ambiente: Docker e Docker Compose.
- Frontend: Vue, com Vite, na pasta `front-end/`.
- Autenticação planejada: Laravel Sanctum, na etapa 8.
- Ambiente de origem: Windows com PowerShell. Os caminhos locais e os processos não são transferidos só por copiar este arquivo.
- A revisão das etapas 1 a 4 ocorreu também em Linux com Bash, na pasta `/home/joaoantonio/TicoGames`. A etapa 5 foi executada no Windows com PowerShell, em `C:\Users\Pichau\TicoGames`. `docs/README.md` tem instruções para Linux e Windows.

## Etapas combinadas

| Etapa | Conteúdo | Estado |
| --- | --- | --- |
| 1 | Modelagem de dados | Revisada e concluída em 28/10/2026; registrada em `docs/data-model.md`. |
| 2 | Inicializar projeto Laravel | Revisada e concluída em 28/10/2026 na pasta atual. |
| 3 | Models | Concluída: 10 Models revisados, comentados e com sintaxe PHP verificada. |
| 4 | Migrations | Concluída: 12 novas, 15 aplicadas no total e verificadas no MySQL. |
| 5 | Seeders | Concluída: carga fictícia sem factories, repetição verificada e testes aprovados. |
| 6 | CRUD básico: Controllers e Services | Quatro CRUDs do catálogo concluídos; fluxos de contas, endereços, carrinho e pedidos ainda pendentes. |
| 7 | CRUD completo: Resources e FormRequests | Pendente. |
| 8 | Registro, login e sessão com Laravel Sanctum | Pendente. |
| 9 | Frontend: login/registro, catálogo, administração, carrinho, pagamento simulado e status | Pendente. |

## Regras de negócio definidas

- Vender jogos físicos e consoles, sem entrega digital ou chaves de ativação.
- Um jogo para PS5 e sua versão para Xbox são produtos separados, com preços e estoques próprios.
- Cada produto tem várias categorias e uma plataforma. Será necessária a tabela `category_product`, sem `category_id` diretamente em `products`.
- Jogos podem ter vários gêneros, como Ação, Aventura e RPG, usados como filtro de busca; consoles não precisam ter gênero. Categorias e gêneros foram mantidos separados nesta modelagem.
- O usuário esclareceu que “porte de console” significa plataforma, como PS5, Xbox Series ou Nintendo Switch. A busca terá filtros por gênero e plataforma, sem um filtro adicional de console de mesa, portátil ou híbrido.
- Dados dos produtos: nome, descrição, preço, uma imagem, categorias, plataforma, estoque e indicador de ativo.
- Dois perfis em `users.role`: `customer` e `admin`. Não há perfil separado de vendedor.
- Visitantes podem navegar e montar carrinho; login é obrigatório para criar pedido.
- Carrinho do visitante persiste no `localStorage` do mesmo navegador. Isso não sincroniza entre dispositivos.
- Carrinho do cliente autenticado fica no banco e deve ser preservado ao sair da conta.
- No login, incorporar os itens do visitante ao carrinho da conta; somar quantidades de produtos iguais e validar a disponibilidade antes da compra.
- Após incorporar com sucesso os itens do visitante, retirar os itens transferidos do carrinho local para não duplicá-los a cada login.
- Um cliente cadastra um endereço por vez, pode salvar vários e seleciona exatamente um por pedido, por meio de cartões de seleção.
- Cada pedido guarda uma cópia do endereço escolhido; alterações no cadastro não mudam pedidos existentes.
- Cada item de pedido guarda nome e preço unitário históricos.
- Pagamento é simulado: o próprio cliente confirma o pagamento de seu pedido, como se tivesse acabado de pagar por cartão ou Pix. Não integrar gateway ou cobrança real.
- O usuário confirmou que haverá apenas confirmar ou cancelar o pagamento, sem escolher nem registrar Pix ou cartão. Não criar `payment_method`.
- A tela de um pedido pendente oferece duas caixas de ação, uma abaixo da outra: confirmar pagamento e cancelar compra. As opções são alternativas sobre o mesmo pedido.
- O resumo do carrinho/compra mostra itens e total. O botão de confirmar abre “Confirme a compra”; o de cancelar abre “Você quer mesmo cancelar?”. Cada confirmação oferece voltar ou concluir a ação escolhida. Apenas abrir ou fechar a confirmação não altera pedido nem estoque. Implementar esse fluxo somente na etapa 9.
- Status: somente `pending_payment`, `paid` e `cancelled`.
- O estoque diminui somente quando a confirmação do cliente é aceita pelo backend.
- Carrinhos e pedidos pendentes não reservam unidades. Conferir estoque ao criar o pedido e novamente ao confirmar pagamento.
- Se faltar estoque, não confirmar o pagamento. A confirmação e a baixa de todos os itens devem ocorrer juntas, em uma transação, com proteção contra confirmações simultâneas.
- Não diminuir estoque novamente se o pedido já estiver pago. Pedidos pagos ou cancelados não aceitam outra decisão; confirmação e cancelamento concorrentes devem resultar em apenas uma decisão aceita.
- O cliente pode cancelar um pedido próprio enquanto estiver aguardando pagamento. Nesse caso não há estoque para devolver.
- Desativar produtos com `is_active` para preservar o histórico dos pedidos.
- Valores monetários: `decimal(10,2)`. Estoque e quantidades: inteiros.

**Ideia adiada a pedido do usuário:** expiração automática de pedidos sem pagamento. Foi sugerido prazo de 24 horas, mas o prazo e a implementação ficaram para depois. Não criar agendador ou regra de expiração agora.

Também não foram incluídos nesta entrega reembolsos, cancelamento após pagamento, cupons, avaliações, múltiplos vendedores ou cálculo de frete. Não ampliar o escopo sem combinar com o usuário.

## Estrutura implementada no banco

As 12 tabelas de negócio definidas nesta revisão são:

`users`, `addresses`, `categories`, `platforms`, `genres`, `products`, `category_product`, `genre_product`, `carts`, `cart_items`, `orders` e `order_items`.

Campos e regras estão em `docs/data-model.md`; a implementação e as verificações da etapa 4 estão em `docs/migrations.md`. Foram aplicadas 15 migrations: as 3 padrão do Laravel e 12 novas, incluindo uma alteração para `users.role` e 11 criações de tabelas. O banco tem as 12 tabelas de negócio e 8 técnicas do Laravel.

Os 10 Models estão revisados: `User`, `Address`, `Category`, `Platform`, `Genre`, `Product`, `Cart`, `CartItem`, `Order` e `OrderItem`. `Category.products()` e `Product.categories()` usam `belongsToMany`, sem `category_id` em `products`. As tabelas de ligação usam relacionamentos muitos-para-muitos do Eloquent, sem classes próprias.

Os Models, as tabelas e os seeders estão definidos e verificados. Produtos, categorias, plataformas e gêneros têm CRUD básico. Os fluxos de contas, endereços, carrinho e pedidos permanecem pendentes; não apresentar a compra como pronta. Resources e FormRequests ficam para a etapa 7 e autenticação para a etapa 8. Aguardar “próximo” antes de outra etapa.

## Etapa 6 — quatro CRUDs do catálogo concluídos

- Criados `app/Http/Controllers/ProductController.php`, `app/Services/ProductService.php` e `routes/api.php`; `bootstrap/app.php` carrega a API. Não foi instalado Sanctum nem executado `install:api`.
- Rotas: GET e POST `/api/products`; GET, PUT/PATCH e DELETE `/api/products/{product}`. O Laravel faz route model binding e retorna 404 para IDs inexistentes.
- Controller com validação inline e respostas JSON, sem Resources ou FormRequests. Service concentra consultas, gravações e associações. Listagem sem paginação ou filtros nesta parte.
- Cadastro exige plataforma existente e ao menos uma categoria; gêneros são opcionais. Preço não negativo com até duas casas decimais; estoque inteiro não negativo. IDs inexistentes e repetidos nas listas são rejeitados. A validação existente será organizada em FormRequests na etapa 7.
- Cadastro e atualização usam transação e `sync()` nas associações. Listas omitidas em atualização são preservadas; listas enviadas substituem as atuais, e `genre_ids: []` remove gêneros. PUT e PATCH aceitam atualização parcial nesta versão.
- DELETE desativa com `is_active = false`; não remove linha ou vínculos. GET lista ativos e inativos para conferir a administração. Consultar um inativo retorna 200 e permite reativação por PATCH. A listagem pública e as permissões virão nas etapas correspondentes.
- As rotas ainda não exigem login ou perfil administrativo; essa parte permanece para a etapa 8, conforme o roteiro. Não há CRUD de usuários ou exposição de senhas nesta API.
- Acrescentado `tests/Feature/ProductApiTest.php` com 10 testes. Na entrega inicial de produtos, a suíte passou com 14 testes e 156 asserções. Após a ampliação do catálogo, passou com 20 testes e 336 asserções em SQLite em memória.
- Pint passou nos cinco arquivos PHP desta parte; sintaxe verificada no Controller, Service e teste.
- Verificação HTTP pelo computador: listagem 200 com 7 produtos, ID inexistente 404 e POST vazio 422. Não houve gravação no MySQL nessas requisições; as mutações dos testes ocorreram somente em memória.
- Após a nova autorização, criados `CategoryController`, `PlatformController`, `GenreController` e seus três Services. Validam `name` como texto obrigatório no POST, até 255 caracteres e único na tabela; atualização permite manter o próprio nome. As respostas incluem `products_count`.
- Os três Services rejeitam exclusão enquanto houver produtos associados, com 422 e a chave `category`, `platform` ou `genre`. Cadastros sem vínculos são removidos fisicamente com 204. Produtos continuam sendo desativados com `is_active`.
- `routes/api.php` usa `Route::apiResources()` e registra 20 rotas para os quatro Controllers. Não foram alterados Models, migrations ou dados de seeders nessa ampliação.
- Criado `tests/Feature/CatalogApiTest.php` com seis testes que percorrem os três cadastros. A suíte passou com 20 testes e 336 asserções. Pint passou nos oito arquivos da ampliação.
- Verificação HTTP: GET de cada cadastro retornou 200, preservando 7 produtos, 3 categorias, 3 plataformas e 4 gêneros. Os quatro POSTs vazios retornaram 422. Não houve gravação no MySQL nessas verificações.
- Guia geral em `docs/crud-catalog.md`; percurso de todas as requisições de produtos em `docs/crud-products.md`, desde `public/index.php` até consultas SQL e resposta, incluindo 404, 422 e 204 vazio. O checklist manual fica para o estudante repetir.

## Resultado da etapa 5 — concluída no Windows

- Foram criados `UserSeeder`, `CategorySeeder`, `PlatformSeeder`, `GenreSeeder`, `ProductSeeder` e `AddressSeeder`, chamados em ordem pelo `DatabaseSeeder`, dentro de uma transação.
- `firstOrCreate` e `firstOrNew` preservam registros encontrados. `syncWithoutDetaching` acrescenta as associações sem apagar outros vínculos. As identificações dos exemplos e suas limitações estão em `docs/seeders.md`.
- Contas locais de demonstração: `admin@ticogames.test` e `customer@ticogames.test`, com senha inicial `password`. Isso não implementa autenticação; não usar como credenciais de produção.
- Criada a imagem genérica `public/images/product-placeholder.svg` para os exemplos. Preços e estoques são fictícios.
- Neste computador o MySQL ainda não tinha a estrutura registrada no ambiente Linux. As 15 migrations existentes foram aplicadas, sem recriar ou apagar tabelas.
- A carga foi executada duas vezes no MySQL 8.4.13. As contagens permaneceram em 2 usuários, 2 endereços, 3 categorias, 3 plataformas, 4 gêneros, 7 produtos, 11 vínculos de categorias e 7 de gêneros. Carrinhos e pedidos seguem vazios.
- `tests/Feature/DatabaseSeederTest.php` acrescenta dois testes de integração. A suíte passou com 4 testes e 41 asserções, em SQLite em memória. Foram verificadas as relações e a preservação de alterações após repetir os seeders. Nenhuma alteração dos testes foi aplicada ao MySQL.
- Sintaxe PHP e Pint passaram nos sete arquivos de seeders e no teste novo.
- O Docker Desktop 4.97.0 falhou em `docker-secrets-engine/engine.sock` ao ser iniciado pelo terminal. O encerramento normal falhou; foram identificados e encerrados apenas os processos da inicialização com erro e o Docker foi aberto pelo Windows Explorer. O engine voltou a responder e os serviços do projeto ficaram saudáveis. Não houve exclusão de diretórios, reset, reinstalação ou remoção de volumes. A causa específica não foi comprovada; a recuperação vale para esta execução. Se reaparecer, prefira abrir pelo menu Iniciar.
- Não foram criados Controllers, Services, Resources, FormRequests, autenticação ou telas. Próxima etapa: 6, apenas quando o usuário pedir.

## O que foi feito até aqui

1. Revisados os Dockerfiles, o `compose.yaml` e os dois `.dockerignore`. As exclusões foram ampliadas para credenciais, dados locais, dependências e arquivos de desenvolvimento. O usuário pediu simplicidade depois dessa revisão; não ampliar essas configurações sem necessidade.
2. Criada a modelagem em `docs/data-model.md`.
3. Aproveitado o projeto Laravel já existente, sem reinstalar o framework nem adicionar Laravel Sail.
4. Simplificado e comentado o `.env.example`, com nome `TicoGames`, conexão MySQL, sessões e cache em arquivos, tarefas síncronas e emails no log.
5. Criado o `.env` local a partir do exemplo e gerada a chave com `php artisan key:generate`. O `.env` é ignorado pelo Git; não copiar a chave para este documento.
6. Criado o roteiro em português no `README.md`, substituindo o texto padrão do Laravel.
7. Iniciado o Docker Desktop, construída a imagem PHP e iniciados os serviços `mysql` e `php`.
8. Instaladas as dependências do Composer dentro do volume `php_vendor`.
9. Limpado o cache de configuração e executadas as verificações da etapa 2.
10. Retomada e concluída a etapa 1 com as novas respostas: várias categorias por produto, busca por gênero e plataforma, pagamento simulado confirmado pelo cliente, somente três status e nenhum registro da forma de pagamento. Atualizados `docs/data-model.md`, `README.md` e este arquivo; `git diff --check` passou. Não foram executados testes de funcionalidades nesta revisão documental.
11. Os documentos de continuidade foram movidos pelo usuário para `docs/README.md` e `docs/gpt.md`; manter essa localização.
12. Revisada a etapa 2 na pasta atual: criado o `.env` a partir do exemplo e gerada sua chave. Os contêineres ativos apontavam para uma cópia antiga na lixeira e foram recriados com `docker compose up -d --build mysql php`, preservando os volumes. A montagem atual é `/home/joaoantonio/TicoGames` em `/app`, com dependências no volume `php_vendor`.
13. Acrescentados `libicu-dev` e a extensão `intl` ao `Dockerfile.dev` para o comando `db:show`. Configurado somente `/app` como diretório confiável do Git dentro da imagem, evitando o aviso de proprietário diferente durante o uso do Composer. O arquivo possui comentários em português.
14. Iniciada a etapa 3 pelo `User`, mantendo os campos `name`, `email` e `password` em `$fillable`, ocultando senha e token, preservando os casts de data e hash e os relacionamentos com endereços, carrinho e pedidos. Os comentários explicam cada bloco; `role` permanece fora de `$fillable`, e seu padrão `customer` será definido na migration. `php -l app/Models/User.php` e `git diff --check` passaram. As solicitações para executar Pint e verificações em memória no Docker foram recusadas pelo usuário; essas verificações não executaram. Não foram criadas tabelas nem dados.
15. O usuário pediu usar somente seeders. Removidos o import da `UserFactory` e o trait `HasFactory` do `User`. Os demais Models serão ajustados individualmente, mantendo a regra de um por vez. Nenhum seeder foi implementado nesta etapa.
16. Revisado somente o `Address`: retirado `HasFactory`, comentados os campos do endereço e explicado `belongsTo(User::class)`, que liga `addresses.user_id` a `users.id`. CEP e número serão colunas de texto, e complemento será nullable na migration. Não foram necessários casts personalizados para os campos de endereço. `php -l app/Models/Address.php` e `git diff --check` passaram. Gravação e consultas no banco permanecem pendentes das migrations da etapa 4. Nenhum outro Model foi alterado nessa revisão.
17. Revisado somente o `Category`: retirado `HasFactory`, mantido `name` em `$fillable` e substituído o relacionamento antigo `hasMany` por `belongsToMany(Product::class)`, conforme a regra de várias categorias por produto. Por convenção, a ligação usa `category_product`, com `category_id` e `product_id`, sem timestamps. A unicidade do nome e as tabelas serão definidas nas migrations. `php -l app/Models/Category.php` e `git diff --check` passaram. A relação inversa será ajustada na revisão do `Product`; os testes com dados dependem dessa revisão e das migrations.
18. Revisado somente o `Platform`: retirado `HasFactory`, mantido `name` em `$fillable` e explicado `hasMany(Product::class)`, que liga `platforms.id` a `products.platform_id`. Cada versão de produto pertence a uma plataforma; uma plataforma reúne vários jogos e consoles. A unicidade do nome e as tabelas serão definidas nas migrations. `php -l app/Models/Platform.php` e `git diff --check` passaram. As consultas com dados ficam pendentes das migrations da etapa 4.
19. Revisado somente o `Genre`: retirado `HasFactory`, mantido `name` em `$fillable` e explicado `belongsToMany(Product::class)`. A tabela de ligação `genre_product` terá `genre_id` e `product_id`, sem timestamps. Um jogo pode ter vários gêneros e consoles podem ficar sem gêneros. A unicidade do nome e as tabelas serão definidas nas migrations. `php -l app/Models/Genre.php` e `git diff --check` passaram. As consultas com dados ficam pendentes das migrations da etapa 4.
20. Revisado somente o `Product`: retirado `HasFactory` e removido `category_id` de `$fillable`. A antiga relação `category()` foi substituída por `categories()` com `belongsToMany`, via `category_product`. Comentados os campos e as relações com plataforma, gêneros, itens de carrinho e itens de pedido. Mantidos os casts `price => decimal:2`, `stock => integer` e `is_active => boolean`. O cast decimal retorna texto com duas casas; não implementa cálculos ou baixa de estoque. `php -l app/Models/Product.php` e `git diff --check` passaram, e os pares de relações com `Category`, `Platform` e `Genre` foram conferidos no código. Consultas, persistência e conversões em execução ainda não foram testadas nesta revisão.
21. Revisado somente o `Cart`: retirado `HasFactory`, mantido `user_id` em `$fillable` e comentadas as relações `user()` com `belongsTo` e `items()` com `hasMany`. O carrinho guarda o dono; produtos e quantidades ficam em `cart_items`. A unicidade de `carts.user_id` será definida na migration. `php -l app/Models/Cart.php` e `git diff --check` passaram; os vínculos com `User.cart()` e o rascunho de `CartItem.cart()` foram conferidos no código. Nenhum dado foi gravado, e a lógica de incorporação do carrinho de visitante permanece para as etapas seguintes.
22. Revisado somente o `CartItem`: retirado `HasFactory`, comentados `cart_id`, `product_id` e `quantity`, mantido o cast inteiro de `quantity` e explicadas as relações `belongsTo` com carrinho e produto. O preço vem do produto atual; adicionar itens não reserva estoque. A unicidade do par `cart_id` e `product_id` será criada na migration, e a validação de quantidade positiva virá nas etapas próprias. `php -l app/Models/CartItem.php` e `git diff --check` passaram. Não foram executados testes de persistência ou conversões em execução nesta revisão.
23. A pedido do usuário, revisados `Order` e `OrderItem` juntos, concluindo a etapa 3. Retirado `HasFactory` dos dois e comentados todos os campos, casts e relacionamentos. `Order` guarda usuário, status, total e os campos de cópia do endereço, com `user()` e `items()`. `OrderItem` guarda pedido, produto, nome e preço históricos e quantidade, com `order()` e `product()`. Total e preço unitário usam `decimal:2`; quantidade usa `integer`. A cópia dos dados, o cálculo do total e as transições de status serão implementados no fluxo de pedidos, não pelos Models. `php -l` passou nos 10 Models; `git diff --check` passou; não há referência a factories em `app/Models`. Não foram criadas migrations, seeders, funcionalidades de autenticação ou fluxo de pedidos.
24. Concluída a etapa 4: criadas 12 migrations datadas de `2026_10_28_000001` a `2026_10_28_000012`, mantendo as 3 padrão. Acrescentado `users.role` em migration separada; criadas as 11 tabelas de negócio restantes, com chaves estrangeiras, índices únicos, chaves compostas nas associações, enum de status, valores padrão e tipos alinhados aos Models. As 15 migrations foram aplicadas no MySQL, todas no lote 1. Criado o guia `docs/migrations.md`, com exemplos e explicações dos conceitos novos. Não foram criados seeders ou CRUDs.

## Resultado da etapa 4 — concluída

- `php -l` passou nas 15 migrations e Pint passou nos 12 arquivos novos; `git diff --check` passou.
- Aplicação, rollback e reaplicação passaram em SQLite em memória. O rollback das tabelas do MySQL do projeto não foi executado.
- `php artisan migrate:status`: 15 migrations executadas, lote 1. Executar `migrate` novamente informou `Nothing to migrate`.
- `php artisan db:show --counts`: 20 tabelas, todas sem dados, exceto `migrations` com 15 registros.
- Um script temporário no MySQL verificou relações, casts, valores padrão, campos de endereço, índices únicos, chaves estrangeiras, estoque sem sinal, rejeição de status inválido, cascatas e restrições de exclusão. As cópias históricas, preenchidas manualmente para o teste, permaneceram independentes do catálogo e do endereço salvo.
- Os dados usados no script foram desfeitos por uma transação. Não foram criadas factories, seeders nem testes permanentes nessa verificação.
- A suíte inicial continua com 2 testes passando e 2 asserções; ela é separada das verificações do esquema.
- `unsignedInteger` aceita zero. Quantidade mínima 1, preços não negativos e pelo menos uma categoria por produto serão validados nos FormRequests da etapa 7 e usados pelos serviços; não são totalmente impostos por essas migrations.
- `cascadeOnDelete` limpa endereços/carrinhos da conta, itens de carrinho e associações. `restrictOnDelete` preserva referências a plataformas, produtos e histórico de pedidos. Excluir um usuário com pedidos ou um pedido com itens é bloqueado.
- Próxima etapa: 5, Seeders, somente após “próximo”.

## Resultado da etapa 3 — concluída

- `User`: revisado e comentado. Sintaxe PHP verificada.
- `Address`: revisado e comentado, sem factories. Sintaxe PHP verificada; gravação e consultas do relacionamento dependem das migrations.
- `Category`: revisado e comentado, sem factories. Sintaxe PHP verificada; relação com produtos definida como muitos para muitos, através de `category_product`.
- `Platform`: revisado e comentado, sem factories. Sintaxe PHP verificada; relação com produtos definida como um para muitos, por `products.platform_id`.
- `Genre`: revisado e comentado, sem factories. Sintaxe PHP verificada; relação com produtos definida como muitos para muitos, através de `genre_product`.
- `Product`: revisado e comentado, sem factories. Sintaxe PHP verificada; plataforma como `belongsTo`, categorias e gêneros como `belongsToMany`, itens de carrinho e pedido como `hasMany`, com casts de preço, estoque e ativo.
- `Cart`: revisado e comentado, sem factories. Sintaxe PHP verificada; pertence a um usuário e possui vários itens. Um carrinho por usuário será garantido na migration.
- `CartItem`: revisado e comentado, sem factories. Sintaxe PHP verificada; pertence a um carrinho e a um produto, com quantidade convertida para inteiro. A unicidade de produto por carrinho será garantida na migration.
- `Order`: revisado e comentado, sem factories. Sintaxe PHP verificada; pertence a um usuário, possui vários itens e tem campos para guardar o endereço histórico e o total decimal.
- `OrderItem`: revisado e comentado, sem factories. Sintaxe PHP verificada; pertence a um pedido e a um produto e tem campos para guardar nome e preço históricos e quantidade inteira.
- A etapa 3 entregou os 10 Models. As tabelas foram criadas depois, na etapa 4.
- Verificação de formatação pendente do `User`: Pint. As verificações de hash da senha, ocultação de senha/token no JSON e proteção de `role` serão retomadas na etapa 8, conforme a orientação de adiar o trabalho específico de autenticação e segurança.
- Na etapa 4 foram verificadas relações persistidas e a unicidade de `carts.user_id`, complementando a revisão de código dos Models.

## Verificações da etapa 2 na pasta atual — 28/10/2026

- `docker compose config --quiet` e `composer validate --no-check-publish --no-interaction`: configurações válidas.
- Serviços `mysql` e `php`: saudáveis, com o código da pasta atual montado no contêiner PHP.
- Laravel **12.70.2**, PHP **8.4.27** e MySQL **8.4.10** verificados no ambiente atual.
- `composer check-platform-reqs`: requisitos atendidos; extensão `intl` carregada.
- `php artisan db:show`: conexão MySQL com `ticogames`, com **zero tabelas**.
- `php artisan test`: **2 testes passaram**, com **2 asserções**. São os exemplos iniciais e não validam as funcionalidades da loja.
- `http://localhost:8000/` e `http://localhost:8000/up`: HTTP **200** pelo computador.
- `.env` preenchido e ignorado pelo Git, sem exposição da chave; `git diff --check` passou.
- Nenhuma migration ou seeder foi criado ou executado nesta etapa. O frontend não foi alterado.

Os resultados acima foram obtidos após corrigir a montagem. Os testes executados antes da correção atingiram a cópia antiga e não foram usados para concluir a etapa atual.

## Verificações já realizadas no computador de origem

- `docker compose config --quiet`: configuração válida.
- `composer check-platform-reqs`: requisitos atendidos.
- Laravel **12.70.2**, PHP **8.4.27** e MySQL **8.4.13** nas versões efetivamente testadas.
- `php artisan db:show`: conexão MySQL com o banco `ticogames`, com **zero tabelas**.
- `php artisan test`: **2 testes passaram**, com 2 asserções. São os exemplos iniciais; não comprovam as funcionalidades da loja.
- `http://localhost:8000` e `http://localhost:8000/up`: HTTP **200** pelo computador de origem.
- Contêineres `mysql` e `php`: saudáveis na última verificação.
- `.env` ignorado pelo Git e `git diff --check` sem erros de whitespace.

Esses são resultados anteriores, não uma garantia sobre o ambiente atual ou outro computador. A rota `/up` testa a inicialização do Laravel; a conexão com o banco foi verificada separadamente.

## Estado do código

- Os 10 Models estão revisados e comentados, com campos e relacionamentos alinhados à modelagem e sem `HasFactory`. A sintaxe de todos passou em `php -l`.
- Relações persistidas, casts e campos de cópia histórica foram verificados com dados temporários na etapa 4. O preenchimento automático das cópias, o cálculo de totais e o fluxo de criação e pagamento dos pedidos ainda dependem das etapas de CRUD.
- A etapa 1 alterou documentos. A etapa 2 ajustou o `Dockerfile.dev`, preparou o `.env` local e corrigiu a montagem do ambiente. Nenhuma funcionalidade da loja foi implementada.
- Existem 15 migrations, todas aplicadas no MySQL: as 3 padrão e as 12 novas da etapa 4. A carga inicial foi aplicada na etapa 5; as contagens estão registradas acima.
- `database/seeders/DatabaseSeeder.php` chama seis seeders, sem factories. A carga preenche catálogo, contas e endereços fictícios, preservando registros encontrados.
- A rota `/` ainda mostra a página padrão do Laravel.
- O Vue está inicializado, mas as telas da loja não foram implementadas. Seu serviço não foi necessário para concluir a etapa 2.
- Os quatro CRUDs do catálogo estão implementados. Os fluxos de contas, endereços, carrinho, pedidos, pagamento simulado e Sanctum permanecem pendentes.
- O `Dockerfile` da raiz é separado da configuração de desenvolvimento. O Compose usa `Dockerfile.dev` para PHP e `front-end/Dockerfile.dev` para Vue.

## Continuar em outro computador

Copiar ou clonar o projeto incluindo `docs/gpt.md`, `docs/README.md` e `docs/data-model.md`. Este arquivo transfere o contexto, não o histórico completo do chat nem o ambiente Docker.

As imagens, os volumes do Docker e o `.env` não são transportados pelo Git. No outro computador, siga `docs/README.md` para preparar o ambiente local. Não envie segredos ao repositório. Execute os comandos na raiz do projeto, onde está `compose.yaml`.

Com Docker Desktop aberto, execute no PowerShell, na raiz do projeto:

```powershell
# Cria o arquivo local somente se ele ainda não existir.
if (-not (Test-Path -LiteralPath .env)) {
    Copy-Item -LiteralPath .env.example -Destination .env
}

# Prepara e inicia o backend e o MySQL; instala as dependências PHP.
docker compose up -d --build mysql php

# Acompanha a instalação; Ctrl+C fecha apenas a visualização dos logs.
docker compose logs -f php
```

Depois que o log informar que o servidor iniciou, encerre a visualização com Ctrl+C:

```powershell
# Gera a chave apenas se o novo .env ainda estiver sem uma chave.
if (-not (Select-String -LiteralPath .env -Pattern '^APP_KEY=.+$' -Quiet)) {
    docker compose exec php php artisan key:generate
}

# Atualiza as configurações carregadas e verifica o ambiente.
docker compose exec php php artisan config:clear
docker compose exec php php artisan --version
docker compose exec php php artisan db:show
docker compose exec php php artisan test
```

Em um banco novo, reproduza a estrutura com `docker compose exec php php artisan migrate`, conforme `docs/migrations.md`, e carregue os exemplos com `docker compose exec php php artisan db:seed`, conforme `docs/seeders.md`. O comando `composer run setup` não é o roteiro usado neste projeto.

## Prompt reutilizável — Controller e Service linha por linha

Registrado a pedido do usuário em 29/10/2026. Aplicar quando ele pedir novamente esse tipo de explicação, sem iniciar outra etapa ou alterar o código por conta própria.

Pedido original: “pode me explicar um controller que tenha mais ou menos o que todos tem linha por linha e um service do mesmo jeito”.

Prompt para copiar e reutilizar:

> Leia os arquivos atuais deste projeto e escolha um Controller e seu Service que representem o padrão usado nos demais CRUDs. Use CategoryController e CategoryService se ainda forem bons exemplos. Explique em português, linha por linha, para que eu consiga entender e apresentar o projeto para uma banca. Não altere o código nem avance de etapa.
>
> Antes do código, explique as responsabilidades do Controller, do Service e do Model. Mostre links dos arquivos e utilize os números reais das linhas. Explique todas as linhas com código; informe de uma vez o papel dos comentários, linhas em branco e chaves.
>
> Detalhe namespace, imports use, classe, herança, construtor, injeção de dependência, propriedades e visibilidade public/private. Explique também $this, variáveis, parâmetros, tipos de retorno, return, ->, ::, arrays e chamadas encadeadas. Não apenas repita os comentários: mostre o que cada expressão faz e em qual ordem ela é executada.
>
> Percorra as cinco ações: listar, consultar por ID, cadastrar, atualizar e excluir ou desativar. Explique a validação, o route model binding, as regras de unicidade e ignore, as consultas do Eloquent, as contagens de relacionamentos, a criação, a atualização, o refresh e as exceções que aparecerem no exemplo. Quando uma expressão juntar várias operações, desmonte-a em passos equivalentes apenas para explicar, sem editar o arquivo.
>
> Relacione cada ação ao método HTTP, à URL, ao corpo enviado, ao método do Controller, ao método do Service, à operação no banco e à resposta recebida no Thunder Client. Explique o caminho completo de ida e volta e onde ele para em caso de erro. Mostre os status 200, 201, 204, 404 e 422 quando aplicáveis e indique quando a resposta não tem corpo. Use exemplos JSON; se resumir uma resposta, avise quais campos foram omitidos.
>
> Ao final, mostre o que se repete nos outros CRUDs e quais diferenças exigem atenção, como relacionamentos, transações e desativação de produtos. Termine com um checklist curto para eu conferir o comportamento no Thunder Client. Mantenha os nomes do código em inglês e as explicações em português.

Forma curta de pedir novamente:

> Explique um Controller e seu Service linha por linha, seguindo o prompt reutilizável salvo em docs/gpt.md.

## Mensagem sugerida para retomar

> Leia docs/gpt.md, docs/README.md, docs/data-model.md, docs/migrations.md, docs/seeders.md, docs/crud-catalog.md e docs/crud-products.md. As etapas 1 a 5 e os quatro CRUDs básicos do catálogo estão concluídos. Os fluxos de contas, endereços, carrinho e pedidos ainda estão pendentes. Explique em português, comente o código e respeite uma etapa por vez. Quero entender todas as requisições e respostas de pelo menos um exemplo, do início ao fim. Espere eu dizer “próximo” antes de outra etapa. Resources e FormRequests ficam para a etapa 7; Sanctum e permissões para a etapa 8.
