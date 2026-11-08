# Migrations e atualização do banco

Migrations versionam a estrutura. `php artisan migrate` executa somente arquivos ainda não registrados em migrations. A carga de exemplos fica separada nos seeders.

## Estrutura

As migrations iniciais criam contas, endereços, catálogo, relações, carrinhos e pedidos. Índices únicos evitam email repetido, carrinho duplicado da conta e repetição de produto dentro do carrinho. FKs mantêm integridade; exclusões históricas são restringidas.

`2026_11_07_000001_complete_store_workflow.php` evolui a base existente:

- Adiciona products.category_id e copia a categoria do vínculo antigo.
- Permite carrinho visitante com user_id nulo e session_id próprio.
- Acrescenta shipped/delivered e paid_at aos pedidos.
- Reserva estoque para pedidos pendentes criados sob a regra antiga.

A migration valida antes de alterar: cada produto antigo precisa de exatamente uma categoria e o estoque deve cobrir as reservas pendentes. Corrija inconsistências antes de repetir; não force atualização removendo dados.

category_product permanece arquivada sem uso pela aplicação. Pagamentos antigos sem data conhecida mantêm paid_at nulo. Reverter essa evolução exige restaurar o backup, pois desfazer reservas automaticamente pode interferir em compras posteriores.

## Comandos

Na raiz, com os containers disponíveis:

```powershell
docker compose exec -T api php artisan migrate:status
docker compose exec -T api php artisan migrate --force
docker compose exec -T api php artisan db:show
```

Faça backup primeiro. Não use migrate:fresh, refresh ou down -v em uma base que precise ser preservada. Para exemplos, execute separadamente `db:seed --force`, conforme [seeders](seeders.md).

No ambiente principal, a migration de evolução já estava aplicada antes desta revisão de 08/11/2026; a carga completa foi acrescentada sem reconstruir tabelas.

PHPUnit aplica migrations em SQLite em memória. A proteção de `tests/TestCase.php` impede executar a suíte contra o banco da loja.
