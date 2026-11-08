# Contexto para continuar o TicoGames

Atualizado em 08/11/2026. Este documento registra o estado atual; o histórico antigo de etapas foi substituído para evitar instruções contraditórias.

## Projeto e convenções

- Backend em api/: Laravel 12, Sanctum por sessão, MySQL 8.4 e BCMath.
- Frontend em front/: oito páginas HTML, JavaScript, CSS e Bootstrap; Axios para requisições.
- Compose único na raiz, com mysql, api, frontend e proxy. Perfil qa isolado na porta 8081.
- Controllers delegam aos Services, FormRequests validam e Resources selecionam JSON.
- Manter nomes de código em inglês e explicações/documentação em português.
- Reutilizar a estrutura e dependências instaladas, com mudanças pequenas e completas.

Leia [README principal](../README.md), [ambiente](README.md), [regras de compra](account-checkout.md) e [correções](correcoes-projeto.md).

## Regras consolidadas

Carrinho visitante vive no banco com hash de token aleatório da sessão. Login/cadastro fundem no AuthService antes de concluir a autenticação. GET /api/cart já retorna o carrinho da conta; warnings são consumidos na primeira leitura. O frontend não dispara fusão. A rota POST /api/cart/merge permanece por compatibilidade e não recebe itens no corpo.

Quantidades são limitadas ao estoque na fusão. Visitante consumido é apagado e seu token removido. Credenciais inválidas e falhas preservam os carrinhos; falha de fusão no cadastro desfaz a conta.

Checkout vazio retorna 422; estoque/atividade em conflito retorna 409. O pedido baixa estoque na criação, copia nomes/preços/endereço e limpa carrinho em transação. Pagar apenas marca paid/paid_at. Dono/admin cancelam somente pending_payment; admin não cancela paid. Fluxo administrativo: paid → shipped → delivered. Estados inválidos retornam 409; decisão alheia por cliente retorna 403 e consulta alheia 404.

Produto tem uma category_id e uma plataforma; gêneros são múltiplos. Resposta mantém categories=[categoria] por compatibilidade. Pesquisa/filtros/ordenação/paginação são do servidor. Frete gratuito e pagamento simulado.

Perfil edita a conta atual. Admin gerencia produtos/categorias, imagens e pedidos. Todas as páginas carregam config.js antes do Store e têm Perfil/Pedidos nos menus desktop e móvel. Texto administrativo é “Pedidos”; cliente usa “Meus pedidos”.

## Ambiente da apresentação

MySQL principal preparado com 30 produtos, três categorias, três clientes e um admin. Há três inativos, três sem estoque e seis pedidos nos cinco estados, incluindo o pedido pago que já existia. A API pública mostra 27 ativos.

Todos os registros anteriores foram preservados integralmente, incluindo os quatro itens do carrinho do cliente padrão. O seeder escolhe uma conta fictícia com carrinho vazio para criar cenários. Repetir a carga não modifica os dados. Backup SQL anterior: .local-backups/pre-demo-20261108.sql.

A migration de evolução já está aplicada. Não recrie a base nem volumes. category_product é arquivo sem uso; pagamentos antigos podem ter paid_at nulo.

## Verificação atual

57 testes PHP/900 asserções e 72 testes Node aprovados; Pint aprovado nos sete PHP alterados. HTTP real no QA verificou sessão/CSRF, fusão no cadastro/login sem endpoint extra, 422 no checkout vazio e 409 ao admin cancelar pago pelas duas rotas.

Os 18 arquivos HTML/JS estáticos comparados em localhost coincidem com o disco; config.js gerado foi conferido separadamente. Swagger atualizado em /api/docs, YAML em api/openapi.yaml e /api/openapi.yaml.

Não houve teste de carga, concorrência real de múltiplos processos MySQL ou nova inspeção visual manual. Consulte [evidências](frontend-verification.md).

## Próximos trabalhos

O usuário restringiu esta revisão aos itens 1 a 5, Markdown e Swagger. **Commit, envio ao GitHub e hospedagem foram explicitamente adiados.** Não foi feito commit/push nesta revisão. Não existe hospedagem pública comprovada.

Para alterar frontend, reconstrua sua imagem. Para configuração da API, limpe config e recrie o serviço. Use PHPUnit com SQLite em memória e QA para mutações de verificação. Não use migrate:fresh ou down -v na apresentação.
