# Etapa 7 — FormRequests e API Resources

> Estado após a integração de 03/11/2026: o backend já possui Sanctum e fluxos de contas, endereços, carrinho e pedidos. Escritas no catálogo exigem sessão e perfil admin; consultas públicas de produtos mostram somente ativos. Consulte [authentication.md](authentication.md) e [account-checkout.md](account-checkout.md) antes dos testes manuais. Os números de testes anteriores a esta integração são históricos.

Concluída em 03/11/2026 para produtos, categorias, plataformas e gêneros. Os Controllers continuam em `api/app/Http/Controllers/Api`, recebem IDs inteiros e guardam o resultado do Service em uma variável antes de retornar.

## Responsabilidades

Um **FormRequest** define quais dados são aceitos. O Laravel o valida antes de executar a ação do Controller. Se a entrada for inválida e a requisição pedir JSON, responde 422 com os campos em `errors`.

Um **API Resource** define quais campos de um Model serão apresentados na resposta. Ele transforma os dados para JSON; não cria tabelas, não valida a entrada e não substitui o Service.

O **Controller** recebe a entrada já validada, chama o Service e retorna o Resource. O **Service** continua cuidando de consultas, gravações, transações e regras de exclusão.

## Arquivos

As classes de entrada estão em `api/app/Http/Requests/` e as de saída em `api/app/Http/Resources/`.

| Cadastro | Criação | Atualização | Resposta |
| --- | --- | --- | --- |
| Categorias | `StoreCategoryRequest` | `UpdateCategoryRequest` | `CategoryResource` |
| Plataformas | `StorePlatformRequest` | `UpdatePlatformRequest` | `PlatformResource` |
| Gêneros | `StoreGenreRequest` | `UpdateGenreRequest` | `GenreResource` |
| Produtos | `StoreProductRequest` | `UpdateProductRequest` | `ProductResource` |

Usamos uma classe para criar e outra para atualizar: os campos obrigatórios no cadastro podem ser omitidos na atualização parcial. Classes próprias por entidade mantêm cada regra fácil de localizar; não foram criadas classes base adicionais.

## Como ler um FormRequest

`extends FormRequest` utiliza o mecanismo de validação do Laravel. `authorize()` retorna `true`; os middlewares das rotas verificam a sessão e o perfil necessário antes de validar os campos.

`rules()` devolve um array de campos e regras. No cadastro de categoria, por exemplo:

```php
// Exige nome preenchido, textual, com limite de tamanho e único na tabela.
'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
```

Na atualização, `sometimes` permite omitir o campo, enquanto `required` impede enviá-lo vazio. PUT e PATCH continuam aceitando atualização parcial.

`$this->route('id')` lê o parâmetro da URL, pois `$this` representa o objeto FormRequest atual. As classes de atualização encontram o registro com `findOrFail()` antes de validar os campos; um ID inexistente responde 404, mesmo que o corpo seja inválido.

Nos três cadastros de classificação, `Rule::unique(...)->ignore($record)` desconsidera o próprio registro ao procurar duplicação. Assim, é possível manter o nome atual, mas não adotar o nome de outro registro. O Model usado em `ignore()` vem da busca no banco.

Produtos exigem nome, descrição, imagem, plataforma existente, preço não negativo com até duas casas, estoque inteiro não negativo e ao menos uma categoria. Cada ID das listas precisa existir e não pode se repetir. Gêneros são opcionais: omitir mantém os vínculos na atualização; enviar `[]` os remove.

## Como o Controller usa a entrada

Trecho do `CategoryController`:

```php
// O Laravel valida este tipo de Request antes de entrar no método.
public function store(StoreCategoryRequest $request)
{
    // validated() entrega somente os campos cobertos pela validação.
    $category = $this->categoryService->create($request->validated());

    // O Resource escolhe os campos; o status 201 informa a criação.
    return (new CategoryResource($category))->response()->setStatusCode(201);
}
```

`all()` devolveria toda a entrada. `validated()` devolve os campos aprovados pelas regras. Os campos `$fillable` dos Models e as restrições do banco continuam existindo, com suas responsabilidades próprias.

## Como ler um Resource

`extends JsonResource` utiliza a transformação de respostas do Laravel. `toArray()` lista os campos apresentados. Dentro do Resource, `$this->name` acessa o nome do Model que ele está representando.

`CategoryResource::collection($categories)` transforma uma coleção; `new CategoryResource($category)` transforma um único registro. A resposta de sucesso usa a chave externa `data`, padrão do Laravel.

Categorias, gêneros e plataformas apresentam `id`, `name`, datas e `products_count` quando a contagem foi carregada. `whenCounted('products')` omite essa chave quando a contagem não está disponível, sem consultar os produtos por conta própria.

`ProductResource` apresenta as colunas do produto e usa os outros Resources nos relacionamentos. `whenLoaded()` utiliza apenas relacionamentos já carregados pelo Service. Os dados internos de `pivot` não aparecem. O preço permanece texto decimal, estoque é inteiro e `is_active` é booleano, conforme os casts do Model.

As datas são formatadas com `toISOString()`. O operador `?->` só chama o método quando a data não é nula.

## Requisições e respostas

Use `http://localhost`, `Accept: application/json` e, ao enviar corpo JSON, `Content-Type: application/json`.

POST `/api/categories`:

```json
{"name": "Lançamentos"}
```

Resposta 201 resumida; datas omitidas e ID apenas ilustrativo:

```json
{
  "data": {
    "id": 4,
    "name": "Lançamentos",
    "products_count": 0
  }
}
```

GET de listagem retorna `{"data": [...]}`. GET individual e PATCH/PUT retornam `{"data": {...}}`, com status 200. Use o ID efetivamente devolvido no cadastro.

Erros 422 continuam usando `message` e `errors`, sem uma chave externa `data`. As mensagens padrão seguem o idioma instalado no Laravel. DELETE continua respondendo 204 sem corpo.

O caminho de cadastro é: Thunder Client → rota → FormRequest → Controller → Service → Model/MySQL → Service → Controller → Resource → resposta JSON. Uma falha de validação interrompe o caminho antes do Controller e da gravação.

## Verificações executadas

- [x] Os três testes antes ignorados foram reativados.
- [x] Suíte com **44 testes aprovados e 664 asserções**, sem testes ignorados, em SQLite em memória.
- [x] Cadastro, consulta, atualização, exclusão e desativação continuam funcionando.
- [x] Campos obrigatórios, nomes únicos, atualização mantendo o próprio nome e valores inválidos retornam o resultado esperado.
- [x] Atualizações inválidas preservam preço, estoque e associações.
- [x] Resources apresentam `data`, mantêm os tipos e omitem `pivot`.
- [x] IDs inexistentes retornam 404; falha na transação de produtos continua desfazendo a gravação.
- [x] Pint foi aplicado nos arquivos combinados nesta integração.

As gravações dos testes ocorreram somente no SQLite em memória. Não foram executados migrations ou seeders no MySQL nesta etapa.

## Checklist manual no Thunder Client

- [ ] Listar os quatro cadastros e conferir a chave `data`.
- [ ] Criar uma categoria válida, guardar `data.id` e receber 201.
- [ ] Enviar nome vazio ou repetido e conferir 422 com `errors.name`.
- [ ] Atualizar mantendo o próprio nome e receber 200.
- [ ] Enviar estoque negativo ou categorias vazias para um produto e conferir 422 sem alterações.
- [ ] Consultar um produto e conferir os relacionamentos sem `pivot`.
- [ ] Excluir uma classificação sem vínculos e receber 204 sem corpo.

O próximo passo é a etapa 9 (telas em HTML, JavaScript, CSS e Bootstrap), somente quando o usuário solicitar.

Referências: [FormRequests](https://laravel.com/docs/12.x/validation#form-request-validation) e [API Resources](https://laravel.com/docs/12.x/eloquent-resources).
