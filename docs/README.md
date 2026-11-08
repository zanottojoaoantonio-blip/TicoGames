# Documentação e ambiente

Documentação revisada em 08/11/2026. O [README principal](../README.md) resume as regras atuais. Estes guias descrevem a implementação vigente, sem instruções de etapas antigas.

## Executar e atualizar

Requisitos: Docker Desktop com containers Linux e Docker Compose. Node é necessário apenas para testes do frontend. PHP e Composer locais não são necessários quando a API roda no Docker.

Na raiz do projeto:

```powershell
if (-not (Test-Path -LiteralPath .env)) {
    Copy-Item -LiteralPath .env.example -Destination .env
}
docker compose config --quiet
docker compose build
docker compose run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose run --rm --no-deps api php artisan key:generate --show
```

Em uma instalação nova, copie a chave gerada para `APP_KEY` no `.env` da raiz. Preserve uma chave já preenchida. O Compose injeta esse arquivo como ambiente da API; não crie outro `.env` em `api/`.

```powershell
docker compose up -d --wait
docker compose exec -T api php artisan migrate --force
docker compose exec -T api php artisan db:seed --force
docker compose ps
```

Faça backup antes de atualizar bases existentes. Nunca use `migrate:fresh` ou `down -v` para preparar a apresentação.

| Serviço | Responsabilidade |
| --- | --- |
| `mysql` | MySQL 8.4, volume `mysql_data`, porta local 3306 |
| `api` | Laravel/FrankenPHP, código montado em `/app`, dependências em `api_vendor` |
| `frontend` | Nginx com HTML/JS/CSS copiados para a imagem |
| `proxy` | Entrada em http://localhost; encaminha API, Sanctum, imagens e frontend |

O proxy também encaminha `/up`. Uploads ficam em `api_uploads`. Sessão/cache usam arquivos, filas são síncronas e emails vão para log.

Após mudar configuração da API no `.env`:

```powershell
docker compose exec -T api php artisan config:clear
docker compose up -d --force-recreate --no-deps --wait api
```

Após mudar HTML/JS/CSS ou `API_BASE_URL`:

```powershell
docker compose up -d --build --no-deps --wait frontend
```

O Laravel usa o código montado; o frontend exige reconstrução. Abra [a loja](http://localhost) pelo proxy, sem abrir HTML como arquivo local.

## Guias

| Assunto | Documento |
| --- | --- |
| Correções e evidências desta revisão | [correcoes-projeto.md](correcoes-projeto.md) |
| Autenticação, cookies e CSRF | [authentication.md](authentication.md) |
| Conta, endereços, carrinho e pedidos | [account-checkout.md](account-checkout.md) |
| Modelo e evolução do banco | [data-model.md](data-model.md), [migrations.md](migrations.md) |
| Carga de demonstração e preservação | [seeders.md](seeders.md) |
| CRUD e validação | [crud-catalog.md](crud-catalog.md), [crud-products.md](crud-products.md), [requests-resources.md](requests-resources.md) |
| Frontend e configuração | [frontend-integration.md](frontend-integration.md), [README do frontend](../front/README.md) |
| Catálogo, detalhes e acesso | [catalog-filters.md](catalog-filters.md), [product-details.md](product-details.md), [frontend-login.md](frontend-login.md) |
| Cobertura e testes | [frontend-plan.md](frontend-plan.md), [frontend-verification.md](frontend-verification.md), [session-cart-fix.md](session-cart-fix.md) |
| Contexto para continuidade | [gpt.md](gpt.md) |

Contrato completo: [Swagger](http://localhost/api/docs), [OpenAPI servido](http://localhost/api/openapi.yaml) e [fonte YAML](../api/openapi.yaml). O Swagger usa cookies e prepara CSRF antes das escritas, conforme o guia de autenticação.

Entrega no Git e hospedagem permanecem para uma etapa posterior.
