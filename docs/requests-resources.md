# Validação e respostas da API

FormRequests validam entrada antes dos Controllers. Eles usam somente validated(), para impedir que o corpo do cliente escolha proprietário, total, status inicial ou perfil administrativo. Autorização de sessão/perfil fica nos middlewares e regras de dono nos Services.

Requests de criação exigem os campos centrais; atualização usa sometimes para preservar campos omitidos. O cadastro público confirma senha e ignora role administrativo. Produtos usam uma category_id; genre_ids é uma lista opcional.

## Saída

Resources selecionam campos e relacionamentos carregados. Sucessos retornam um objeto ou lista em data. Produtos paginados incluem links/meta. Exclusões/limpeza/logout retornam 204 sem corpo. Senhas, token visitante e seu hash não são expostos.

Preço/total/subtotal/frete são textos decimais. Estoque e quantidade são inteiros; is_active é booleano. Carrinho inclui warnings; após autenticar, a primeira consulta entrega os ajustes da fusão automática.

OrderResource mantém endereço histórico, itens, total, created_at e paid_at. A data de pagamento pode ser nula. Alterar produto/endereço cadastrado não reescreve o pedido.

## Erros

| HTTP | Uso |
| --- | --- |
| 401 | Autenticação necessária |
| 403 | Perfil sem permissão ou decisão em pedido alheio |
| 404 | ID inexistente ou consulta privada sem acesso |
| 409 | Estoque, limite do carrinho ou transição em conflito |
| 419 | Sessão/CSRF inválido |
| 422 | Validação, carrinho vazio ou restrição de exclusão |
| 429 | Limite de tentativas de autenticação |

Erros usam message e, na validação de campos, errors. Carrinho vazio pode ter apenas message; o frontend apresenta esse texto. Erros de /api são JSON independentemente de Accept.

Contrato detalhado em [OpenAPI](../api/openapi.yaml), com [Swagger](http://localhost/api/docs). Cobertura automática: ApiDocumentationTest verifica todas as operações e as regras desta revisão.
