# Verificação da revisão — 08/11/2026

Os resultados abaixo pertencem à revisão dos itens 1 a 5. As regras atuais estão no [README](../README.md) e os detalhes das mudanças no [registro](correcoes-projeto.md).

## Testes automatizados

```powershell
node --test front/tests/*.test.cjs
docker compose exec -T api php artisan test
```

Resultados: **72 testes Node** e **57 testes PHP, com 900 asserções**, aprovados. Pint passou nos sete arquivos PHP alterados.

PHPUnit exige APP_ENV=testing, SQLite e :memory:. tests/TestCase.php interrompe antes das migrations se a configuração apontar para o banco principal.

A suíte cobre autenticação, fusão automática, rollback, avisos de estoque, privacidade, checkout vazio 422, reserva na criação, pagamento sem nova baixa, reposição única, bloqueio de cancelamento pago por admin, transições e carga preservando carrinho existente.

No frontend, os testes verificam configuração antes do Store, links desktop/móvel, leitura do carrinho sem fusão pelo navegador, formulários, recuperação, chamadas e ausência do botão de cancelar pago.

## Verificação HTTP real

As mutações de teste ocorreram somente no QA em http://localhost:8081, com cookies e CSRF. Foram 22 requisições aprovadas, incluindo cadastro/login com fusão automática, checkout, pagamento e recusa de cancelamento pago pelas duas rotas administrativas. Nenhuma delas usou POST /api/cart/merge.

No principal, foram comparados os oito HTML e dez JavaScript estáticos: **18 de 18 idênticos aos arquivos do projeto**, após reconstruir o frontend. config.js gerado pelo container foi conferido separadamente. Swagger e YAML responderam 200, e o YAML servido corresponde ao arquivo local.

A API pública principal retorna 27 produtos ativos e três categorias. Os 30 registros totais incluem três produtos inativos. A carga completa foi aplicada com backup e comparação integral de todos os registros anteriores; repetir não mudou os dados.

O relatório HTTP local está em .local-backups/http-review-20261108.json. Essa pasta é ignorada pelo Git.

## Reproduzir QA

```powershell
docker compose --profile qa up -d --build qa-api qa-frontend qa-proxy
# Abra http://localhost:8081
docker compose --profile qa stop qa-proxy qa-frontend qa-api
```

O perfil QA usa SQLite em /tmp, rede própria, cookie específico e diretórios temporários. O código/vendor são montados para leitura; não usa o banco MySQL da apresentação. Recriar o container QA recria seu banco. Não use down para encerrar apenas QA, pois isso também pode parar serviços principais.

## Limites da evidência

Não houve teste de carga, concorrência real de processos MySQL ou nova inspeção visual manual no navegador. Os testes de DOM/HTML e a comparação HTTP verificam comportamento e arquivos servidos. Pagamento é simulado. Git e hospedagem ficaram para uma etapa posterior.
