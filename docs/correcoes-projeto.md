# Correções dos itens 1 a 5 — 08/11/2026

A revisão foi aplicada sobre as alterações locais existentes. Por orientação do usuário, commit, envio ao GitHub e hospedagem ficaram para depois. O [README principal](../README.md) e os demais guias foram reescritos para descrever as regras atuais.

| Item | Correção e evidência |
| --- | --- |
| 1. Fusão no login/cadastro | AuthService chama CartService::merge antes de concluir a autenticação. Cadastro/fusão são transacionais; falha não cria conta nem inicia login. GET /api/cart já traz o resultado e os warnings da primeira leitura. Frontend não depende de POST /api/cart/merge. |
| 2. Checkout vazio | OrderService retorna 422, com message “O carrinho está vazio.”. Testes cobrem carrinho ausente e carrinho esvaziado. Estoque/atividade em conflito continuam 409. |
| 3. Cancelamento de pago | Removida a transição paid → cancelled para admin e o botão correspondente. POST /cancel e PATCH /status retornam 409 para pago, sem mudar paid_at, status ou estoque. Pending_payment continua permitindo cancelar e repor uma única vez. |
| 4. Carrinho divergente | carrinho.html carrega config.js antes do Store e mantém Perfil/Pedidos no desktop e móvel. Frontend reconstruído; os 18 arquivos HTML/JS estáticos comparados são idênticos aos servidos em localhost. config.js gerado foi conferido separadamente. |
| 5. Carga na apresentação | MySQL principal atualizado para 30 produtos, três categorias e três clientes. Cenários acrescentados sem alterar registros anteriores. Backup e comparação integral executados antes/depois; segunda carga sem alterações. |

## Testes executados

- **57 testes PHP, 900 asserções**, todos aprovados em SQLite em memória.
- **72 testes Node**, todos aprovados.
- Pint aprovado nos sete arquivos PHP alterados.
- ApiDocumentationTest confirma cobertura de todas as operações, fusão automática, 422 no vazio e cancelamento restrito a pendentes.
- Testes HTML verificam config.js antes do Store e links Perfil/Pedidos em todas as oito páginas.
- Regressões incluem credenciais inválidas sem consumir visitante, fusão sem chamada extra, ajustes de estoque/avisos, rollback no cadastro e preservação de carrinho durante a carga.

## Verificação HTTP

No QA em http://localhost:8081, usando cookies reais e CSRF, foram aprovadas 22 requisições que verificam:

1. Visitante adiciona itens, cadastra e consulta o carrinho já fundido.
2. Checkout cria pedido pendente e reserva unidades; carrinho vazio retorna 422.
3. Pagamento mantém o estoque já reservado.
4. Admin tenta cancelar pago pelas duas rotas: ambas retornam 409; status, paid_at e estoque permanecem.
5. Conta mantém item após logout; novo visitante adiciona itens, faz login e GET /api/cart soma as quantidades sem POST /api/cart/merge.

No ambiente principal, a consulta pública mostra **27 produtos ativos** e três categorias. Os 30 produtos incluem três inativos, ocultos ao visitante. O YAML servido coincide com api/openapi.yaml e a página Swagger responde 200.

## Preservação da base principal

Antes: sete produtos, duas categorias, um cliente, um pedido pago e quatro itens no carrinho existente.

Depois: 30 produtos, três categorias, três clientes e um admin. Permanecem todos os registros antigos, com seus campos intactos. Há seis pedidos: dois pagos (um anterior), um pendente, um enviado, um entregue e um cancelado.

A carga encontrou o carrinho do cliente padrão preenchido. StoreScenarioSeeder foi corrigido para escolher outra conta fictícia com carrinho vazio, evitando consumir esses itens. Um teste de regressão reproduz esse caso.

Arquivos locais ignorados pelo Git:

- .local-backups/pre-demo-20261108.sql: backup SQL anterior à carga.
- .local-backups/pre-demo-20261108.json: snapshot dos registros anteriores.
- .local-backups/verify-demo.php: conferência integral e idempotência.
- .local-backups/http-review-20261108.json: relatório HTTP e arquivos servidos.

A segunda execução da carga manteve exatamente os dados da primeira. Não foram usados migrate:fresh, exclusão de volumes ou limpeza de dados existentes.

## Limites

O QA HTTP usa SQLite separado; a preparação e a conferência de preservação ocorreram no MySQL principal. Não houve teste de carga ou disputa concorrente real de processos MySQL. A interface foi validada por testes de DOM/HTML e comparação HTTP; não houve nova inspeção visual manual nesta revisão. Assets externos da interface/Swagger dependem de CDN.

Git e hospedagem seguem adiados. Não foi criado commit, enviado código ou publicado um ambiente público.
