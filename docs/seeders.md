# Carga de demonstração

`DatabaseSeeder` executa todos os seeders em uma transação. Eles acrescentam exemplos sem apagar a base ou reiniciar estoque, preço, senha ou perfil de registros existentes.

| Seeder | Conteúdo |
| --- | --- |
| UserSeeder | Um administrador e três clientes fictícios |
| CategorySeeder | Jogos, Consoles e Acessórios |
| PlatformSeeder | PlayStation 5, Xbox Series e Nintendo Switch |
| GenreSeeder | Ação, Aventura, RPG e Corrida |
| ProductSeeder | 30 produtos, categoria obrigatória e gêneros |
| AddressSeeder | Dois endereços fictícios do cliente padrão |
| StoreScenarioSeeder | Carrinhos populados e pedidos nos cinco estados |

As senhas iniciais das contas são `password`. Emails: `admin@ticogames.test`, `customer@ticogames.test`, `ana@ticogames.test`, `bruno@ticogames.test`. Contas preexistentes mantêm suas credenciais.

Há três versões inativas de Resident Evil 4 e três versões sem estoque de Hogwarts Legacy. Visitantes/clientes consultam somente produtos ativos: em uma base nova, são 27 dos 30 registros. Estoque zero continua visível com compra desabilitada.

## Preservação e cenários

Produtos são identificados por nome/plataforma; valores padrão são usados somente na criação. Gêneros de demonstração são acrescentados sem remover vínculos existentes. Categorias, plataformas e gêneros usam seus nomes; contas usam email; endereços usam dono/rua/número.

Os pedidos de demonstração usam uma das três contas fictícias com carrinho vazio. Isso impede consumir uma compra já em andamento no cliente padrão. Se todas estiverem com itens, a carga falha e sua transação é desfeita. Um endereço fictício é criado para a conta escolhida.

Os cenários usam Hades ativo com pelo menos cinco unidades. Pedidos são criados pelos Services: a criação reserva estoque, pagamento não baixa novamente e cancelamento repõe. O marcador de sessão `demo-guest-cart`, persistido como hash, impede recriar pedidos nas próximas execuções. Não remova esse marcador para repetir a carga.

O seeder também prepara carrinho visitante e carrinho da Ana, sem substituir itens preexistentes. Nenhum carrinho novo depende de localStorage.

## Aplicar

Faça backup e execute na raiz:

```powershell
docker compose exec -T api php artisan migrate --force
docker compose exec -T api php artisan db:seed --force --no-interaction
```

Não use `migrate:fresh`. Em bases com cadastros adicionais, as contagens podem superar os mínimos.

## Ambiente principal em 08/11/2026

Antes: sete produtos, duas categorias, um cliente, um pedido pago e quatro itens no carrinho existente.

Depois: 30 produtos, três categorias, três clientes, um admin e seis pedidos: dois pagos, um pendente, um enviado, um entregue e um cancelado. Os três inativos e três sem estoque foram acrescentados. Os registros anteriores foram comparados integralmente, inclusive datas, estoque, carrinhos, contas, endereços, pedidos e vínculos.

Backup SQL: `.local-backups/pre-demo-20261108.sql`. O snapshot de conferência fica na mesma pasta ignorada pelo Git. Uma segunda carga manteve exatamente os dados e contagens.

`DatabaseSeederTest` cobre mínimos, cenários, repetição e preservação de carrinho preexistente.
