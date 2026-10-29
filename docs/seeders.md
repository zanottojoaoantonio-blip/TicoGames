# Etapa 5 — Seeders

Concluída em 28/10/2026. Os exemplos foram carregados no MySQL do computador Windows atual.

## Conceito

Uma migration define a estrutura da tabela. Um Model representa os registros e seus relacionamentos. Um **seeder** usa essa estrutura para cadastrar os dados iniciais, sem precisar preencher cada formulário manualmente.

Usamos listas fixas e os Models diretamente, sem factories, conforme combinado. Isso facilita conferir os exemplos e explicar o código para a banca.

## Como os arquivos se organizam

O comando `db:seed` chama o `DatabaseSeeder`. Ele executa os demais seeders nesta ordem:

| Seeder | Dados e dependências |
| --- | --- |
| `UserSeeder` | Duas contas fictícias: administrador e cliente. |
| `CategorySeeder` | Jogos, Consoles e Destaques. |
| `PlatformSeeder` | PlayStation 5, Xbox Series e Nintendo Switch. |
| `GenreSeeder` | Ação, Aventura, RPG e Corrida. |
| `ProductSeeder` | Sete produtos e seus vínculos com as listas anteriores. |
| `AddressSeeder` | Dois endereços fictícios pertencentes ao cliente. |

Os produtos precisam das plataformas, categorias e gêneros. Os endereços precisam do usuário. Por isso, a ordem é importante. Os IDs são obtidos pelas consultas e pelos relacionamentos, sem presumir que uma categoria sempre terá ID 1.

O `DatabaseSeeder` envolve a carga em uma transação: se algum seeder falhar, as alterações daquela execução são desfeitas juntas. Ele não apaga dados existentes.

## Novidades do código

### Buscar antes de criar

Uma chamada direta a `create()` tentaria inserir novos registros toda vez. Isso pode duplicar produtos ou provocar erros nos campos únicos.

`updateOrCreate()` também permite repetir a carga, mas atualiza os registros encontrados. Isso poderia repor o estoque após uma venda ou alterar um preço editado pelo administrador. Para estes exemplos, escolhemos `firstOrCreate()`:

```php
// O primeiro argumento identifica o produto de demonstração.
// O segundo só é usado se o produto ainda não existir.
$product = Product::firstOrCreate(
    ['name' => $data['name'], 'platform_id' => $platform->id],
    [
        'description' => $data['description'],
        'price' => $data['price'],
        'image_path' => 'images/product-placeholder.svg',
        'stock' => $data['stock'],
        'is_active' => true,
    ],
);
```

O mesmo nome para duas plataformas resulta em dois produtos. Reexecutar com o mesmo nome e plataforma não reinicia preço, estoque ou indicador de ativo.

Os nomes das listas, o email das contas e rua/número dos endereços identificam os respectivos exemplos. Alterar esses identificadores manualmente pode fazer a próxima execução criar novamente o exemplo original. Estes seeders não são uma ferramenta de sincronização de catálogo, e a combinação de nome e plataforma não é uma nova restrição única no banco.

### Preparar o usuário antes de salvar

`firstOrNew()` busca uma conta pelo email; se ela não existe, prepara um objeto sem gravá-lo. O `UserSeeder` confere `exists`, preenche explicitamente os campos da conta nova e chama `save()`.

Isso permite atribuir `role` diretamente no seeder, mantendo esse campo fora de `$fillable`. Uma conta existente não tem nome, senha ou perfil alterados pela carga. O cast já existente no `User` transforma a senha em hash. Isso apenas prepara dados; não implementa login, registro ou Sanctum.

### Associar sem apagar vínculos

`attach()` insere uma associação e pode duplicá-la na repetição. `sync()` substitui as associações pela lista fornecida. Escolhemos `syncWithoutDetaching()` para acrescentar o que falta, mantendo as associações que já existem:

```php
// Usa category_product e não remove outras categorias já associadas ao produto.
$product->categories()->syncWithoutDetaching([$category->id]);
```

O mesmo recurso liga gêneros aos jogos. Os consoles têm a lista de gêneros vazia.

### Criar endereço pelo relacionamento

`$customer->addresses()->firstOrCreate(...)` cria o endereço já ligado ao cliente, preenchendo `user_id` pelo relacionamento. Os exemplos usam CEP fictício, um número com letra e complementos com e sem valor para demonstrar os tipos definidos nas migrations.

## Dados de demonstração

- 2 usuários e 2 endereços do cliente.
- 3 categorias, 3 plataformas e 4 gêneros.
- 7 produtos: quatro versões de jogos e três consoles.
- Elden Ring aparece para PlayStation 5 e Xbox Series, com preços e estoques separados.
- 11 associações em `category_product` e 7 em `genre_product`.
- A imagem `public/images/product-placeholder.svg` é uma ilustração genérica local, não uma foto de produto.
- Preços e estoques são fictícios. Os preços são textos decimais, como `'299.90'`.

Contas exclusivamente para demonstração local:

| Perfil | Email | Senha inicial |
| --- | --- | --- |
| Administrador | `admin@ticogames.test` | `password` |
| Cliente | `customer@ticogames.test` | `password` |

O seeder não muda uma conta que já utilize esses emails. A tela de login e a autenticação serão implementadas na etapa 8.

Carrinhos, itens de carrinho, pedidos e itens de pedido continuam vazios. Esses registros devem surgir pelas ações de compra, quando o fluxo for implementado.

## Executar e conferir

Execute na raiz do projeto, com Docker e os serviços `mysql` e `php` ativos:

```bash
# Aplica somente migrations pendentes, caso este seja um banco novo.
docker compose exec php php artisan migrate

# Cadastra os exemplos definidos pelos seeders, sem factories.
docker compose exec php php artisan db:seed

# Mostra as tabelas e suas quantidades de registros.
docker compose exec php php artisan db:show --counts

# Executa os testes em SQLite em memória, conforme phpunit.xml.
docker compose exec php php artisan test
```

Não é necessário usar `migrate:fresh --seed`, que removeria as tabelas e seus dados. `db:seed` é suficiente para esta carga.

## Checklist executado

- [x] Sintaxe verificada nos sete arquivos de seeders e no teste novo.
- [x] Pint passou nos oito arquivos PHP desta etapa.
- [x] Quatro testes passaram, com 41 asserções, em SQLite em memória.
- [x] Os testes verificaram associações, versões por plataforma e consoles sem gênero.
- [x] Reexecutar preservou estoque, preço, indicador de ativo, nome do cliente e um vínculo adicional criados no teste.
- [x] Neste Windows, as 15 migrations existentes foram aplicadas ao MySQL que ainda estava sem estrutura.
- [x] `db:seed` foi executado duas vezes no MySQL; as contagens permaneceram iguais, sem duplicações.
- [x] O MySQL tem 20 tabelas; carrinhos e pedidos continuam vazios.
- [x] Os serviços `mysql` e `php` estavam saudáveis ao concluir a carga.

Os testes novos estão em `tests/Feature/DatabaseSeederTest.php`. Os dados alterados dentro dos testes não foram gravados no MySQL do projeto.

## Ocorrência do Docker no Windows

Antes da carga, o Docker Desktop 4.97.0 falhou ao renomear `docker-secrets-engine/engine.sock` para `.stale`. O encerramento normal não respondeu. Foram identificados e encerrados apenas os processos do Docker que permaneciam na inicialização com erro; o aplicativo foi aberto pelo Windows Explorer e voltou a responder.

Não foi necessário renomear ou excluir diretórios, remover volumes, reinstalar o Docker ou restaurar configurações de fábrica. Essa recuperação foi verificada nesta execução, não como correção permanente. Se o erro reaparecer ao iniciar pelo terminal do Codex, prefira abrir o Docker Desktop pelo menu Iniciar do Windows.

Há um [relato semelhante de falha ao iniciar pelo Codex](https://github.com/openai/codex/issues/52698). Ele sugere uma possível relação com a forma de inicialização; a causa específica deste computador não foi comprovada.

A próxima etapa é a 6: CRUD básico com Controllers e Services. Aguardar o usuário dizer “próximo”.
