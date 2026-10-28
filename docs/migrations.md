# Etapa 4 — Migrations

Concluída em 28/10/2026. Foram criadas 12 migrations e mantidas as 3 que já acompanhavam o Laravel. As 15 foram aplicadas no MySQL `ticogames`.

## O que uma migration faz

O Model representa os registros e seus relacionamentos no PHP. A migration cria ou altera a estrutura onde esses registros serão salvos no banco.

O comando `make:migration` gera um arquivo de migration para você preencher. Já `migrate` executa os arquivos pendentes e altera o banco. Criar o arquivo, por si só, não cria uma tabela.

- `up()` aplica uma mudança, como criar uma tabela ou acrescentar uma coluna.
- `down()` desfaz a mudança estrutural daquela migration.
- `Schema::create()` cria uma tabela.
- `Schema::table()` modifica uma tabela existente. Foi usado para acrescentar `users.role` sem reescrever a migration original.
- O nome do arquivo começa com uma data e um horário. O Laravel usa essa ordem para executar as migrations.
- A tabela técnica `migrations` registra quais arquivos já foram executados e em qual lote (`batch`). Executar `migrate` novamente aplica somente o que ainda está pendente.

As tabelas referenciadas precisam existir primeiro. Por isso, criamos plataformas antes de produtos, e pedidos antes de itens de pedido. A reversão acontece na ordem inversa.

## Tipos e modificadores usados

| Código | Significado no projeto |
| --- | --- |
| `$table->id()` | Chave primária numérica gerada pelo banco. |
| `$table->string('name')` | Texto curto, com limite padrão de 255 caracteres. |
| `$table->text('description')` | Texto maior para descrições. |
| `$table->char('state', 2)` | Campo para uma sigla de estado de dois caracteres. |
| `$table->decimal('price', 10, 2)` | Número decimal com 10 dígitos no total, sendo 2 depois da vírgula. |
| `$table->unsignedInteger('stock')` | Inteiro sem sinal; no MySQL, aceita zero e valores positivos. |
| `$table->boolean('is_active')` | Indicador usado como verdadeiro ou falso pelo Model. |
| `->nullable()` | Permite ausência de valor (`NULL`), como no complemento. |
| `->default(0)` | Define o valor usado pelo banco quando a coluna é omitida na inserção. |
| `->unique()` | Impede valores repetidos, como dois nomes iguais de plataforma. |
| `$table->timestamps()` | Cria `created_at` e `updated_at`, preenchidos pelo Eloquent ao salvar e atualizar registros. |

Em `decimal(10, 2)`, restam oito dígitos para a parte inteira: o maior valor positivo é `99999999.99`. Escolhemos decimal para os valores monetários e mantivemos o cast `decimal:2` nos Models, que os retorna como texto com duas casas.

CEP e número do imóvel são texto. Assim, `01234-567`, `12A` e `s/n` podem ser preservados. Complementos aceitam `NULL`.

`enum()` restringe uma coluna a uma lista de valores. `users.role` aceita `customer` e `admin`, com padrão `customer`. `orders.status` aceita `pending_payment`, `paid` e `cancelled`, com padrão `pending_payment`. Definir essas colunas faz parte da estrutura dos dados; a configuração de autenticação continua na etapa 8.

Uma distinção importante: `unsignedInteger()` aceita **zero**. O padrão `1` dos itens não impede alguém de informar zero explicitamente. A exigência de quantidade mínima `1`, assim como preços não negativos e pelo menos uma categoria por produto, será validada nos FormRequests da etapa 7 e usada pelos serviços. `decimal()` limita o formato numérico, mas não impede valores negativos.

## Chaves estrangeiras

```php
// Cada carrinho pertence a um usuário e só pode existir uma vez por conta.
$table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
```

Lendo da esquerda para a direita:

1. `foreignId('user_id')` cria a coluna que guarda o identificador.
2. `unique()` impede dois carrinhos com o mesmo usuário.
3. `constrained()` usa a convenção de nomes para criar a referência a `users.id`.
4. `cascadeOnDelete()` remove o carrinho se seu usuário puder ser excluído.

Uma chave estrangeira impede, por exemplo, salvar um item que aponta para um produto inexistente. Os modificadores de coluna, como `unique()` e `nullable()`, vêm antes de `constrained()`.

## Exclusões em cascata e restritas

| Relação | Escolha e motivo |
| --- | --- |
| Usuário → endereços e carrinho | Cascata: são dados pertencentes à conta. |
| Carrinho → itens | Cascata: excluir o carrinho limpa seus itens. |
| Categorias, gêneros e produtos → associações | Cascata: remove somente as linhas de ligação, sem excluir a outra entidade. |
| Plataforma → produtos | Restrição: impede remover uma plataforma ainda utilizada. |
| Produto → itens de carrinho ou pedido | Restrição: impede deixar itens sem o produto referenciado. |
| Usuário → pedidos | Restrição: preserva o vínculo com o histórico de compras. |
| Pedido → itens de pedido | Restrição: impede excluir um pedido que já possui itens históricos. |

`restrictOnDelete()` faz o banco recusar a exclusão enquanto houver referências. Por isso, um usuário com pedidos não poderá ser excluído, mesmo que outras relações dessa conta usem cascata. No fluxo da loja, um cancelamento será uma mudança de status, e produtos sairão de oferta por `is_active`.

## Associações e combinações únicas

As tabelas `category_product` e `genre_product` têm apenas os dois IDs. Usamos uma chave primária composta:

```php
// Impede repetir o mesmo produto na mesma categoria.
$table->primary(['category_id', 'product_id']);
```

Ela identifica cada combinação sem precisar de `id` próprio. As associações também não usam timestamps, conforme a modelagem.

Já `cart_items` e `order_items` possuem `id` próprio e um índice único adicional:

```php
// O mesmo produto aparece uma vez em cada carrinho, com sua quantidade.
$table->unique(['cart_id', 'product_id']);
```

Isso permite ter vários produtos no carrinho e permite que o mesmo produto apareça em carrinhos diferentes. Somente a repetição da mesma combinação é impedida. A lógica de somar quantidades será implementada no CRUD.

## Arquivos criados

| Ordem | Migration | Informação principal |
| --- | --- | --- |
| 1 | [add_role_to_users_table](../database/migrations/2026_10_28_000001_add_role_to_users_table.php) | Alteração de tabela, enum e valor padrão. |
| 2 | [create_addresses_table](../database/migrations/2026_10_28_000002_create_addresses_table.php) | Chave estrangeira, texto para CEP/número e complemento opcional. |
| 3–5 | `create_categories_table`, `create_platforms_table`, `create_genres_table` | Repetem a estrutura `id`, nome único e datas. |
| 6 | [create_products_table](../database/migrations/2026_10_28_000006_create_products_table.php) | Plataforma, preço decimal, estoque sem sinal e ativo. |
| 7–8 | `create_category_product_table`, `create_genre_product_table` | Chaves compostas para as relações muitos para muitos. |
| 9 | [create_carts_table](../database/migrations/2026_10_28_000009_create_carts_table.php) | Usuário único para um carrinho por conta. |
| 10 | [create_cart_items_table](../database/migrations/2026_10_28_000010_create_cart_items_table.php) | Quantidade e combinação única de carrinho/produto. |
| 11 | [create_orders_table](../database/migrations/2026_10_28_000011_create_orders_table.php) | Status, total e campos para o endereço histórico. |
| 12 | [create_order_items_table](../database/migrations/2026_10_28_000012_create_order_items_table.php) | Nome/preço históricos e combinação única de pedido/produto. |

Os pedidos guardam a cópia do endereço, sem `address_id`. Os itens de pedido guardam `product_name` e `unit_price`, além do vínculo com o produto. O preenchimento dessas cópias e o cálculo do total serão feitos na criação do pedido, nas etapas de CRUD.

## Comandos usados e resultados

Execute na raiz do projeto:

```bash
# Aplica somente as migrations que ainda estão pendentes.
docker compose exec php php artisan migrate

# Mostra quais arquivos já foram executados e o lote de cada um.
docker compose exec php php artisan migrate:status

# Mostra as tabelas e a quantidade de linhas.
docker compose exec php php artisan db:show --counts
```

O banco agora tem **20 tabelas**: as **12 de negócio** e **8 técnicas do Laravel**, incluindo a própria tabela `migrations`. Há 15 registros de controle nessa tabela, todos no lote 1. As demais tabelas ficaram sem registros; os seeders serão a etapa 5.

`migrate:rollback` executa `down()` nas migrations do último lote. Quando `down()` remove uma tabela, seus dados também são removidos. Testamos a reversão e a reaplicação em SQLite **em memória**, sem desfazer as tabelas do MySQL do projeto.

## Checklist executado

- [x] Sintaxe PHP verificada nas 15 migrations.
- [x] Pint passou nos 12 arquivos novos; `git diff --check` passou.
- [x] Aplicação, rollback e reaplicação passaram em um banco em memória.
- [x] As 15 migrations foram aplicadas no MySQL; executar novamente informou que não havia pendências.
- [x] Foram verificados valores padrão, casts, relações e associações usando dados temporários no MySQL.
- [x] O banco rejeitou duplicações, produto inexistente, estoque negativo e status fora da lista.
- [x] Foram verificadas cascatas e restrições de exclusão.
- [x] Cópias históricas preenchidas manualmente permaneceram independentes do catálogo e do endereço salvo.
- [x] Todos os registros temporários foram desfeitos por uma transação.
- [x] Os 2 testes iniciais do projeto continuam passando, com 2 asserções.

As verificações do MySQL foram executadas por um script temporário, sem criar factories, seeders ou testes permanentes. Elas comprovam a estrutura e a integração dos Models; o fluxo automático de compra e pagamento ainda será implementado.

Referência: [Migrations no Laravel 12](https://laravel.com/docs/12.x/migrations).

A próxima etapa é a 5, Seeders, somente após o usuário dizer “próximo”.
