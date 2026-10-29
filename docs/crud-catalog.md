# Etapa 6 — CRUDs básicos do catálogo

Em 28/10/2026, o usuário autorizou implementar os CRUDs do catálogo juntos e explicar um deles por completo depois. Foram concluídos **produtos, categorias, plataformas e gêneros**. Resources e FormRequests permanecem na etapa 7; autenticação e permissões, na etapa 8.

Revisão de 29/10/2026: os Controllers seguem o exemplo fornecido pelo usuário, com IDs inteiros, variáveis antes das respostas e `$request->all()`. A validação de entrada foi retirada a pedido dele e será implementada nos FormRequests; nesta etapa, use corpos válidos. As restrições do banco continuam existindo, mas não substituem a validação HTTP.

## Estrutura

Cada cadastro tem um Controller e um Service próprios, seguindo o padrão de produtos, sem classes base, interfaces ou repositórios extras.

Os Controllers ficam em `app/Http/Controllers/Api/`, com namespace `App\Http\Controllers\Api`. Os Services continuam em `app/Services/`.

| Cadastro | Controller | Service | URL base |
| --- | --- | --- | --- |
| Produtos | `ProductController` | `ProductService` | `/api/products` |
| Categorias | `CategoryController` | `CategoryService` | `/api/categories` |
| Plataformas | `PlatformController` | `PlatformService` | `/api/platforms` |
| Gêneros | `GenreController` | `GenreService` | `/api/genres` |

Use `http://localhost:8000`, sem `www`. Configure `Accept: application/json`, sem `*/` antes do valor. Para enviar um corpo JSON, configure também `Content-Type: application/json`.

## As cinco ações de cada cadastro

Substitua `RESOURCE` por `products`, `categories`, `platforms` ou `genres`.

| Método e caminho | Controller | Service | Resposta |
| --- | --- | --- | --- |
| GET `/api/RESOURCE` | `index()` | `listAll()` | 200 e uma lista JSON. |
| GET `/api/RESOURCE/ID` | `show(int $id)` | `findById($id)` | 200 e um objeto; 404 se o ID não existir. |
| POST `/api/RESOURCE` | `store()` | `create($request->all())` | 201 e o objeto criado para dados válidos. |
| PATCH ou PUT `/api/RESOURCE/ID` | `update()` | `update($id, $request->all())` | 200 e o objeto atualizado; 404 se o ID não existir. |
| DELETE `/api/RESOURCE/ID` | `destroy()` | `delete()` | 204 sem corpo; a regra de exclusão está abaixo. |

As quatro chamadas `Route::apiResource()` em `routes/api.php` registram essas 20 rotas, com parâmetro `{id}`. O Controller passa o ID ao Service, que busca com `findOrFail()` e gera 404 quando não encontra. São rotas HTTP; as classes Resources para organizar o JSON serão estudadas na etapa 7.

## Categorias, plataformas e gêneros

Os três possuem o campo `name`. Para um POST em categorias, por exemplo:

```json
{
  "name": "Lançamentos"
}
```

O cadastro espera `name` como texto, até 255 caracteres e único na tabela. As regras de validação HTTP, incluindo a exceção do próprio registro na atualização, serão feitas nos FormRequests. Por enquanto, a coluna mantém a restrição de unicidade no banco; não espere resposta 422 de validação para um nome repetido.

A resposta inclui `id`, `name`, `created_at`, `updated_at` e `products_count`. `withCount()` calcula a quantidade de produtos sem carregar os produtos inteiros na listagem e na busca por ID; `loadCount()` acrescenta a contagem depois de criar ou atualizar.

Exemplo resumido de resposta com status 201, omitindo as datas apenas nesta explicação:

```json
{
  "id": 4,
  "name": "Lançamentos",
  "products_count": 0
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

O guia [crud-products.md](crud-products.md) contém os corpos de exemplo e o **percurso completo de cada requisição**, do Thunder Client até o MySQL e a resposta de volta. A API ainda está sem autenticação; ela será configurada na etapa 8.

## Verificações realizadas

- [x] Pint passou nos 11 arquivos PHP da revisão: quatro Controllers, quatro Services, rotas e dois arquivos de testes.
- [x] Suíte com **17 testes aprovados, 254 asserções e 3 testes de validação ignorados** em SQLite em memória.
- [x] Os três cadastros foram testados em criação, listagem, consulta, atualização e exclusão.
- [x] Renomeação com dados válidos verificada. Validação de nomes obrigatórios, tipos, tamanho e duplicação fica pendente para a etapa 7.
- [x] Exclusão de classificações utilizadas foi rejeitada, preservando os vínculos.
- [x] IDs inexistentes retornaram 404; `route:list --path=api` mostrou as 20 rotas.
- [x] Os cadastros e alterações dos testes ocorreram no SQLite em memória, sem gravar no MySQL.

Os três testes de validação estão preservados com `markTestSkipped()`; remover essa marcação ao implementar os FormRequests. Antes desta revisão, havia validação inline e a suíte tinha 20 testes aprovados. Esse resultado anterior não descreve a validação disponível agora.

Os testes estão em `tests/Feature/CatalogApiTest.php` e `tests/Feature/ProductApiTest.php`.

## Checklist manual no Thunder Client

- [ ] Usar `localhost`, sem `www`, e corrigir o header `Accept`.
- [ ] Executar GET nas quatro URLs base e conferir os dados.
- [ ] Criar uma categoria, uma plataforma e um gênero de teste; guardar os IDs retornados.
- [ ] Consultar os novos IDs e atualizar os nomes com PATCH.
- [ ] Na etapa 7, reativar os testes de validação e conferir nomes vazios ou repetidos com resposta 422.
- [ ] Excluir os registros de teste sem produtos e conferir 204; consultar novamente e conferir 404.
- [ ] Tentar excluir uma classificação utilizada e conferir 422, sem perda de dados.
- [ ] Repetir o ciclo de produtos do outro guia, incluindo desativação e consulta posterior.

Não foram criados CRUDs de contas, endereços, carrinho ou pedidos nesta entrega de catálogo. Esses fluxos ainda dependem da identidade do usuário, do histórico e das regras de estoque; não devem ser tratados como uma simples edição livre das tabelas. Não iniciar outra etapa sem o usuário pedir.
