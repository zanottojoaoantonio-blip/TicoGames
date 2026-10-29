# Etapa 6 — CRUDs básicos do catálogo

Em 28/10/2026, o usuário autorizou implementar os CRUDs do catálogo juntos e explicar um deles por completo depois. Foram concluídos **produtos, categorias, plataformas e gêneros**. Resources e FormRequests permanecem na etapa 7; autenticação e permissões, na etapa 8.

## Estrutura

Cada cadastro tem um Controller e um Service próprios, seguindo o padrão de produtos, sem classes base, interfaces ou repositórios extras.

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
| GET `/api/RESOURCE` | `index()` | `getAll()` | 200 e uma lista JSON. |
| GET `/api/RESOURCE/ID` | `show()` | `getOne()` | 200 e um objeto; 404 se o ID não existir. |
| POST `/api/RESOURCE` | `store()` | `create()` | 201 e o objeto criado; 422 para entrada inválida. |
| PATCH ou PUT `/api/RESOURCE/ID` | `update()` | `update()` | 200 e o objeto atualizado; 404 ou 422 em caso de erro. |
| DELETE `/api/RESOURCE/ID` | `destroy()` | `delete()` | 204 sem corpo; a regra de exclusão está abaixo. |

`Route::apiResources()` em `routes/api.php` registra essas 20 rotas. São rotas HTTP; as classes Resources para organizar o JSON serão estudadas na etapa 7.

## Categorias, plataformas e gêneros

Os três possuem o campo `name`. Para um POST em categorias, por exemplo:

```json
{
  "name": "Lançamentos"
}
```

O nome é obrigatório, deve ser texto, ter no máximo 255 caracteres e ser único na respectiva tabela. Na atualização, o próprio registro é ignorado na busca por duplicação: manter o nome atual é permitido.

A resposta inclui `id`, `name`, `created_at`, `updated_at` e `products_count`. `withCount()` calcula a quantidade de produtos sem carregar os produtos inteiros na listagem; `loadCount()` faz isso no detalhe.

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

- [x] Pint passou nos oito arquivos desta ampliação: seis classes novas, rotas e teste.
- [x] Suíte com **20 testes e 336 asserções** passou em SQLite em memória.
- [x] Os três cadastros foram testados em criação, listagem, consulta, atualização e exclusão.
- [x] Nomes obrigatórios, tipos, tamanho máximo, duplicação e renomeação foram verificados.
- [x] Exclusão de classificações utilizadas foi rejeitada, preservando os vínculos.
- [x] IDs inexistentes retornaram 404; `route:list --path=api` mostrou as 20 rotas.
- [x] No MySQL, as quatro listagens responderam 200, com 7 produtos, 3 categorias, 3 plataformas e 4 gêneros. POSTs vazios foram rejeitados com 422.
- [x] As verificações HTTP não alteraram os dados de demonstração.

Os testes estão em `tests/Feature/CatalogApiTest.php` e `tests/Feature/ProductApiTest.php`.

## Checklist manual no Thunder Client

- [ ] Usar `localhost`, sem `www`, e corrigir o header `Accept`.
- [ ] Executar GET nas quatro URLs base e conferir os dados.
- [ ] Criar uma categoria, uma plataforma e um gênero de teste; guardar os IDs retornados.
- [ ] Consultar os novos IDs e atualizar os nomes com PATCH.
- [ ] Repetir um nome e conferir 422.
- [ ] Excluir os registros de teste sem produtos e conferir 204; consultar novamente e conferir 404.
- [ ] Tentar excluir uma classificação utilizada e conferir 422, sem perda de dados.
- [ ] Repetir o ciclo de produtos do outro guia, incluindo desativação e consulta posterior.

Não foram criados CRUDs de contas, endereços, carrinho ou pedidos nesta entrega de catálogo. Esses fluxos ainda dependem da identidade do usuário, do histórico e das regras de estoque; não devem ser tratados como uma simples edição livre das tabelas. Não iniciar outra etapa sem o usuário pedir.
