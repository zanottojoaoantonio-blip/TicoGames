# TicoGames

Projeto final de curso: loja de jogos físicos e consoles, com backend Laravel, banco MySQL e frontend Vue.

## Etapa atual: 2 — Inicialização do Laravel

O projeto já tem a estrutura do Laravel 12. Nesta etapa, preparamos o ambiente e verificamos o backend e sua conexão com o MySQL. As tabelas e funcionalidades da loja serão implementadas nas próximas etapas.

A modelagem está em [docs/data-model.md](docs/data-model.md).

## Conceitos

- **Composer:** instala as bibliotecas PHP nas versões registradas em `composer.lock`.
- **Artisan:** ferramenta de comandos do Laravel, como gerar a chave da aplicação.
- **Dockerfile.dev:** prepara o PHP para desenvolvimento.
- **Compose:** organiza os serviços `php`, `mysql` e `node` e a comunicação entre eles.
- **.env:** guarda configurações locais e não é enviado ao Git. O `.env.example` serve de modelo.

Aproveitamos o Compose existente em vez de reinstalar o Laravel ou adicionar o Laravel Sail, mantendo uma única configuração de Docker para estudar.

## Primeira inicialização

Abra o Docker Desktop e execute no PowerShell, na pasta do projeto:

```powershell
# Cria a configuração local sem sobrescrever um .env existente.
if (-not (Test-Path -LiteralPath .env)) {
    Copy-Item -LiteralPath .env.example -Destination .env
}

# Valida o Compose sem mostrar as credenciais.
docker compose config --quiet

# Constrói a imagem PHP e inicia o banco e o backend em segundo plano.
# O serviço php instala as dependências antes de iniciar o servidor.
docker compose up -d --build mysql php

# Acompanha a inicialização; Ctrl+C encerra apenas a visualização do log.
docker compose logs -f php
```

Quando o log mostrar que o servidor iniciou, encerre a visualização com Ctrl+C e execute:

```powershell
# Gera a chave de criptografia no .env, apenas na primeira preparação.
docker compose exec php php artisan key:generate

# Descarta configurações antigas em cache após preparar o .env.
docker compose exec php php artisan config:clear
```

O primeiro `php` identifica o serviço do Compose; o segundo é o programa executado dentro dele.

Abra [http://localhost:8000](http://localhost:8000). A página padrão do Laravel deve aparecer. A rota [http://localhost:8000/up](http://localhost:8000/up) verifica a inicialização da aplicação, mas não a conexão com o banco.

## Configuração escolhida

O `.env.example` tem comentários explicando cada bloco.

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
docker compose exec php php artisan --version

# Conecta ao MySQL e mostra informações do banco.
docker compose exec php php artisan db:show

# Executa os testes que já acompanham o projeto.
# Eles ainda não comprovam funcionalidades da loja nem a conexão MySQL.
docker compose exec php php artisan test
```

- [ ] Os serviços `mysql` e `php` estão ativos e saudáveis.
- [ ] O Artisan informa uma versão Laravel 12.x.
- [ ] A página inicial responde sem erro.
- [ ] A rota `/up` responde com HTTP 200.
- [ ] `db:show` acessa o banco `ticogames` pelo driver MySQL.
- [ ] Os testes existentes passam.
- [ ] `.env` não aparece como arquivo a adicionar ao Git.

O banco pode estar vazio nesta etapa. Não execute migrations ou seeders agora: as migrations serão tratadas na etapa 4. O comando padrão `composer run setup` também executa migrations e não é o caminho usado neste roteiro.

## Uso diário

```powershell
# Inicia o ambiente preparado; não precisa gerar a chave novamente.
docker compose up -d mysql php

# Interrompe os serviços sem remover os dados do banco.
docker compose stop
```

O Vue já existe em `front-end/`, mas sua implementação fica para a etapa 9. Para iniciar também seu servidor de desenvolvimento quando necessário:

```powershell
# Inicia o Vue na porta 5173 e instala suas dependências.
docker compose up -d --build node
```

## Estrutura para as próximas etapas

- `app/Models`: classes que representam os dados e relacionamentos.
- `database/migrations`: arquivos que criam e alteram tabelas.
- `database/seeders`: dados iniciais e de demonstração.
- `routes/web.php`: rotas web; por enquanto contém a página padrão.
- `config/`: configurações do Laravel, que podem ler o `.env`.
- `storage/`: arquivos gerados, sessões, cache e logs.
- `front-end/`: projeto Vue separado do backend.

A próxima etapa é a criação dos Models, após a conclusão desta etapa e o usuário dizer “próximo”.
