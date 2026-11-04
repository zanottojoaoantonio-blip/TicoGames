# TicoGames

Integração de 03/11/2026: mantidos os fluxos autenticados do backend, as validações locais, as datas e o campo `platform_id` nos Resources do catálogo. Os erros de `api/*` usam JSON independentemente de `Accept`.

> FormRequests e Resources estão implementados. O contrato atual usa `data` nas respostas de sucesso; veja [requests-resources.md](requests-resources.md).


Projeto final de curso: loja de jogos físicos e consoles, com backend Laravel, banco MySQL e frontend Vue.

## Etapa atual: 8 — backend autenticado

O catálogo tem CRUD completo de produtos, categorias, plataformas e gêneros, com FormRequests e API Resources. Foram acrescentados os fluxos de contas, endereços, carrinho e pedidos, respeitando dono, estoque e histórico. Autenticação usa sessão/cookie com Sanctum, seguindo a aula indicada pelo usuário.

Visitantes consultam os produtos ativos. Administradores modificam o catálogo e gerenciam contas. Clientes autenticados administram os próprios endereços, carrinho e pedidos. Pagamento é simulado; o estoque só diminui ao aceitar a confirmação, dentro de uma transação.

A suíte atual passou com **44 testes e 664 asserções** em SQLite em memória. Sessão, CSRF e CORS foram verificados também por HTTP em um contêiner com SQLite temporário. O Pint passou nos arquivos PHP alterados. A concorrência real dos bloqueios do MySQL não foi exercitada pela suíte SQLite.

Guias atuais:

- [authentication.md](authentication.md): Sanctum, correspondência com o tutorial, rotas de login e consumo por cookies/CSRF.
- [account-checkout.md](account-checkout.md): contas, endereços, carrinho e pedidos.
- [requests-resources.md](requests-resources.md): validação e JSON com data, introduzidos na etapa 7.
- [crud-catalog.md](crud-catalog.md) e [crud-products.md](crud-products.md): explicações e histórico da implementação do catálogo.
- [data-model.md](data-model.md), [migrations.md](migrations.md) e [seeders.md](seeders.md): estrutura e carga inicial.

A carga de demonstração permanece com usuários, endereços e produtos fictícios. As verificações automatizadas não criaram pedidos nem alteraram produtos no MySQL. O frontend Vue existe como projeto inicial em front/, mas suas telas e integração continuam para a etapa 9, somente após solicitação do usuário.

Para continuar em outro computador ou nova conversa, leia [gpt.md](gpt.md), que registra as decisões e resultados, incluindo o histórico das etapas anteriores.

## Conceitos

- **Composer:** instala as bibliotecas PHP nas versões registradas em `composer.lock`.
- **Artisan:** ferramenta de comandos do Laravel, como gerar a chave da aplicação.
- **`api/`:** código Laravel e Dockerfiles do FrankenPHP. O Compose usa `api/Dockerfile.dev` e monta o código local.
- **`front/`:** projeto Vue compilado e servido pelo Nginx no serviço `frontend`.
- **`proxy/`:** imagem Nginx que encaminha as requisições ao Vue ou à API.
- **`.env`:** arquivo local na raiz, criado a partir de `api/.env.example`. O Compose lê as credenciais do MySQL e monta o mesmo arquivo no Laravel.
- **Extensões PHP:** acrescentam recursos à linguagem. `pdo_mysql` permite conectar ao MySQL; `intl` fornece a formatação usada por comandos como `db:show`.

O proxy atende em `http://localhost` e envia `/api`, `/sanctum`, `/up`, `/images` e `/storage` ao Laravel. Os demais caminhos vão para o Vue. O MySQL preserva os dados no volume `mysql_data` e aceita conexões locais em `127.0.0.1:3306`.

## Primeira inicialização

Execute os comandos na raiz do projeto, onde está `compose.yaml`, com o Docker em execução. Este README está em `docs/`; essa pasta não é o diretório de execução dos comandos.

No Linux, com Bash:

```bash
# Cria a configuração local sem sobrescrever um .env existente.
if [ ! -f .env ]; then
    cp api/.env.example .env
fi

# Verifica o Compose sem mostrar as credenciais.
docker compose config --quiet

# Constrói as imagens, remove contêineres dos serviços antigos e espera a pilha iniciar.
docker compose up -d --build --remove-orphans --wait

# Acompanha a instalação das dependências; Ctrl+C fecha apenas a visualização.
docker compose logs -f api
```

No Windows, abra o Docker Desktop e use o PowerShell:

```powershell
# Cria a configuração local sem sobrescrever um .env existente.
if (-not (Test-Path -LiteralPath .env)) {
    Copy-Item -LiteralPath api/.env.example -Destination .env
}

# Valida o Compose sem mostrar as credenciais.
docker compose config --quiet

# Constrói as imagens, remove contêineres dos serviços antigos e espera a pilha iniciar.
docker compose up -d --build --remove-orphans --wait

# Acompanha a inicialização; Ctrl+C encerra apenas a visualização do log.
docker compose logs -f api
```

Quando o log mostrar que o servidor iniciou, encerre a visualização com Ctrl+C. No Linux:

```bash
# Gera a chave somente se ela ainda não estiver preenchida no .env.
if ! grep -q '^APP_KEY=.' .env; then
    docker compose exec api php artisan key:generate
fi

# Descarta configurações antigas em cache após preparar o .env.
docker compose exec api php artisan config:clear
```

No PowerShell:

```powershell
# Preserva uma chave existente e gera somente quando o campo está vazio.
if (-not (Select-String -LiteralPath .env -Pattern '^APP_KEY=.+$' -Quiet)) {
    docker compose exec api php artisan key:generate
}

# Descarta configurações antigas em cache após preparar o .env.
docker compose exec api php artisan config:clear
```

Em `docker compose exec api php artisan`, `api` identifica o serviço do Compose e `php` é o programa executado dentro do contêiner.

Ao mover ou copiar a pasta, use novamente `docker compose up -d --build --wait` na nova localização para atualizar a montagem. O comando preserva os volumes.

Abra [http://localhost](http://localhost) para ver o Vue. A rota [http://localhost/up](http://localhost/up) verifica se o proxy alcança o Laravel. Para confirmar a conexão com o banco, use `docker compose exec api php artisan db:show`. Para testar login e cookies, siga o [guia de autenticação](authentication.md#testar-no-navegador).

## Configuração escolhida

O arquivo `api/.env.example` tem comentários explicando cada bloco.

| Configuração | Valor | Motivo |
| --- | --- | --- |
| `APP_NAME` | `TicoGames` | Identifica o projeto. |
| `DB_CONNECTION` | `mysql` | Banco escolhido para a loja. |
| `DB_HOST` | `mysql` | Nome do serviço do banco na rede Docker. |
| `SESSION_DRIVER` | `file` | Sessões em arquivos, sem tabela nesta etapa. |
| `CACHE_STORE` | `file` | Cache em arquivos, sem Redis ou tabela. |
| `QUEUE_CONNECTION` | `sync` | Tarefas executadas na própria requisição, sem processo de fila separado. |
| `MAIL_MAILER` | `log` | Emails registrados no log em vez de enviados. |

Sessões e cache poderiam usar o banco, mas arquivos atendem ao desenvolvimento local e permitem iniciar sem migrations. Isso não muda a decisão de salvar o carrinho autenticado no MySQL quando essa funcionalidade for implementada.

As credenciais de exemplo e `APP_DEBUG=true` são para desenvolvimento local. A chave `APP_KEY` deve ser gerada, não copiada para a documentação nem compartilhada.

## Checklist de verificação

```powershell
# Mostra os contêineres e seus estados de saúde.
docker compose ps

# Mostra a versão do Laravel instalada.
docker compose exec api php artisan --version

# Conecta ao MySQL e mostra informações do banco.
docker compose exec api php artisan db:show

# Executa a suíte Laravel em SQLite em memória.
docker compose exec api php artisan test
```

Resultados registrados até a etapa 4, em 28/10/2026:

- [x] O Compose é válido e os serviços `mysql` e `php` estão saudáveis.
- [x] Laravel **12.70.2**, PHP **8.4.27** e MySQL **8.4.10** verificados.
- [x] Os requisitos PHP do Composer estão atendidos e a extensão `intl` está carregada.
- [x] A página inicial e a rota `/up` respondem com HTTP 200.
- [x] `db:show --counts` acessa `ticogames` pelo driver MySQL, com **20 tabelas** após as migrations.
- [x] Os **2 testes iniciais passaram**, com **2 asserções**.
- [x] O `.env` local tem uma chave gerada e está ignorado pelo Git.

Os testes iniciais cobrem a resposta da página padrão e uma asserção de exemplo. A conexão MySQL foi verificada separadamente; os testes ainda não validam catálogo, carrinho, pedidos ou pagamento.

As migrations já foram aplicadas neste ambiente. Em um banco novo, execute `docker compose exec api php artisan migrate` para reproduzir a estrutura, conforme [migrations.md](migrations.md), e `docker compose exec api php artisan db:seed` para carregar os exemplos da etapa 5, conforme [seeders.md](seeders.md). O comando padrão `composer run setup` não é o caminho usado neste roteiro.

## Uso diário

```powershell
# Inicia o ambiente preparado; não precisa gerar a chave novamente.
docker compose up -d --wait

# Interrompe os serviços sem remover os dados do banco.
docker compose stop
```

O Nginx serve a versão compilada do Vue. Depois de editar arquivos em `front/`, reconstrua o serviço:

```powershell
docker compose up -d --build --wait frontend
```

## Estrutura para as próximas etapas

- `api/app/Models`: classes que representam os dados e relacionamentos.
- `api/database/migrations`: arquivos que criam e alteram tabelas.
- `api/database/seeders`: dados iniciais e de demonstração.
- `api/routes/web.php`: rotas web; por enquanto contém a página padrão.
- `api/config/`: configurações do Laravel, que podem ler o `.env`.
- `api/storage/`: arquivos gerados, sessões, cache e logs.
- `front/`: projeto Vue separado do backend.

A etapa 8 do backend está concluída. Aguardar solicitação antes de iniciar a etapa 9 (Vue). O fluxo atual de autenticação está em [authentication.md](authentication.md).
