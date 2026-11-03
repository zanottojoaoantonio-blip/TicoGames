# Etapa 7 — FormRequests e API Resources

> Etapa 8 implementada em 02/11/2026: consultas públicas do catálogo preservadas; alterações exigem sessão e perfil admin. Veja [authentication.md](authentication.md) e [account-checkout.md](account-checkout.md) para o estado atual.


Implementada em 02/11/2026. Validação automatizada concluída: 23 testes aprovados e 369 asserções em SQLite em memória.

## Entrada

Os oito arquivos em `app/Http/Requests` separam cadastro (Store) de atualização (Update). O Laravel executa authorize() e rules() antes da ação do Controller. Erros retornam 422 com message e errors quando Accept é application/json. authorize() retorna true; autenticação e permissões continuam na etapa 8.

Os Controllers agora recebem o FormRequest correspondente e chamam `$request->validated()`, enviando apenas os campos validados aos Services. `sometimes` valida o campo se enviado; `required` impede valor vazio. `Rule::unique(...)->ignore($this->route('id'))` permite manter o próprio nome na edição. PUT e PATCH continuam parciais.

Produtos exigem nome, descrição, plataforma, preço, caminho da imagem, estoque e ao menos uma categoria no cadastro. Gêneros e is_active são opcionais. Categorias e gêneros usam regras com `.*` para cada elemento: integer, distinct e exists. Gêneros aceitam lista vazia, categorias não. Imagem continua sendo caminho, sem upload.

## Saída

Os quatro arquivos em `app/Http/Resources` definem o JSON pelo método toArray(). `new ProductResource($product)` apresenta um produto; `ProductResource::collection($products)` apresenta uma lista. Ambas as respostas têm data na raiz. Cadastro continua 201, consultas e atualização 200, exclusão 204 sem corpo. Erros não usam esse envelope de sucesso.

Exemplo de categoria:

```json
{"data":{"id":4,"name":"Acessórios","products_count":0}}
```

Produtos expõem id, name, description, price, image_path, stock, is_active e relações platform, categories e genres. Cada classificação traz id e name e, quando carregada, products_count. Sem timestamps, pivot ou platform_id redundante. `whenLoaded` só inclui relações carregadas; `whenCounted` só inclui contagens carregadas. Os Services já fazem esse carregamento.

## Fluxo

Thunder Client → rota → FormRequest → Controller → Service → Model/banco → Service → Controller → Resource → JSON. Erro de validação interrompe antes do Controller. Regras de exclusão com vínculos continuam nos Services. Os guias antigos de CRUD descrevem o contrato anterior; este documento substitui as partes sobre validação e formato da resposta.

## Verificação

- [x] Sintaxe PHP verificada nos Requests, Resources, Controllers e testes.
- [x] Suíte executada em SQLite em memória: `docker compose exec php php artisan test`.
- [x] Pint aplicado aos 24 arquivos PHP alterados.
- [ ] No Thunder Client, POST válido retorna 201 com data; GET retorna 200 com data.
- [ ] POST vazio, nome duplicado e produto inválido retornam 422.
- [ ] PATCH preserva campos omitidos e permite manter o nome atual.
- [ ] genre_ids vazio remove gêneros; category_ids vazio é rejeitado.
- [ ] DELETE continua 204 e preserva as regras de negócio existentes.

Após a implementação, o usuário pediu iniciar o Docker. PHP e MySQL estão saudáveis; os 23 testes passaram antes e depois do Pint, sem testes ignorados. Os testes não gravaram no MySQL. O checklist manual no Thunder Client permanece disponível para o estudante.
