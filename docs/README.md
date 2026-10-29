# TicoGames

Projeto final de curso: loja de jogos físicos e consoles, com backend Laravel, banco MySQL e frontend Vue.

## Etapa atual: 6 — CRUDs básicos do catálogo concluídos

Após o CRUD de produtos, o usuário autorizou implementar os CRUDs do catálogo juntos e explicar um deles por completo. Produtos, categorias, plataformas e gêneros possuem Controllers, Services e 20 rotas JSON no total. O guia [crud-catalog.md](crud-catalog.md) apresenta os quatro cadastros, e [crud-products.md](crud-products.md) acompanha cada requisição do Thunder Client até o banco e a resposta de volta.

Em 28/10/2026, concluímos os seeders usando os Models diretamente, sem factories. O MySQL possui 20 tabelas e recebeu 2 usuários, 2 endereços, 3 categorias, 3 plataformas, 4 gêneros e 7 produtos, com as associações de categorias e gêneros. Carrinhos e pedidos continuam vazios. A carga foi executada duas vezes sem duplicações.

A modelagem define várias categorias e uma plataforma por produto, gêneros e pagamento simulado confirmado pelo cliente. Os 10 Models, as migrations e os seeders estão preparados. Na etapa 6, os quatro CRUDs do catálogo estão implementados. Resources e FormRequests ficam para a etapa 7 e autenticação para a etapa 8. Os fluxos de contas, endereços, carrinho e pedidos ainda precisam ser implementados, respeitando usuários, estoque e histórico.

No `User`, os comentários explicam os campos permitidos, os recursos padrão do Laravel, os casts e os relacionamentos. A sintaxe PHP foi verificada. Toda a configuração de autenticação, incluindo registro, login, sessão, Sanctum e regras de acesso, fica para a etapa 8, assim como suas verificações específicas de segurança. As relações com os dados da loja foram verificadas com registros temporários na etapa 4.

No `Address`, os campos guardam destinatário, CEP, rua, número, complemento, bairro, cidade e estado. `user_id` identifica o usuário, e `user()` descreve o relacionamento `belongsTo` com `User`. CEP e número são texto, e complemento é opcional. Esses valores foram preservados na verificação com dados temporários.

No `Category`, `name` guarda o nome da categoria e `products()` usa `belongsToMany`, pois produtos podem participar de várias categorias. O Eloquent usa a tabela de ligação `category_product`, alinhada com `Product.categories()`. As associações foram verificadas no MySQL.

No `Platform`, `name` guarda o nome da plataforma e `products()` usa `hasMany`: uma plataforma reúne vários produtos, e cada produto aponta para uma plataforma por `platform_id`. Esse vínculo foi verificado no MySQL.

No `Genre`, `name` guarda o nome do gênero e `products()` usa `belongsToMany`, através de `genre_product`. Jogos com vários gêneros e um console sem gênero foram verificados com dados temporários.

No `Product`, os campos guardam plataforma, nome, descrição, preço, caminho da imagem, estoque e ativo. `categories()` e `genres()` usam tabelas de ligação; não há `category_id` no produto. Os casts retornam preço como texto decimal com duas casas, estoque como inteiro e ativo como booleano. Os valores e as associações foram verificados com dados temporários na etapa 4.

No `Cart`, `user_id` identifica o dono, `user()` usa `belongsTo` e `items()` usa `hasMany`. Produtos e quantidades ficam nos itens, em `cart_items`. A unicidade de `user_id` e a exclusão dos itens em cascata foram verificadas no MySQL. A incorporação do carrinho de visitante será implementada nas etapas seguintes.

No `CartItem`, `cart_id` indica o carrinho, `product_id` indica o produto e `quantity` guarda a quantidade, com cast inteiro. As duas relações usam `belongsTo`. O preço vem do produto atual. A quantidade padrão e a unicidade do par `cart_id` e `product_id` foram verificadas no MySQL.

No `Order`, os campos guardam usuário, status, total e uma cópia do endereço de entrega. `user()` usa `belongsTo` e `items()` usa `hasMany`. `total` tem cast `decimal:2`. O banco aceita `pending_payment`, `paid` e `cancelled`, com padrão `pending_payment`.

No `OrderItem`, os campos guardam pedido, produto, nome histórico, preço unitário histórico e quantidade. As relações `order()` e `product()` usam `belongsTo`; `unit_price` tem cast `decimal:2` e `quantity`, `integer`. A cópia do endereço, do nome e do preço será preenchida pelo fluxo de criação do pedido, nas etapas de CRUD. O cálculo do total e a confirmação do pagamento também ficam para esse fluxo.

A sintaxe PHP dos Models e migrations foi verificada; Pint passou nas 12 migrations novas. As relações, casts, restrições e cópias históricas preenchidas manualmente foram verificados no MySQL, e os registros temporários foram desfeitos. Os 2 testes iniciais continuam passando. O fluxo automático de compra e pagamento ainda será implementado.

Os dados iniciais são criados pelos seeders, sem factories. A suíte atual tem 17 testes aprovados, 254 asserções e 3 testes de validação pendentes. A pedido do usuário, os Controllers estão em `app/Http/Controllers/Api`, recebem IDs inteiros e repassam `$request->all()` aos Services; a validação de entrada ficará para os FormRequests na etapa 7. Autenticação e telas ainda não foram implementadas.

A modelagem está em [data-model.md](data-model.md).

Para continuar em outro computador ou em uma nova conversa, leia [gpt.md](gpt.md): ele registra as decisões, as verificações realizadas e o próximo passo.

## Conceitos

- **Composer:** instala as bibliotecas PHP nas versões registradas em `composer.lock`.
- **Artisan:** ferramenta de comandos do Laravel, como gerar a chave da aplicação.
- **Dockerfile.dev:** prepara o PHP para desenvolvimento.
- **Compose:** organiza os serviços `php`, `mysql` e `node` e a comunicação entre eles.
- **.env:** guarda configurações locais e não é enviado ao Git. O `.env.example` serve de modelo.
- **Extensões PHP:** acrescentam recursos à linguagem. `pdo_mysql` permite conectar ao MySQL; `intl` fornece a formatação usada por comandos como `db:show`.

Aproveitamos o Compose existente em vez de reinstalar o Laravel ou adicionar o Laravel Sail, mantendo uma única configuração de Docker para estudar.

## Primeira inicialização

Execute os comandos na raiz do projeto, onde está `compose.yaml`, com o Docker em execução. Este README está em `docs/`; essa pasta não é o diretório de execução dos comandos.

No Linux, com Bash:

```bash
# Cria a configuração local sem sobrescrever um .env existente.
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Verifica o Compose sem mostrar as credenciais.
docker compose config --quiet

# Constrói a imagem e inicia os serviços com os arquivos desta pasta.
docker compose up -d --build mysql php

# Acompanha a instalação das dependências; Ctrl+C fecha apenas a visualização.
docker compose logs -f php
```

No Windows, abra o Docker Desktop e use o PowerShell:

```powershell
# Cria a configuração local sem sobrescrever um .env existente.
if (-not (Test-Path -LiteralPath .env)) {
    Copy-Item -LiteralPath .env.example -Destination .env
}

# Valida o Compose sem mostrar as credenciais.
docker compose config --quiet

# Constrói a imagem PHP e inicia o banco e o backend em segundo plano.
# O serviço php instala as dependências antes de iniciar o servidor.
docker compose up -d --build mysql php

# Acompanha a inicialização; Ctrl+C encerra apenas a visualização do log.
docker compose logs -f php
```

Quando o log mostrar que o servidor iniciou, encerre a visualização com Ctrl+C. No Linux:

```bash
# Gera a chave somente se ela ainda não estiver preenchida no .env.
if ! grep -q '^APP_KEY=.' .env; then
    docker compose exec php php artisan key:generate
fi

# Descarta configurações antigas em cache após preparar o .env.
docker compose exec php php artisan config:clear
```

No PowerShell:

```powershell
# Preserva uma chave existente e gera somente quando o campo está vazio.
if (-not (Select-String -LiteralPath .env -Pattern '^APP_KEY=.+$' -Quiet)) {
    docker compose exec php php artisan key:generate
}

# Descarta configurações antigas em cache após preparar o .env.
docker compose exec php php artisan config:clear
```

O primeiro `php` identifica o serviço do Compose; o segundo é o programa executado dentro dele.

Ao mover ou copiar a pasta, use novamente `docker compose up -d --build mysql php` na nova localização. Isso atualiza a montagem dos arquivos. Apenas iniciar um contêiner antigo pode manter o vínculo com a pasta anterior. Não é necessário remover volumes para fazer essa atualização.

Abra [http://localhost:8000](http://localhost:8000). A página padrão do Laravel deve aparecer. A rota [http://localhost:8000/up](http://localhost:8000/up) verifica a inicialização da aplicação, mas não a conexão com o banco.

## Configuração escolhida

O `.env.example` tem comentários explicando cada bloco.

| Configuração | Valor | Motivo |
| --- | --- | --- |
| `APP_NAME` | `TicoGames` | Identifica o projeto. |
| `DB_CONNECTION` | `mysql` | Banco escolhido para a loja. |
| `DB_HOST` | `mysql` | Nome do serviço do banco na rede Docker. |
| `SESSION_DRIVER` | `file` | Sessões em arquivos, sem tabela nesta etapa. |
| `CACHE_STORE` | `file` | Cache em arquivos, sem Redis ou tabela. |
| `QUEUE_CONNECTION` | `sync` | Tarefas executadas na própria requisição, sem processo de fila separado. |
| `MAIL_MAILER` | `log` | Emails registrados no log em vez de enviados. |

Sessões e cache poderiam usar o banco, mas arquivos atendem ao desenvolvimento local e permitem iniciar sem migrations. Isso não muda a decisão de salvar o carrinho autenticado no MySQL quando essa funcionalidade for implementada.

As credenciais de exemplo e `APP_DEBUG=true` são para desenvolvimento local. A chave `APP_KEY` deve ser gerada, não copiada para a documentação nem compartilhada.

## Checklist de verificação

```powershell
# Mostra os contêineres e seus estados de saúde.
docker compose ps

# Mostra a versão do Laravel instalada.
docker compose exec php php artisan --version

# Conecta ao MySQL e mostra informações do banco.
docker compose exec php php artisan db:show

# Executa os testes que já acompanham o projeto.
# Eles ainda não comprovam funcionalidades da loja nem a conexão MySQL.
docker compose exec php php artisan test
```

Resultados registrados até a etapa 4, em 28/10/2026:

- [x] O Compose é válido e os serviços `mysql` e `php` estão saudáveis.
- [x] Laravel **12.70.2**, PHP **8.4.27** e MySQL **8.4.10** verificados.
- [x] Os requisitos PHP do Composer estão atendidos e a extensão `intl` está carregada.
- [x] A página inicial e a rota `/up` respondem com HTTP 200.
- [x] `db:show --counts` acessa `ticogames` pelo driver MySQL, com **20 tabelas** após as migrations.
- [x] Os **2 testes iniciais passaram**, com **2 asserções**.
- [x] O `.env` local tem uma chave gerada e está ignorado pelo Git.

Os testes iniciais cobrem a resposta da página padrão e uma asserção de exemplo. A conexão MySQL foi verificada separadamente; os testes ainda não validam catálogo, carrinho, pedidos ou pagamento.

As migrations já foram aplicadas neste ambiente. Em um banco novo, execute `docker compose exec php php artisan migrate` para reproduzir a estrutura, conforme [migrations.md](migrations.md), e `docker compose exec php php artisan db:seed` para carregar os exemplos da etapa 5, conforme [seeders.md](seeders.md). O comando padrão `composer run setup` não é o caminho usado neste roteiro.

## Uso diário

```powershell
# Inicia o ambiente preparado; não precisa gerar a chave novamente.
docker compose up -d mysql php

# Interrompe os serviços sem remover os dados do banco.
docker compose stop
```

O Vue já existe em `front-end/`, mas sua implementação fica para a etapa 9. Para iniciar também seu servidor de desenvolvimento quando necessário:

```powershell
# Inicia o Vue na porta 5173 e instala suas dependências.
docker compose up -d --build node
```

## Estrutura para as próximas etapas

- `app/Models`: classes que representam os dados e relacionamentos.
- `database/migrations`: arquivos que criam e alteram tabelas.
- `database/seeders`: dados iniciais e de demonstração.
- `routes/web.php`: rotas web; por enquanto contém a página padrão.
- `config/`: configurações do Laravel, que podem ler o `.env`.
- `storage/`: arquivos gerados, sessões, cache e logs.
- `front-end/`: projeto Vue separado do backend.

Os quatro CRUDs do catálogo estão concluídos. Aguardar o usuário dizer “próximo” antes de iniciar outra parte. O percurso de todas as requisições de produtos está em [crud-products.md](crud-products.md).
