# TicoGames

Loja de demonstração com Laravel 12, MySQL 8.4 e frontend HTML, JavaScript e Bootstrap. Pagamento somente simulado e frete gratuito.

A documentação de backend, frontend, banco e API está reunida em [DOCUMENTACAO.md](DOCUMENTACAO.md).

## Iniciar

Requisitos: Docker Desktop com containers Linux e Docker Compose.

Na raiz, prepare a configuração sem sobrescrever um ambiente existente:

```powershell
if (-not (Test-Path -LiteralPath .env)) {
    Copy-Item -LiteralPath .env.example -Destination .env
}
docker compose build
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
```

Somente em uma instalação nova, gere a chave e salve o resultado em `APP_KEY` no `.env` da raiz:

```powershell
docker compose run --rm --no-deps api php artisan key:generate --show
```

Preserve uma chave já configurada. Depois, inicie a loja e prepare a base:

```powershell
docker compose up -d --wait
docker compose exec -T api php artisan migrate --force
docker compose exec -T api php artisan db:seed --force
```

Faça backup antes de atualizar uma base existente. Os seeders acrescentam dados sem reiniciar preço, estoque ou contas já cadastradas.

As migrations de criação foram consolidadas com o esquema atual. Bases antigas que ainda não receberam a conversão de categorias, carrinhos de visitantes e reservas de pedidos precisam ser atualizadas pela versão anterior do projeto antes de usar esta versão; `migrate` não reaplica arquivos já executados. Veja [Migrations, backups e manutenção](DOCUMENTACAO.md#migrations-backups-e-manutenção).

## Acessos

| Recurso | Endereço |
| --- | --- |
| Loja | [http://localhost](http://localhost) |
| Swagger interativo | [http://localhost/api/docs](http://localhost/api/docs) |
| Contrato OpenAPI | [http://localhost/api/openapi.yaml](http://localhost/api/openapi.yaml) |
| Saúde da API | [http://localhost/up](http://localhost/up) |

O Compose contém somente `mysql`, `api`, `frontend` e `proxy`. Banco, sessões, cache, logs, arquivos privados e uploads ficam em volumes persistentes do Docker. O volume `api_storage` guarda o storage da API; `api_uploads` continua exclusivo para imagens públicas. As verificações do JavaScript podem ser executadas com `node --test front/tests/javascript.test.cjs`, sem dependências adicionais. O projeto não inclui ambiente QA.

## Contas de demonstração

| Perfil | E-mail | Senha inicial |
| --- | --- | --- |
| Administrador | admin@ticogames.test | password |
| Cliente | customer@ticogames.test | password |
| Cliente | ana@ticogames.test | password |
| Cliente | bruno@ticogames.test | password |

A carga contém 30 produtos, três categorias, três plataformas, quatro gêneros, carrinhos populados e pedidos nos cinco estados. Há produtos inativos e sem estoque. Contas existentes preservam suas credenciais.

## Regras principais

- Login e cadastro fundem o carrinho visitante no backend; a próxima consulta já retorna o carrinho da conta.
- Checkout vazio retorna 422. Criar pedido baixa o estoque e limpa o carrinho na mesma transação.
- Pagar apenas confirma o pagamento simulado. Somente pedidos pendentes podem ser cancelados, inclusive pelo administrador.
- Cancelamento repõe o estoque uma única vez; pedidos pagos seguem para enviado e entregue por ação administrativa.
- A API controla preços, totais, permissões e histórico. O frontend apresenta os dados confirmados pelo servidor.

## Atualizar e acompanhar

Após editar o frontend:

```powershell
docker compose up -d --build --no-deps --wait frontend
```

Após alterar configuração da API no `.env`:

```powershell
docker compose exec -T api php artisan config:clear
docker compose up -d --force-recreate --no-deps --wait api
```

Para conferir a execução ou parar os serviços sem apagar dados:

```powershell
docker compose ps
docker compose logs --tail 50 api
docker compose stop
```

`docker compose down -v` apaga os volumes. Preserve backups do MySQL, de `api_storage` e de `api_uploads`. Detalhes em [DOCUMENTACAO.md](DOCUMENTACAO.md).
