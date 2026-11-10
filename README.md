# TicoGames

Loja de demonstração com Laravel 12, MySQL 8.4 e frontend HTML, JavaScript e Bootstrap. Pagamento somente simulado e frete gratuito.

A documentação de backend, frontend, banco e API está reunida em [DOCUMENTACAO.md](DOCUMENTACAO.md).

## Configuração completa no Linux

### Pré-requisitos

- Seu repositório do GitHub já clonado e terminal Bash aberto na raiz do projeto, onde está `compose.yaml`.
- Docker Engine em execução e Docker Compose 2.24 ou superior.
- Permissão para executar Docker com seu usuário e portas locais `80` e `3306` livres.
- Acesso à internet para baixar as imagens e as dependências na primeira execução.

PHP, Composer e MySQL são executados nos containers; não é necessário instalá-los no Linux para iniciar a loja.

O Compose usa o `.env.example` versionado. Não é necessário copiar arquivos nem preencher `APP_KEY` para uma instalação nova. Se já existir um `.env` na raiz, seus valores têm prioridade, preservando a chave e as credenciais do ambiente.

Antes de iniciar uma base existente, faça backup: a API executará migrations e seeders automaticamente. Bases antigas que ainda não receberam a conversão de categorias, carrinhos de visitantes e reservas de pedidos precisam ser atualizadas pela versão anterior do projeto; `migrate` não reaplica arquivos já executados. Veja [Migrations, backups e manutenção](DOCUMENTACAO.md#migrations-backups-e-manutenção).

### 1. Construir e iniciar os containers

```bash
docker compose up -d --build --wait
```

O comando constrói as imagens e inicia `mysql`, `api`, `frontend` e `proxy`. Após o MySQL ficar pronto, a API executa automaticamente, nesta ordem:

1. `composer install` e a verificação dos requisitos PHP.
2. Limpeza do cache de configuração.
3. Geração da chave, somente se não houver `APP_KEY` configurada nem chave persistida.
4. `php artisan migrate --force`.
5. `php artisan db:seed --force`.
6. Inicialização do servidor.

Os seeders acrescentam dados sem reiniciar preço, estoque ou contas já cadastradas. Reiniciar a API reaplica somente migrations pendentes e repete a carga sem duplicar os dados de demonstração. Se uma etapa falhar, o servidor não inicia; consulte os logs e corrija a causa.

Aguarde o término: `--wait` só retorna sucesso quando os serviços estão prontos. Não é necessário executar migrations ou seeders manualmente.

Confira os containers:

```bash
docker compose ps
```

Se algum serviço falhar, consulte os logs antes de continuar:

```bash
docker compose logs --tail 100 mysql api frontend proxy
```

### 2. Conferir a instalação

```bash
docker compose ps
docker compose exec -T api php artisan migrate:status
```

Confirme que os serviços estão saudáveis e as migrations aparecem como executadas. Acesse a loja em [http://localhost](http://localhost) e entre com uma das contas de demonstração abaixo.

Para conferir a chave carregada e a carga de uma instalação nova:

```bash
docker compose exec -T api php -d zend.assertions=1 -d assert.exception=1 tests/compose-bootstrap.php
```

## Acessos

| Recurso | Endereço |
| --- | --- |
| Loja | [http://localhost](http://localhost) |
| Swagger interativo | [http://localhost/api/docs](http://localhost/api/docs) |
| Contrato OpenAPI | [http://localhost/api/openapi.yaml](http://localhost/api/openapi.yaml) |
| Saúde da API | [http://localhost/up](http://localhost/up) |

O Compose contém somente `mysql`, `api`, `frontend` e `proxy`. Banco, sessões, cache, logs e arquivos privados ficam em volumes persistentes do Docker. O volume `api_storage` guarda o storage da API. Os uploads ficam na pasta local `api/storage/app/public`, montada diretamente em `/app/storage/app/public`; as imagens continuam no computador mesmo após apagar os volumes. Essa pasta é ignorada pelo Git, portanto os uploads não acompanham um novo clone. As verificações do JavaScript podem ser executadas com `node --test front/tests/javascript.test.cjs`, sem dependências adicionais. O projeto não inclui ambiente QA.

## Contas de demonstração

| Perfil | E-mail | Senha inicial |
| --- | --- | --- |
| Administrador | admin@ticogames.test | password |
| Cliente | customer@ticogames.test | password |
| Cliente | ana@ticogames.test | password |
| Cliente | bruno@ticogames.test | password |

A carga contém 30 produtos com descrições próprias e imagens em `api/public/images`, três categorias, três plataformas, quatro gêneros, carrinhos populados e pedidos nos cinco estados. O ProductSeeder também substitui a imagem padrão dos produtos antigos, preservando imagens personalizadas, preços e estoque existentes. Há produtos inativos e sem estoque. Contas existentes preservam suas credenciais.

## Regras principais

- Login e cadastro fundem o carrinho visitante no backend; a próxima consulta já retorna o carrinho da conta.
- Checkout vazio retorna 422. Criar pedido baixa o estoque e limpa o carrinho na mesma transação.
- Pagar apenas confirma o pagamento simulado. Somente pedidos pendentes podem ser cancelados, inclusive pelo administrador.
- Cancelamento repõe o estoque uma única vez; pedidos pagos seguem para enviado e entregue por ação administrativa.
- A API controla preços, totais, permissões e histórico. O frontend apresenta os dados confirmados pelo servidor.

## Atualizar e acompanhar

Após editar o frontend:

```bash
docker compose up -d --build --no-deps --wait frontend
```

Após alterar configuração da API no `.env.example` ou no `.env` opcional:

```bash
docker compose exec -T api php artisan config:clear
docker compose up -d --force-recreate --no-deps --wait api
```

Para conferir a execução ou parar os serviços sem apagar dados:

```bash
docker compose ps
docker compose logs --tail 50 api
docker compose stop
```

`docker compose down -v` apaga os volumes, incluindo o banco e a chave gerada, mas mantém os arquivos em `api/storage/app/public`. Apagar o banco também remove os vínculos entre produtos e imagens; restaure esses dados de um backup ou configure os caminhos nos seeders. Preserve backups do MySQL, de `api_storage` e da pasta `api/storage/app/public`. Detalhes em [DOCUMENTACAO.md](DOCUMENTACAO.md).
