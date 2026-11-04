# Contas, endereços, carrinho e pedidos

Implementados em 02/11/2026 a pedido do usuário, junto com a preparação e implantação da autenticação da etapa 8. Mantido o padrão de Controller, Service, FormRequest e Resource. Não foram criadas telas.

## Contas

`UserController` e `UserService` implementam listagem, consulta, criação, atualização e exclusão administrativa. As rotas `/api/users` exigem `auth:sanctum` e `admin`; clientes não podem consultar outras contas. Senhas e remember_token não aparecem no Resource. As operações de cadastro criam clientes; nenhum campo de entrada promove a conta a admin.

O próprio usuário consulta sua conta em `GET /api/me`, altera os dados em `PATCH /api/user` e pode excluir em `DELETE /api/user`. Se houver pedidos, a exclusão é rejeitada com 422 para preservar o histórico. Excluir a própria conta encerra a sessão. A criação pública e a autenticação seguem [authentication.md](authentication.md).

## Endereços

`AddressController` e `AddressService` fornecem as cinco ações REST em `/api/addresses`. Todas exigem autenticação. O Service faz as buscas pelo relacionamento da conta conectada; IDs de outra conta retornam 404.

O dono vem da sessão, não de user_id enviado no JSON. Campos: recipient_name, postal_code (até 9 caracteres), street, number (até 20), complement opcional, neighborhood, city e state com dois caracteres. Atualizações são parciais. Criar retorna 201; consultar/alterar, 200; excluir, 204.

## Carrinho

`CartController` e `CartService` trabalham com o único carrinho da conta conectada. Os itens continuam salvos ao sair da conta. Há transação e bloqueio da linha do usuário para serializar alterações e a criação do carrinho.

| Método e rota | Função |
| --- | --- |
| GET `/api/cart` | Consulta ou prepara o carrinho da conta, com itens e produtos. |
| POST `/api/cart/items` | Recebe product_id e quantity; acrescenta à quantidade existente. |
| PATCH `/api/cart/items/{id}` | Define a quantidade absoluta do item. |
| DELETE `/api/cart/items/{id}` | Remove o item próprio. |
| DELETE `/api/cart` | Remove todos os itens do próprio carrinho. |
| POST `/api/cart/merge` | Recebe items, cada um com product_id e quantity, e soma ao carrinho autenticado. |

Consulta, inclusão, alteração e incorporação retornam 200 com o carrinho em data; exclusões retornam 204. Quantidades precisam ser positivas e não exceder o estoque disponível nas inclusões/alterações. Produto inativo é rejeitado. Falha em qualquer item da incorporação desfaz toda a operação.

A etapa 9 enviará os itens do localStorage após o login e retirará os itens locais somente após o sucesso. O endpoint soma quantidades; reenviar a mesma operação soma novamente. A interface deve tratar esse fluxo para não repetir automaticamente a incorporação. O frontend e o localStorage não foram implementados nesta entrega.

## Pedidos e pagamento simulado

`OrderController` e `OrderService` oferecem ações do fluxo de compra. Itens de pedido não têm CRUD livre: são criados pelo backend, a partir do carrinho.

| Método e rota | Função |
| --- | --- |
| GET `/api/orders` | Lista somente os pedidos da própria conta. |
| GET `/api/orders/{id}` | Consulta o pedido próprio com seus itens. |
| POST `/api/orders` | Recebe address_id, usa o carrinho da conta e cria pedido pendente. |
| POST `/api/orders/{id}/pay` | Confirma pagamento simulado e baixa o estoque. |
| POST `/api/orders/{id}/cancel` | Cancela um pedido pendente sem alterar estoque. |

Na criação, o Service valida o endereço próprio, carrinho não vazio, produtos ativos e quantidades disponíveis. Calcula o total com BCMath, sem float, e verifica o limite decimal(10,2). Ignora preço, total, status e dono enviados pelo cliente, pois esses campos não passam pelo FormRequest. Copia o endereço, os nomes e os preços; salva o pedido e os itens e limpa o carrinho dentro de uma transação. Responde 201 com status pending_payment. Não reserva nem baixa estoque nessa ação.

Na confirmação, bloqueia a linha do pedido com lockForUpdate(), verifica se está pendente e bloqueia os produtos em ordem de ID. Confere novamente ativo e estoque de todos os itens; somente então diminui as quantidades e altera o status para paid. Toda a operação é uma transação. O cancelamento disputa o mesmo bloqueio do pedido e só aceita pending_payment. Nova decisão sobre pedido pago ou cancelado retorna 422. Consultar ou decidir pedido de outra pessoa retorna 404.

Não há pagamento real, campo payment_method, expiração automática nem cancelamento de pedido pago. Alterações posteriores no catálogo ou no endereço salvo não mudam as cópias históricas do pedido.

## Testes e limites da verificação

A suíte atual tem 44 testes e 664 asserções, todos aprovados em SQLite em memória. Os casos novos cobrem autenticação, perfis, proprietário de endereço/carrinho/pedido, incorporação de itens, persistência ao sair, cópias históricas, estoque conferido novamente, repetição de decisões, rollback e limite do total. HTTP real confirmou sessão, CSRF e CORS.

Os bloqueios para concorrência estão implementados para o MySQL. A suíte SQLite verifica os resultados e transações sequenciais; ela não comprova a disputa simultânea de bloqueios do MySQL. Não foi executado teste de concorrência real nesta entrega.

## Checklist manual para o estudante

- [ ] Entrar como cliente e criar, alterar e excluir um endereço de teste.
- [ ] Acrescentar um produto ao carrinho, alterar sua quantidade e conferir o resultado.
- [ ] Sair e entrar novamente; conferir que os itens continuam no carrinho.
- [ ] Criar pedido com address_id próprio; conferir itens, total e cópia do endereço.
- [ ] Confirmar pagamento e conferir que a baixa ocorre uma única vez.
- [ ] Em outro pedido pendente, cancelar e conferir que o estoque não muda.
- [ ] Entrar com outra conta e conferir que ela não vê nem altera esses dados.

Os dados desse checklist manual são reais no banco local. Os testes automatizados já executados usaram apenas SQLite em memória.
