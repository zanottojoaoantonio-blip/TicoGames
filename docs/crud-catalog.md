# CRUD de categorias, plataformas e gêneros

Os três cadastros usam o mesmo percurso: Route → middleware → FormRequest → Controller → Service → Model → Resource. O catálogo público pode consultar; somente admin pode gravar.

| Método e rota | Resposta |
| --- | --- |
| GET /api/categories, /api/platforms, /api/genres | 200, lista em data |
| POST nas mesmas rotas | 201, registro criado em data |
| GET /api/{recurso}/{id} | 200, registro em data |
| PATCH/PUT /api/{recurso}/{id} | 200, registro atualizado |
| DELETE /api/{recurso}/{id} | 204 sem corpo |

Use IDs reais obtidos nas consultas. Nome deve ser texto não vazio, com limite de 255 e único na tabela. Atualização ignora o próprio registro na verificação de unicidade. ID inexistente retorna 404; nome inválido/repetido retorna 422.

Services carregam products_count, ordenam nomes e impedem excluir classificações associadas a produtos, incluindo inativos. Esse impedimento retorna 422 para preservar os vínculos. Sem produtos associados, a exclusão é física.

Categorias se relacionam diretamente por products.category_id. Plataforma também é FK direta; gêneros usam genre_product. A tabela antiga category_product não participa das consultas atuais.

O painel frontend gerencia categorias e produtos; plataformas/gêneros podem ser administrados pela API/Swagger. Categorias novas passam a aparecer nas opções dinâmicas do catálogo.

Antes de gravar no Swagger, prepare CSRF e faça login como admin. Visitante recebe 401, cliente recebe 403. Testes: CatalogApiTest e CatalogWorkflowTest. Consulte [produtos](crud-products.md) e [contrato](requests-resources.md).
