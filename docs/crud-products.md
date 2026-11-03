# Etapa 6 — CRUD básico de produtos

Revisado em 29/10/2026 para seguir o Controller fornecido pelo usuário: namespace `Api`, IDs inteiros, variáveis antes das respostas e `$request->all()`. A validação de entrada foi retirada a pedido dele e ficará nos FormRequests da etapa 7. Os exemplos abaixo usam dados válidos; entrada inválida ainda não tem uma resposta 422 padronizada.

Parte de produtos concluída em 28/10/2026. Depois, o usuário autorizou concluir os CRUDs do catálogo juntos e explicar um deles com todas as requisições e respostas. Categorias, plataformas e gêneros também estão implementados; veja [crud-catalog.md](crud-catalog.md). Resources e FormRequests ainda ficam para a etapa 7.

## Conceitos antes do código

CRUD resume quatro operações: **Create** (criar), **Read** (consultar), **Update** (atualizar) e **Delete** (excluir). Na API de produtos, a consulta tem duas ações: listar todos e consultar um pelo ID.

O caminho de uma chamada é: **Thunder Client → rota → Controller → Service → Model → MySQL**. A resposta volta em JSON, um formato de dados que o Vue também poderá consumir.

- **Rota:** associa um método e uma URL a uma ação do Controller.
- **Controller:** recebe a requisição, passa os dados ao Service e devolve a resposta HTTP. A validação será acrescentada com FormRequests na etapa 7.
- **Service:** executa as consultas e gravações do produto, incluindo seus relacionamentos.
- **Model:** usa o Eloquent para trabalhar com as tabelas já criadas.

Poderíamos colocar as consultas no Controller. Usamos um Service porque ele faz parte da etapa combinada e mantém a gravação das associações no mesmo lugar, permitindo reutilizar essa operação. Não criamos repositórios, classes base ou interfaces extras.

## Arquivos envolvidos

| Arquivo | Responsabilidade |
| --- | --- |
| `bootstrap/app.php` | Carrega `routes/api.php`, com o prefixo `/api`. |
| `routes/api.php` | Registra as cinco ações de cada cadastro por `Route::apiResources`. |
| `app/Http/Controllers/Api/ProductController.php` | Recebe requisições, passa IDs e dados ao Service e monta respostas. |
| `app/Services/ProductService.php` | Consulta, cria, atualiza e desativa produtos. |
| `tests/Feature/ProductApiTest.php` | Testa o comportamento da API e uma falha durante a gravação. |

Não foi executado `install:api`, pois esse comando também instala Sanctum. A autenticação continua reservada à etapa 8. Por enquanto as rotas não exigem login nem perfil; são a API em desenvolvimento. Não há Resources ou FormRequests novos: eles pertencem à etapa 7.

`Route::apiResource()` registra as rotas. Os Resources da etapa 7 serão classes para organizar os dados da resposta JSON; são recursos diferentes do framework, apesar dos nomes semelhantes.

## Como ler o Controller

O construtor recebe `ProductService`. O Laravel cria essa dependência automaticamente. Não é necessário montar o Service com `new` em cada método nem registrar uma configuração adicional.

`index()` lista todos. `store()` cadastra. `show()` consulta um produto. `update()` altera os campos enviados. `destroy()` desativa o produto. Cada método guarda o resultado do Service em uma variável antes de montar a resposta.

Os métodos `show()`, `update()` e `destroy()` recebem `int $id`, correspondente a `{id}` na rota. O Controller passa esse ID ao Service, que encontra o registro com `findOrFail()`. Se ele não existir, o Laravel transforma a exceção em resposta 404. Não há route model binding implícito de `Product` nesta versão.

`$request->all()` devolve os dados de entrada recebidos; ele não os valida. O Controller repassa esse array ao Service. Os campos `$fillable` dos Models e as restrições do banco continuam existindo. A validação de campos obrigatórios, preços, estoque e associações ficará nos FormRequests.

Nesta primeira versão, PUT e PATCH aceitam atualização parcial: o Service altera os campos enviados e preserva os demais. As regras de validação de cadastro e edição serão implementadas nos FormRequests na etapa 7.

## Como ler o Service

- `listAll()` consulta os cadastros ordenados pelo ID e carrega plataforma, categorias e gêneros com `with()`.
- `findById(int $id)` busca o produto com `findOrFail()` e carrega os relacionamentos com `with()`.
- `create()` separa `category_ids` e `genre_ids`, salva o produto e grava as tabelas de ligação.
- `update(int $id, array $data)` localiza o produto e muda somente os dados enviados. Uma lista de associações enviada substitui a lista anterior; uma lista omitida é preservada.
- `delete(int $id)` localiza o produto e define `is_active` como falso, mantendo a linha no banco.

`DB::transaction()` mantém a gravação do produto e dos vínculos na mesma operação. Se uma associação falhar, a criação ou atualização não deve ficar pela metade.

No CRUD usamos `sync()` porque a lista enviada pelo administrador representa as associações desejadas naquele momento. Isso difere do `syncWithoutDetaching()` dos seeders, que apenas acrescenta os vínculos iniciais sem apagar associações existentes.

Após criar ou atualizar, o Service chama `findById()` para reler o cadastro com seus relacionamentos, incluindo padrões como `is_active = true`. Assim, a resposta do cadastro também contém esse valor quando o cliente não o enviou.

## Comportamento da desativação

Excluir fisicamente com `delete()` apagaria o registro e poderia afetar referências em carrinhos e pedidos. A modelagem já previa retirar produtos de oferta com `is_active`, então usamos essa coluna, sem acrescentar `deleted_at` ou novas migrations.

Esta API básica lista **ativos e inativos** para conferir os cadastros administrativos. Depois de um DELETE, consultar o mesmo ID ainda responde 200, com `is_active: false`. É possível reativá-lo enviando `is_active: true` no PATCH. A listagem pública do catálogo e suas permissões serão tratadas nas etapas correspondentes.

## Testar no Thunder Client

Com os serviços ativos, crie as requisições abaixo. Configure os headers:

| Header | Valor | Para que serve |
| --- | --- | --- |
| `Accept` | `application/json` | Solicita respostas e erros em JSON. |
| `Content-Type` | `application/json` | Informa o formato do corpo de POST, PUT e PATCH. |

Use a aba **Body → JSON** para enviar os corpos. Não é necessário token nesta etapa.

| Método | URL | Ação | Status esperado |
| --- | --- | --- | --- |
| GET | `http://localhost:8000/api/products` | Listar cadastros. | 200 |
| GET | `http://localhost:8000/api/products/1` | Consultar o ID 1. | 200, se existir. |
| POST | `http://localhost:8000/api/products` | Cadastrar. | 201 |
| PATCH ou PUT | `http://localhost:8000/api/products/ID` | Atualizar o ID informado. | 200 |
| DELETE | `http://localhost:8000/api/products/ID` | Desativar. | 204, sem corpo. |

### 1. Listar e identificar os IDs

Comece pelo GET de listagem. Na última verificação havia 7 produtos. A resposta é uma lista JSON, sem um campo externo `data`. Cada produto inclui `platform`, `categories` e `genres`, com seus IDs. Copie os IDs existentes para testar a criação.

Os Models ainda são devolvidos diretamente em JSON, incluindo datas e dados de ligação `pivot`. A organização da resposta em Resources virá na etapa 7. Não há paginação ou filtros nesta parte inicial.

### 2. Cadastrar um produto

Os IDs abaixo são exemplos; confira os existentes no GET. O preço usa ponto decimal, e a imagem é o caminho da ilustração já presente no projeto, sem upload de arquivo.

```json
{
  "name": "Jogo de Teste",
  "description": "Jogo físico para testar o cadastro pelo Thunder Client.",
  "platform_id": 1,
  "price": "199.90",
  "image_path": "images/product-placeholder.svg",
  "stock": 5,
  "category_ids": [1, 3],
  "genre_ids": [1, 3]
}
```

`platform_id` é a única plataforma do produto. `category_ids` deve ter pelo menos uma categoria. `genre_ids` é opcional e pode ser `[]`. Se `is_active` não for enviado, o banco utiliza verdadeiro.

Guarde o `id` retornado com status 201 e use-o nas próximas requisições. Um POST sempre cria um novo cadastro; não tem a busca prévia por nome que usamos nos seeders. Repetir o mesmo POST pode criar outro produto com o mesmo nome e plataforma.

### 3. Consultar e atualizar o produto criado

Faça GET para `/api/products/ID`, substituindo ID pelo valor retornado no POST.

Depois envie um PATCH para a mesma URL:

```json
{
  "price": "179.90",
  "stock": 3
}
```

Somente preço e estoque mudam. Nome, plataforma, categorias e gêneros permanecem.

Para alterar associações, envie as listas completas desejadas. `"genre_ids": []` retira todos os gêneros; omitir esse campo os mantém. Pela regra de negócio, cada produto precisa de pelo menos uma categoria; a rejeição de `"category_ids": []` será implementada nos FormRequests da etapa 7.

### 4. Desativar e conferir

Envie DELETE para o ID de teste. A resposta 204 é vazia, o que significa sucesso. Consulte o mesmo ID com GET e confira `is_active: false`.

O produto permanece salvo e aparece nesta listagem administrativa inicial. Se quiser reativá-lo, envie PATCH com `{"is_active": true}`.

### 5. Conferir os erros disponíveis e os pendentes

- GET, PATCH ou DELETE para `/api/products/999999`, caso o ID não exista: 404, gerado pela busca no Service.
- Campos ausentes, preço e estoque inválidos, associações inexistentes, lista de categorias vazia e IDs repetidos: validação HTTP pendente para a etapa 7.
- As restrições do banco continuam ativas. Elas podem rejeitar uma gravação inválida, mas isso não equivale a devolver erros de campos com status 422.

## Percurso completo: onde cada requisição começa e termina

### Entrada e saída comuns

1. Você escolhe método, URL, headers e, quando necessário, um corpo em **Body → JSON** no Thunder Client.
2. Ao clicar em Send, a requisição chega à porta 8000 do computador, publicada pelo Docker para o servidor PHP do contêiner.
3. `public/index.php` carrega o autoload do Composer e `bootstrap/app.php`, captura a requisição e a entrega ao Laravel.
4. O Laravel passa a requisição pelos middlewares aplicáveis e encontra a ação registrada em `routes/api.php` pelo método e pela URL. Essas rotas recebem o prefixo `/api`.
5. O Controller recebe a requisição e, nos caminhos individuais, o parâmetro `int $id`.
6. Em cadastro ou atualização, o Controller repassa `$request->all()` ao Service, sem validação de entrada nesta etapa.
7. O Service busca o registro com `findOrFail()` quando há ID; se não existir, o fluxo para com 404. Nas demais operações, utiliza o Model e seus relacionamentos; o Eloquent monta as consultas SQL para o MySQL.
8. O banco devolve os resultados. O Eloquent os representa como Models ou Collection. Os casts do `Product` apresentam preço como texto decimal, estoque como inteiro e ativo como booleano.
9. O Service retorna ao Controller. `response()->json(...)` serializa os dados em JSON e define o status. Para DELETE, `noContent()` envia 204 sem JSON.
10. A resposta HTTP volta pela porta publicada e é exibida na aba Response do Thunder Client. O ciclo terminou; uma nova consulta será uma nova requisição.

```mermaid
sequenceDiagram
    participant T as Thunder Client
    participant L as Laravel e rotas
    participant C as ProductController
    participant S as ProductService
    participant M as Product / Eloquent
    participant D as MySQL
    T->>L: Método, URL, headers e corpo
    L->>C: Ação correspondente
    C->>S: Chamada com ID e/ou dados recebidos
    S->>M: Consulta ou gravação
    M->>D: SQL
    D-->>M: Dados ou confirmação
    M-->>S: Model ou Collection
    S-->>C: Resultado
    C-->>T: Status HTTP e JSON, ou 204 vazio
```

O diagrama representa o caminho de sucesso. Um ID inexistente interrompe o caminho na busca feita pelo Service. Uma falha durante a gravação das associações provoca rollback da transação. A validação de entrada será estudada na próxima etapa.

### GET de listagem

Requisição, sem corpo:

```http
GET http://localhost:8000/api/products
Accept: application/json
```

`routes/api.php` seleciona `index()` do `ProductController`, no namespace `Api`. Ele chama `listAll()` do Service. O Service executa `Product::with(['platform', 'categories', 'genres'])->orderBy('id')->get()`.

O Eloquent consulta os produtos e carrega os relacionamentos usando `platform_id`, `category_product` e `genre_product`. A Collection volta ao Controller, que responde **200 OK**, com uma lista JSON.

Trecho reduzido da lista, mostrando alguns campos de dois produtos; a resposta real inclui todos os produtos e seus outros campos:

```json
[
  {"id": 1, "name": "Elden Ring", "price": "299.90", "stock": 8},
  {"id": 2, "name": "Elden Ring", "price": "279.90", "stock": 6}
]
```

Não há alteração no banco. Esta listagem administrativa inicial também inclui produtos desativados.

### GET de um produto

Requisição, sem corpo:

```http
GET http://localhost:8000/api/products/1
Accept: application/json
```

O parâmetro `{id}` recebe 1. O Controller executa `show(int $id)` e chama `findById($id)` do Service. O Service busca o produto com `findOrFail()` e carrega plataforma, categorias e gêneros com `with()`.

O Controller responde **200 OK**, com um objeto JSON. Para ver o que a resposta contém, este exemplo mantém os campos do produto e resume os campos internos dos relacionamentos; a API também envia datas e informações `pivot` das associações:

```json
{
  "id": 1,
  "platform_id": 1,
  "name": "Elden Ring",
  "description": "Jogo físico de ação e RPG para PlayStation 5.",
  "price": "299.90",
  "image_path": "images/product-placeholder.svg",
  "stock": 8,
  "is_active": true,
  "platform": {"id": 1, "name": "PlayStation 5"},
  "categories": [{"id": 1, "name": "Jogos"}, {"id": 3, "name": "Destaques"}],
  "genres": [{"id": 1, "name": "Ação"}, {"id": 3, "name": "RPG"}]
}
```

Se o ID não existir, a resposta é **404 Not Found**, em vez de um produto. Com `Accept: application/json`, o erro vem em JSON com `message`; o texto e os detalhes de debug podem variar conforme a configuração local. O Service não executa a consulta de relacionamentos nesse caso.

### POST de cadastro

Método e endereço:

```http
POST http://localhost:8000/api/products
Accept: application/json
Content-Type: application/json
```

Corpo em Body → JSON:

```json
{
  "name": "Jogo de Teste",
  "description": "Jogo físico cadastrado pelo Thunder Client.",
  "platform_id": 1,
  "price": "199.90",
  "image_path": "images/product-placeholder.svg",
  "stock": 5,
  "category_ids": [1],
  "genre_ids": []
}
```

A rota seleciona `store()` do Controller. Ele chama `create($request->all())` do Service com os dados recebidos. A validação da plataforma e das categorias ficará para os FormRequests.

O Service abre a transação, separa as duas listas de IDs, salva as colunas em `products`, grava os vínculos em `category_product` e `genre_product` e lê o produto com os relacionamentos. Se tudo der certo, a transação é confirmada. Se uma gravação falhar, ela é desfeita junto com as outras dessa operação.

O Controller responde **201 Created** com o objeto criado. Exemplo resumido, usando ID 8 apenas como ilustração:

```json
{
  "id": 8,
  "name": "Jogo de Teste",
  "platform_id": 1,
  "price": "199.90",
  "stock": 5,
  "is_active": true,
  "categories": [{"id": 1, "name": "Jogos"}],
  "genres": []
}
```

A resposta real também inclui descrição, caminho da imagem, datas, plataforma e campos das associações. O ID é gerado pelo banco. Guarde o valor recebido e use-o nas próximas chamadas; ele não é garantidamente 8.

### PATCH ou PUT de atualização

Método e endereço, substituindo 8 pelo ID criado:

```http
PATCH http://localhost:8000/api/products/8
Accept: application/json
Content-Type: application/json
```

Corpo:

```json
{
  "price": "179.90",
  "stock": 3
}
```

A rota chama `update(Request $request, int $id)` do Controller, que repassa `$id` e `$request->all()` ao Service. O Service encontra o produto com `findOrFail()`; um ID inexistente gera 404.

O Service abre uma transação e executa a atualização. Como nenhuma lista foi enviada, categorias e gêneros são preservados. Se `category_ids` ou `genre_ids` forem enviados, `sync()` passa a usar exatamente a lista correspondente. Depois o Service recarrega o registro e o devolve ao Controller.

A resposta é **200 OK**, com o objeto atualizado. Trecho resumido:

```json
{
  "id": 8,
  "name": "Jogo de Teste",
  "price": "179.90",
  "stock": 3,
  "is_active": true
}
```

Os demais campos e relacionamentos também vêm na resposta. Nesta implementação inicial, PUT e PATCH apontam para a mesma ação e aceitam campos parciais. Atualizar o estoque no cadastro é diferente do fluxo de pagamento: nenhuma compra é criada por essa chamada.

### DELETE de desativação

Requisição, sem corpo:

```http
DELETE http://localhost:8000/api/products/8
Accept: application/json
```

A rota chama `destroy(int $id)` do Controller, que chama `delete($id)` do Service. O Service encontra o produto com `findOrFail()` e grava `is_active = false`; não executa uma exclusão física.

O Controller responde **204 No Content**. A área de resposta vazia é o resultado esperado: não existe JSON nessa resposta.

Um novo GET para o mesmo ID responde 200. Trecho resumido:

```json
{
  "id": 8,
  "name": "Jogo de Teste",
  "price": "179.90",
  "stock": 3,
  "is_active": false
}
```

Esse GET é outra chamada e demonstra que a linha continua no banco. Para reativar, faça PATCH com `{"is_active": true}`. Um DELETE de ID inexistente retorna 404.

### Onde o caminho para quando há erro

- **Hostname inválido:** com `www.localhost`, o erro de resolução pode acontecer no Thunder Client antes de alcançar o Laravel. Use `localhost` ou `127.0.0.1`.
- **Servidor inacessível:** se o Docker ou PHP estiver parado, o cliente não consegue obter uma resposta HTTP da aplicação.
- **ID inexistente:** o Service chama `findOrFail()` e o Laravel converte a exceção em 404, antes de atualizar ou excluir o registro.
- **Entrada inválida:** ainda não há validação HTTP de campos. Os FormRequests da etapa 7 passarão a devolver 422 para esses casos. Nesta fase, a entrada pode ser aceita ou provocar erro do banco.
- **Regra de exclusão de classificação:** nos outros três CRUDs, o Service pode retornar um erro de validação por existir produto associado. O Laravel o apresenta em JSON com status 422.
- **Falha durante a transação de produtos:** os dados da operação são desfeitos; não fica um produto parcialmente cadastrado. O teste provoca uma falha de chave estrangeira diretamente no Service para verificar essa garantia.

O formato de erro por campo, como `errors.stock`, será definido pela validação dos FormRequests na etapa 7. A regra de exclusão das classificações continua devolvendo 422 conforme o exemplo em `crud-catalog.md`.

## Verificação executada

- [x] Pint passou nos 11 arquivos PHP da revisão, incluindo Controllers, Services, rotas e testes.
- [x] Suíte atual com **17 testes aprovados, 254 asserções e 3 testes de validação ignorados**, em SQLite em memória.
- [x] Listagem, consulta, criação, criação sem gênero, atualização parcial e substituição de associações verificadas.
- [x] Desativação, reativação e preservação de vínculos verificadas.
- [x] IDs inexistentes verificados, com busca nos Services e resposta 404.
- [ ] Reativar os três testes de validação ao implementar os FormRequests na etapa 7.
- [x] Falha de chave estrangeira no Service desfez toda a criação, inclusive as associações já gravadas.
- [x] `route:list --path=api` mostrou as cinco rotas de produtos.
- [x] As gravações da suíte ocorreram somente no SQLite em memória, sem alterar os cadastros do MySQL.

O roteiro manual do Thunder Client acima fica para o estudante repetir. Antes da retirada da validação inline, a suíte tinha 20 testes aprovados e 336 asserções; esse resultado anterior não descreve a validação disponível agora. O resultado atual está em `docs/crud-catalog.md`.

Referências: [rotas no Laravel 12](https://laravel.com/docs/12.x/routing), [Controllers e apiResource](https://laravel.com/docs/12.x/controllers) e [validação](https://laravel.com/docs/12.x/validation).
