# CRUDs do catálogo — etapas 6 e 7

> Estado após a integração de 03/11/2026: o backend já possui Sanctum e fluxos de contas, endereços, carrinho e pedidos. Escritas no catálogo exigem sessão e perfil admin; consultas públicas de produtos mostram somente ativos. Consulte [authentication.md](authentication.md) e [account-checkout.md](account-checkout.md) antes dos testes manuais. Os números de testes anteriores a esta integração são históricos.

Os CRUDs de **produtos, categorias, plataformas e gêneros** foram implementados na etapa 6. Em 03/11/2026, a etapa 7 acrescentou os FormRequests e API Resources. Autenticação e permissões da etapa 8 já estão implementadas.

Os Controllers seguem o exemplo do usuário, com IDs inteiros e variáveis antes do retorno. Agora recebem FormRequests específicos e repassam `$request->validated()` ao Service. Os Resources organizam as respostas de sucesso com a chave externa `data`. Veja a explicação das classes em [requests-resources.md](requests-resources.md).

## Estrutura

Cada cadastro tem um Controller e um Service próprios, seguindo o padrão de produtos, sem classes base, interfaces ou repositórios extras.

Os Controllers ficam em `api/app/Http/Controllers/Api/`, com namespace `App\Http\Controllers\Api`. Os Services continuam em `api/app/Services/`.

| Cadastro | Controller | Service | URL base |
| --- | --- | --- | --- |
| Produtos | `ProductController` | `ProductService` | `/api/products` |
| Categorias | `CategoryController` | `CategoryService` | `/api/categories` |
| Plataformas | `PlatformController` | `PlatformService` | `/api/platforms` |
| Gêneros | `GenreController` | `GenreService` | `/api/genres` |

Use `http://localhost`, sem `www`. Configure `Accept: application/json`, sem `*/` antes do valor. Para enviar um corpo JSON, configure também `Content-Type: application/json`.

## As cinco ações de cada cadastro

Substitua `RESOURCE` por `products`, `categories`, `platforms` ou `genres`.

| Método e caminho | Controller | Service | Resposta |
| --- | --- | --- | --- |
| GET `/api/RESOURCE` | `index()` | `listAll()` | 200 e uma lista em `data`. |
| GET `/api/RESOURCE/ID` | `show(int $id)` | `findById($id)` | 200 e um objeto em `data`; 404 se o ID não existir. |
| POST `/api/RESOURCE` | `store()` | `create($request->validated())` | 201 e o objeto criado em `data`; entrada inválida gera 422. |
| PATCH ou PUT `/api/RESOURCE/ID` | `update()` | `update($id, $request->validated())` | 200 e o objeto atualizado em `data`; 404 ou 422 em caso de erro. |
| DELETE `/api/RESOURCE/ID` | `destroy()` | `delete()` | 204 sem corpo; a regra de exclusão está abaixo. |

As quatro chamadas `Route::apiResource()` em `api/routes/api.php` registram essas 20 rotas, com parâmetro `{id}`. O Controller passa o ID ao Service, que busca com `findOrFail()` e gera 404 quando não encontra. Na atualização, o FormRequest também confirma a existência antes de validar os campos. `apiResource()` registra rotas; as classes em `api/app/Http/Resources` organizam o JSON.

## Categorias, plataformas e gêneros

Os três possuem o campo `name`. Para um POST em categorias, por exemplo:

```json
{
  "name": "Lançamentos"
}
```

O FormRequest exige `name` preenchido, textual, até 255 caracteres e único na tabela. Na atualização, `sometimes` permite omitir o campo, e `ignore()` permite manter o nome do próprio registro. Nome vazio, inválido ou utilizado por outro registro gera 422.

A resposta inclui `id`, `name`, `created_at`, `updated_at` e `products_count`. `withCount()` calcula a quantidade de produtos sem carregar os produtos inteiros na listagem e na busca por ID; `loadCount()` acrescenta a contagem depois de criar ou atualizar.

Exemplo resumido de resposta com status 201, omitindo as datas apenas nesta explicação:

```json
{
  "data": {
    "id": 4,
    "name": "Lançamentos",
    "products_count": 0
  }
}
```

O ID é gerado pelo banco; utilize o valor realmente devolvido nas próximas requisições. Para renomear, faça PATCH na URL com esse ID:

```json
{
  "name": "Novidades"
}
```

A resposta é 200, com o mesmo ID e o novo nome. Um DELETE de cadastro não utilizado responde 204 sem corpo; consultá-lo depois responde 404.

### Exclusão de classificações utilizadas

Poderíamos remover associações automaticamente, mas uma categoria poderia desaparecer de vários produtos, inclusive deixando um produto sem categoria. Plataforma é obrigatória e possui restrição no banco. Escolhemos uma regra simples para os três cadastros: **não excluir enquanto existirem produtos associados**, mesmo que estejam inativos.

O Service consulta `products()->exists()`. Se houver vínculo, lança `ValidationException`, que o Laravel transforma em 422. A chave de erro é `category`, `platform` ou `genre`.

Exemplo ao tentar excluir uma categoria utilizada:

```json
{
  "message": "Não é possível excluir uma categoria que possui produtos associados.",
  "errors": {
    "category": [
      "Não é possível excluir uma categoria que possui produtos associados."
    ]
  }
}
```

Primeiro ajuste os produtos pelo seu CRUD. Cada produto deve continuar com ao menos uma categoria e uma plataforma; gêneros podem ficar vazios. Desativar um produto preserva seus vínculos e não libera a exclusão da classificação.

## Produtos e percurso das requisições

Produto é desativado com `is_active = false`, preservando os vínculos e o histórico. A listagem administrativa inicial inclui ativos e inativos.

O guia [crud-products.md](crud-products.md) contém os corpos de exemplo e o **percurso completo de cada requisição**, do Thunder Client até o MySQL e a resposta de volta. As rotas de escrita exigem autenticação e perfil admin; consulte o guia de autenticação.

## Verificações realizadas

- [x] Pint foi aplicado nos arquivos combinados nesta integração: Requests, Resources, Controllers, comentários de Services e testes.
- [x] Suíte com **44 testes aprovados e 664 asserções**, sem testes ignorados, em SQLite em memória.
- [x] Os três cadastros foram testados em criação, listagem, consulta, atualização e exclusão.
- [x] Nomes obrigatórios, tipos, tamanho, duplicação e renomeação mantendo o próprio nome verificados.
- [x] Respostas com `data` e relacionamentos de produtos sem `pivot` verificados.
- [x] Exclusão de classificações utilizadas foi rejeitada, preservando os vínculos.
- [x] IDs inexistentes retornaram 404; `route:list --path=api` mostrou as 20 rotas.
- [x] Os cadastros e alterações dos testes ocorreram no SQLite em memória, sem gravar no MySQL.

Os três testes de validação antes ignorados foram reativados nesta etapa. As respostas 422 usam `message` e `errors`, sem a chave externa `data` dos Resources.

Os testes estão em `api/tests/Feature/CatalogApiTest.php` e `api/tests/Feature/ProductApiTest.php`.

## Checklist manual no Thunder Client

- [ ] Usar `localhost`, sem `www`, e corrigir o header `Accept`.
- [ ] Executar GET nas quatro URLs base e conferir os dados.
- [ ] Criar uma categoria, uma plataforma e um gênero de teste; guardar os IDs retornados.
- [ ] Consultar os novos IDs e atualizar os nomes com PATCH.
- [ ] Conferir a chave `data` nos sucessos e nomes vazios ou repetidos com resposta 422.
- [ ] Excluir os registros de teste sem produtos e conferir 204; consultar novamente e conferir 404.
- [ ] Tentar excluir uma classificação utilizada e conferir 422, sem perda de dados.
- [ ] Repetir o ciclo de produtos do outro guia, incluindo desativação e consulta posterior.

Os fluxos de contas, endereços, carrinho e pedidos foram integrados com o commit remoto e estão descritos em `account-checkout.md`. A próxima etapa são as telas em HTML, JavaScript, CSS e Bootstrap, somente quando solicitado.
