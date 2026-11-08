# Contas, endereços, carrinho e pedidos

Controllers recebem requisições validadas, Services aplicam regras e Resources selecionam a saída JSON. As rotas privadas usam sessão Sanctum.

## Conta e endereços

`GET /api/me` consulta a conta; `PATCH /api/user` altera nome/email/senha; `DELETE /api/user` exclui a conta e encerra a sessão. Contas com pedidos não podem ser excluídas: 422 preserva o histórico. O CRUD `/api/users` exige admin; os cadastros criam clientes.

`/api/addresses` oferece listagem, consulta, criação, atualização e exclusão dos próprios endereços. O dono vem da sessão; um `user_id` no corpo não transfere propriedade. IDs alheios retornam 404.

Campos: `recipient_name`, `postal_code`, `street`, `number`, `complement` opcional, `neighborhood`, `city`, `state`. Criação retorna 201, consulta/edição 200 e exclusão 204.

## Carrinho

| Método e rota | Ação |
| --- | --- |
| GET `/api/cart` | Consulta/prepara carrinho da sessão visitante ou conta |
| POST `/api/cart/items` | Soma `quantity` ao `product_id` |
| PATCH `/api/cart/items/{id}` | Define quantidade absoluta; zero remove |
| DELETE `/api/cart/items/{id}` | Remove item próprio |
| DELETE `/api/cart` | Limpa os itens |
| POST `/api/cart/merge` | Compatibilidade: consome visitante identificado pela sessão, sem corpo de itens |

O visitante usa token gerado pela API e guardado na sessão; o banco guarda seu hash. O carrinho autenticado persiste após logout. A fusão ocorre automaticamente em `AuthService::login/register`, soma por produto, limita ao estoque, remove indisponíveis e consome o visitante. A primeira leitura após autenticar inclui `warnings`; leituras posteriores não repetem os avisos.

Há transações e bloqueios no carrinho/usuário/produtos. Repetir a fusão não duplica itens. Falhas preservam os carrinhos; limite de 100 produtos distintos ou indisponibilidade nas inclusões/edições retorna 409. Subtotais, frete zero e total vêm do servidor.

## Pedidos

| Método e rota | Ação |
| --- | --- |
| GET `/api/orders?status=paid` | Pedidos próprios; admin vê todos, com filtro opcional |
| GET `/api/orders/{id}` | Pedido próprio ou qualquer pedido como admin |
| POST `/api/orders` | Recebe `address_id`, cria pendente, baixa estoque e limpa carrinho |
| POST `/api/orders/{id}/pay` | Pagamento simulado de pedido pendente |
| POST `/api/orders/{id}/cancel` | Cancela somente pendente e repõe unidades |
| PATCH `/api/orders/{id}/status` | Admin aplica transições permitidas |

Criação exige endereço próprio, carrinho não vazio, produtos ativos e estoque. **Vazio retorna 422** com `message`; estoque/atividade em conflito retorna 409. Cálculos usam BCMath e respeitam decimal(10,2). Corpo do cliente não determina preço, total, dono ou status.

A criação copia endereço, nomes e preços, baixa todas as unidades e limpa o carrinho atomicamente. Pagamento apenas marca `paid` e `paid_at`, sem nova baixa. Alterações posteriores nos cadastros não mudam o histórico.

| Origem | Destinos |
| --- | --- |
| `pending_payment` | `paid`, `cancelled` |
| `paid` | `shipped`, somente admin |
| `shipped` | `delivered`, somente admin |
| `delivered`, `cancelled` | Nenhum |

Dono/admin podem pagar ou cancelar pendentes. Admin também respeita a proibição de cancelar pagos. Cancelamento inválido retorna 409 sem reposição; o válido repõe uma vez. Decidir pedido alheio como cliente retorna 403; consultar pedido alheio retorna 404.

Frete gratuito. Sem gateway real, reembolso ou expiração automática. Testes e limites: [verificação](frontend-verification.md).
