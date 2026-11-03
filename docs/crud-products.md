# CRUD de produtos — etapas 6 e 7

Atualizado em 03/11/2026. O CRUD de produtos utiliza Controller, Service, FormRequests e API Resource. O Controller mantém o estilo solicitado: namespace `Api`, IDs inteiros e variáveis antes do retorno.

## Conceitos antes do código

CRUD significa criar, consultar, atualizar e excluir. Neste projeto, consultar tem duas ações: listar todos e buscar por ID. Excluir um produto significa desativá-lo com `is_active = false`, preservando seu cadastro e seus vínculos.

| Parte | Responsabilidade |
| --- | --- |
| Rota | Associa método HTTP e URL à ação do Controller. |
| FormRequest | Valida a entrada antes de executar a ação de cadastro ou atualização. |
| Controller | Recebe o ID e os dados validados, chama o Service e retorna o Resource. |
| Service | Consulta, grava, associa e desativa os produtos. |
| Model | Usa o Eloquent para representar os registros e relacionamentos. |
| Resource | Escolhe os campos apresentados na resposta JSON. |

O caminho de cadastro é: Thunder Client → rota → FormRequest → Controller → Service → Model/MySQL. Na volta, o Service entrega o Model ao Controller, que retorna um Resource para montar o JSON.

Usamos Services para concentrar as operações e as associações em um lugar reutilizável. Cada entidade tem seu próprio Controller e Service; não foram criadas interfaces, repositórios ou classes base adicionais.

## Arquivos envolvidos

| Arquivo | Responsabilidade |
| --- | --- |
| `public/index.php` | Carrega o Laravel e entrega a requisição à aplicação. |
| `bootstrap/app.php` | Carrega a API com o prefixo `/api`. |
| `routes/api.php` | Registra as cinco ações por `Route::apiResource()`, com `{id}`. |
| `app/Http/Controllers/Api/ProductController.php` | Liga a entrada HTTP ao Service e ao Resource. |
| `app/Http/Requests/StoreProductRequest.php` | Valida a criação. |
| `app/Http/Requests/UpdateProductRequest.php` | Confirma o ID e valida a atualização. |
| `app/Services/ProductService.php` | Executa as operações do produto. |
| `app/Http/Resources/ProductResource.php` | Define as colunas e relacionamentos enviados em `data`. |
| `tests/Feature/ProductApiTest.php` | Verifica o CRUD, a validação e o rollback da transação. |

Autenticação e permissões permanecem na etapa 8. Os FormRequests retornam `true` em `authorize()` nesta etapa. O guia [requests-resources.md](requests-resources.md) explica as classes de entrada e saída.

## Como ler o Controller

O Laravel injeta `ProductService` pelo construtor. A propriedade `$this->productService` guarda esse objeto para ser usado nas ações.

`index()` chama `listAll()`, `show(int $id)` chama `findById($id)`, `store()` chama `create()`, `update()` chama `update()` e `destroy()` chama `delete()`.

Cadastro e atualização recebem Requests específicos. O Laravel valida a entrada antes de executar os respectivos métodos. Dentro deles, `$request->validated()` retorna os campos aprovados pelas regras.

```php
// O Request já foi validado quando o Laravel entra no método.
public function store(StoreProductRequest $request)
{
    // Envia ao Service somente os campos validados.
    $product = $this->productService->create($request->validated());

    // Seleciona a saída e informa que o recurso foi criado.
    return (new ProductResource($product))->response()->setStatusCode(201);
}
```

`show()`, `update()` e `destroy()` recebem IDs inteiros. Os Services utilizam `findOrFail()` para localizar os registros. Na atualização, o FormRequest também faz essa busca antes da validação: um ID inexistente gera 404, mesmo com corpo inválido. Não há route model binding implícito de `Product` neste padrão.

## Entrada: regras dos FormRequests

No cadastro, são obrigatórios nome, descrição, caminho da imagem, plataforma, preço, estoque e uma lista de categorias com pelo menos um ID. A plataforma e os IDs das listas precisam existir. IDs repetidos são rejeitados.

O preço deve ser não negativo, ter até duas casas decimais e caber em `decimal(10,2)`. O estoque deve ser inteiro entre zero e `4294967295`. `is_active` é opcional no cadastro e recebe o padrão verdadeiro do banco quando omitido.

Gêneros são opcionais: consoles podem não ter nenhum. O preço deve usar ponto decimal. A imagem é um caminho de arquivo; este CRUD não implementa upload.

PUT e PATCH aceitam atualização parcial. `sometimes` permite omitir campos; `required` impede enviar vazios aqueles que precisam de valor. Listas enviadas substituem as associações atuais. Omitir a lista mantém seus vínculos; `genre_ids: []` remove os gêneros. Categorias vazias são rejeitadas.

Uma falha de validação retorna 422 antes da ação e da gravação. Os campos `$fillable` do Model e as restrições do banco continuam existindo.

## Operações do Service

- `listAll()` ordena os produtos pelo ID e carrega plataforma, categorias e gêneros com `with()`.
- `findById(int $id)` usa `findOrFail()` com os relacionamentos; se não encontrar, gera a exceção que o Laravel apresenta como 404.
- `create(array $data)` separa `category_ids` e `genre_ids`, grava as colunas do produto e associa os IDs.
- `update(int $id, array $data)` encontra o produto, altera os campos e substitui apenas as listas enviadas.
- `delete(int $id)` encontra o produto e grava `is_active = false`.

`DB::transaction()` mantém a gravação do produto e dos vínculos na mesma operação. Se uma associação falhar, todas as gravações daquela operação são desfeitas.

`sync()` grava exatamente as associações enviadas no CRUD. Nos seeders usamos `syncWithoutDetaching()`, que acrescenta vínculos sem remover os existentes.

Depois de criar ou atualizar, o Service chama `findById()` para reler as colunas e os relacionamentos, incluindo padrões definidos pelo banco.

## Saída: ProductResource

As respostas de sucesso possuem a chave externa `data`. Na listagem ela contém um array; na consulta, criação e atualização contém um objeto. Os Resources relacionados não criam novas camadas `data` dentro dos relacionamentos.

O Resource apresenta ID, plataforma, nome, descrição, preço, imagem, estoque, ativo, datas e os relacionamentos carregados. As tabelas intermediárias não aparecem como `pivot`.

`whenLoaded()` utiliza os relacionamentos carregados pelo Service. Preço permanece texto decimal com duas casas, estoque é inteiro e ativo é booleano, seguindo os casts do Model.

## Testar no Thunder Client

Use `http://localhost:8000`, sem `www`. Configure `Accept: application/json` e, ao enviar corpo JSON, `Content-Type: application/json`. Os erros usam `message` e `errors`; não recebem a chave externa `data`.

| Método e caminho | Ação | Sucesso |
| --- | --- | --- |
| GET `/api/products` | Listar | 200 com `data` como array. |
| GET `/api/products/ID` | Consultar | 200 com `data` como objeto. |
| POST `/api/products` | Criar | 201 com o objeto em `data`. |
| PATCH ou PUT `/api/products/ID` | Atualizar | 200 com o objeto em `data`. |
| DELETE `/api/products/ID` | Desativar | 204 sem corpo. |

### 1. Listagem

Envie GET para `/api/products`. O Laravel encontra `index()`, o Controller chama `listAll()` do Service e o Eloquent consulta os produtos e seus relacionamentos. A coleção volta ao Controller, que retorna `ProductResource::collection($products)`.

Resposta resumida: apenas dois produtos e alguns campos; a resposta real tem datas, outros campos e relacionamentos.

```json
{
  "data": [
    {"id": 1, "name": "Elden Ring", "price": "299.90", "stock": 8},
    {"id": 2, "name": "Elden Ring", "price": "279.90", "stock": 6}
  ]
}
```

Esta listagem administrativa inicial inclui ativos e inativos. Não há paginação ou filtros nesta etapa.

### 2. Cadastro

Confira os IDs de plataformas e categorias existentes. Envie POST para `/api/products`, em Body → JSON:

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

O Laravel resolve `StoreProductRequest`. Se os dados forem válidos, executa `store()`, que chama `create()` do Service com `validated()`. O Service grava o produto e os vínculos em uma transação e relê o resultado. O Controller retorna o Resource com status 201.

Resposta resumida, com ID 8 apenas ilustrativo; foram omitidos descrição, imagem, datas e parte dos campos dos relacionamentos:

```json
{
  "data": {
    "id": 8,
    "name": "Jogo de Teste",
    "platform_id": 1,
    "price": "199.90",
    "stock": 5,
    "is_active": true,
    "platform": {"id": 1, "name": "PlayStation 5"},
    "categories": [{"id": 1, "name": "Jogos"}],
    "genres": []
  }
}
```

Guarde o valor de `data.id` realmente devolvido. O banco gera o ID; ele não é garantidamente 8. Um POST cria outro produto, sem a busca prévia por nome dos seeders.

### 3. Consulta por ID

Envie GET para `/api/products/ID`, substituindo ID pelo valor salvo. A rota chama `show(int $id)`, que pede `findById($id)` ao Service. A busca carrega os relacionamentos e devolve o Model; o Controller retorna `new ProductResource($product)` com status 200.

O formato é o mesmo objeto em `data` mostrado acima. Se o ID não existir, `findOrFail()` interrompe a busca e o Laravel devolve 404.

### 4. Atualização

Envie PATCH para `/api/products/ID` com:

```json
{"price": "179.90", "stock": 3}
```

O Laravel resolve `UpdateProductRequest`, que encontra o ID e valida os campos. O Controller recebe o ID e chama `update($id, $request->validated())` do Service.

O Service altera preço e estoque dentro da transação e mantém categorias e gêneros, pois as listas não foram enviadas. Ele relê o cadastro e o Controller o apresenta pelo Resource com status 200.

Trecho resumido; os demais campos e relacionamentos também são enviados:

```json
{
  "data": {"id": 8, "name": "Jogo de Teste", "price": "179.90", "stock": 3, "is_active": true}
}
```

Atualizar estoque no cadastro não cria compra ou pagamento. O fluxo de pedidos ainda será implementado.

### 5. Desativação e reativação

Envie DELETE para `/api/products/ID`. O Controller chama `delete($id)` do Service, que encontra o produto e grava `is_active = false`. O Controller devolve **204 sem corpo**; não existe JSON nessa resposta.

Consulte o mesmo ID com GET. Ele continua respondendo 200 com o objeto em `data`, agora com `is_active: false`. Os vínculos permanecem no banco. Para reativar, envie PATCH com `{"is_active": true}`.

### 6. Erros

- ID inexistente em GET, PATCH, PUT ou DELETE: 404.
- POST sem os campos obrigatórios: 422.
- Preço negativo, mais de duas casas ou maior que `99999999.99`: 422.
- Estoque negativo, fracionário ou maior que `4294967295`: 422.
- Plataforma ou IDs das listas inexistentes, categorias vazias e IDs repetidos: 422.

Exemplo de erro de estoque, com texto ilustrativo conforme o idioma instalado no Laravel:

```json
{
  "message": "The stock field must be at least 0.",
  "errors": {"stock": ["The stock field must be at least 0."]}
}
```

Os erros não têm a chave externa `data`. O header `Accept` solicita JSON; as mensagens padrão podem aparecer em inglês.

## Onde o percurso para

- Hostname inválido ou servidor parado: a chamada pode falhar no cliente, sem uma resposta da aplicação.
- ID inexistente: a busca com `findOrFail()` gera 404. Na atualização isso ocorre no FormRequest antes da validação; em consulta e exclusão, no Service.
- Dados inválidos: o FormRequest gera 422 antes de executar a ação do Controller.
- Classificação utilizada: nos outros três CRUDs, a regra do Service bloqueia a exclusão com 422.
- Falha na gravação de associações: a transação desfaz as gravações daquela operação.

## Verificações executadas

- [x] Pint passou nos 21 arquivos PHP da etapa 7.
- [x] Suíte com **21 testes aprovados e 480 asserções**, sem testes ignorados, em SQLite em memória.
- [x] Listagem, consulta, criação com e sem gêneros, atualização parcial e substituição de associações.
- [x] Desativação, reativação e preservação de vínculos.
- [x] Validação de criação e atualização; entradas inválidas preservam os dados.
- [x] Respostas com `data`, tipos preservados e relacionamentos sem `pivot`.
- [x] IDs inexistentes respondem 404, mesmo com corpo de atualização inválido.
- [x] Falha de chave estrangeira diretamente no Service provoca rollback de toda a criação.

As gravações da suíte ocorreram no SQLite em memória, sem alterar o MySQL. Os IDs e valores dos exemplos são ilustrativos; o roteiro manual fica para o estudante repetir.

## Checklist manual

- [ ] Listar os produtos e identificar os IDs das associações existentes.
- [ ] Criar um produto e guardar `data.id`.
- [ ] Consultar esse ID e conferir relacionamentos sem `pivot`.
- [ ] Atualizar apenas preço e estoque e conferir que as listas foram preservadas.
- [ ] Enviar uma atualização inválida e receber 422 sem alterações.
- [ ] Desativar, consultar o mesmo ID e depois reativar.

Referências: [FormRequests](https://laravel.com/docs/12.x/validation#form-request-validation) e [API Resources](https://laravel.com/docs/12.x/eloquent-resources).
