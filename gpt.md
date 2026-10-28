# TicoGames — contexto para continuar o projeto

Última atualização: 27/10/2026.

## Leia isto primeiro

Este arquivo registra o contexto do projeto para continuar em outro computador ou em uma nova conversa. Leia também `docs/data-model.md` e `README.md`, depois confira os arquivos atuais antes de alterar código.

**Paramos após concluir a etapa 2: inicialização do Laravel. A próxima é a etapa 3: Models. Ela ainda não foi iniciada.**

O último pedido do usuário foi criar este arquivo de continuidade. Isso não é autorização para avançar de etapa. Aguarde ele dizer “próximo” ou pedir explicitamente a etapa 3.

Ao concluir uma etapa, atualize este arquivo com o que mudou, o que foi verificado e qual é o próximo passo. Registre limitações sem apresentar funcionalidades planejadas como já implementadas.

## Contexto do estudante e forma de trabalhar

- O usuário é estudante do ensino médio e está fazendo o projeto final do curso.
- O prazo informado foi de duas semanas para fazer, entender e explicar o projeto para uma banca.
- Priorize soluções simples e fáceis de explicar. Evite ferramentas, abstrações e funcionalidades extras sem necessidade.
- Faça somente uma etapa por vez e espere o usuário dizer “próximo”.
- Explique o conceito antes de apresentar ou escrever código.
- Comente cada parte do código para ajudar no aprendizado.
- Explique em português; use nomes de tabelas, classes, métodos e campos em inglês.
- Quando houver alternativas relevantes, apresente as opções e justifique a escolha.
- Termine cada etapa com um checklist do que testar. Diferencie verificações executadas de testes ainda pendentes.

## Projeto e tecnologias

E-commerce de jogos físicos e consoles chamado **TicoGames**.

- Backend: Laravel 12; a instalação verificada está na versão **12.70.2**.
- Banco: MySQL 8.4.
- Ambiente: Docker e Docker Compose.
- Frontend: Vue, com Vite, na pasta `front-end/`.
- Autenticação planejada: Laravel Sanctum, na etapa 8.
- Ambiente de origem: Windows com PowerShell. Os caminhos locais e os processos não são transferidos só por copiar este arquivo.

## Etapas combinadas

| Etapa | Conteúdo | Estado |
| --- | --- | --- |
| 1 | Modelagem de dados | Concluída; registrada em `docs/data-model.md`. |
| 2 | Inicializar projeto Laravel | Concluída e verificada no computador de origem. |
| 3 | Models | Próxima; ainda não iniciada. |
| 4 | Migrations | Pendente. |
| 5 | Seeders | Pendente. |
| 6 | CRUD básico: Controllers e Services | Pendente. |
| 7 | CRUD completo: Resources e FormRequests | Pendente. |
| 8 | Registro, login e sessão com Laravel Sanctum | Pendente. |
| 9 | Frontend: login/registro, catálogo, administração, carrinho, pagamento simulado e status | Pendente. |

## Regras de negócio definidas

- Vender jogos físicos e consoles, sem entrega digital ou chaves de ativação.
- Um jogo para PS5 e sua versão para Xbox são produtos separados, com preços e estoques próprios.
- Cada produto tem uma categoria e uma plataforma.
- Jogos podem ter vários gêneros; consoles não precisam de gênero.
- Usar apenas plataforma, sem filtro adicional de console portátil, de mesa ou híbrido.
- Dados dos produtos: nome, descrição, preço, uma imagem, categoria, plataforma, estoque e indicador de ativo.
- Dois perfis em `users.role`: `customer` e `admin`. Não há perfil separado de vendedor.
- Visitantes podem navegar e montar carrinho; login é obrigatório para criar pedido.
- Carrinho do visitante persiste no `localStorage` do mesmo navegador. Isso não sincroniza entre dispositivos.
- Carrinho do cliente autenticado fica no banco e deve ser preservado ao sair da conta.
- No login, incorporar os itens do visitante ao carrinho da conta; somar quantidades de produtos iguais e validar a disponibilidade antes da compra.
- Um cliente pode salvar vários endereços, exibidos como cartões de seleção.
- Cada pedido guarda uma cópia do endereço escolhido; alterações no cadastro não mudam pedidos existentes.
- Cada item de pedido guarda nome e preço unitário históricos.
- Pagamento é simulado: o administrador confirma o recebimento. Não integrar gateway ou cobrança real.
- Status: `pending_payment`, `paid`, `delivered` e `cancelled`.
- O estoque diminui somente quando o administrador confirma o pagamento.
- Carrinhos e pedidos pendentes não reservam unidades. Conferir estoque ao criar o pedido e novamente ao confirmar pagamento.
- Se faltar estoque, não confirmar o pagamento. A confirmação e a baixa de todos os itens devem ocorrer juntas, em uma transação, com proteção contra confirmações simultâneas.
- Não diminuir estoque novamente se o pedido já estiver pago. Marcar como entregue também não muda estoque.
- O cliente pode cancelar um pedido próprio enquanto estiver aguardando pagamento. Nesse caso não há estoque para devolver.
- Desativar produtos com `is_active` para preservar o histórico dos pedidos.
- Valores monetários: `decimal(10,2)`. Estoque e quantidades: inteiros.

**Ideia adiada a pedido do usuário:** expiração automática de pedidos sem pagamento. Foi sugerido prazo de 24 horas, mas o prazo e a implementação ficaram para depois. Não criar agendador ou regra de expiração agora.

Também não foram incluídos nesta entrega reembolsos, cancelamento após pagamento, cupons, avaliações, múltiplos vendedores ou cálculo de frete. Não ampliar o escopo sem combinar com o usuário.

## Modelagem definida, ainda não implementada

As 11 tabelas de negócio planejadas são:

`users`, `addresses`, `categories`, `platforms`, `genres`, `products`, `genre_product`, `carts`, `cart_items`, `orders` e `order_items`.

Campos e relacionamentos estão detalhados em `docs/data-model.md`. O parágrafo final desse documento descreve o encerramento histórico da etapa 1; o progresso atualizado está neste arquivo.

Para a etapa 3, a direção prevista é reaproveitar `User` e criar `Address`, `Category`, `Platform`, `Genre`, `Product`, `Cart`, `CartItem`, `Order` e `OrderItem`, com os relacionamentos e as conversões de tipos necessários. A ligação `genre_product` pode usar o relacionamento muitos-para-muitos do Eloquent, sem uma classe própria.

Não criar migrations, seeders, controllers ou autenticação junto com os Models: cada assunto tem sua etapa. Ter o Model definido não significa que sua tabela já existe.

## O que foi feito até aqui

1. Revisados os Dockerfiles, o `compose.yaml` e os dois `.dockerignore`. As exclusões foram ampliadas para credenciais, dados locais, dependências e arquivos de desenvolvimento. O usuário pediu simplicidade depois dessa revisão; não ampliar essas configurações sem necessidade.
2. Criada a modelagem em `docs/data-model.md`.
3. Aproveitado o projeto Laravel já existente, sem reinstalar o framework nem adicionar Laravel Sail.
4. Simplificado e comentado o `.env.example`, com nome `TicoGames`, conexão MySQL, sessões e cache em arquivos, tarefas síncronas e emails no log.
5. Criado o `.env` local a partir do exemplo e gerada a chave com `php artisan key:generate`. O `.env` é ignorado pelo Git; não copiar a chave para este documento.
6. Criado o roteiro em português no `README.md`, substituindo o texto padrão do Laravel.
7. Iniciado o Docker Desktop, construída a imagem PHP e iniciados os serviços `mysql` e `php`.
8. Instaladas as dependências do Composer dentro do volume `php_vendor`.
9. Limpado o cache de configuração e executadas as verificações da etapa 2.

## Verificações já realizadas no computador de origem

- `docker compose config --quiet`: configuração válida.
- `composer check-platform-reqs`: requisitos atendidos.
- Laravel **12.70.2**, PHP **8.4.27** e MySQL **8.4.13** nas versões efetivamente testadas.
- `php artisan db:show`: conexão MySQL com o banco `ticogames`, com **zero tabelas**.
- `php artisan test`: **2 testes passaram**, com 2 asserções. São os exemplos iniciais; não comprovam as funcionalidades da loja.
- `http://localhost:8000` e `http://localhost:8000/up`: HTTP **200** pelo computador de origem.
- Contêineres `mysql` e `php`: saudáveis na última verificação.
- `.env` ignorado pelo Git e `git diff --check` sem erros de whitespace.

Esses são resultados anteriores, não uma garantia sobre o ambiente atual ou outro computador. A rota `/up` testa a inicialização do Laravel; a conexão com o banco foi verificada separadamente.

## Estado do código

- `app/Models/User.php` ainda é o Model padrão do Laravel; não possui os relacionamentos da loja ou o campo `role` configurado.
- Existem somente as migrations padrão de usuários/sessões, cache e jobs; elas não foram executadas nesta sequência.
- `database/seeders/DatabaseSeeder.php` é o arquivo inicial, sem os dados da loja implementados nesta sequência.
- A rota `/` ainda mostra a página padrão do Laravel.
- O Vue está inicializado, mas as telas da loja não foram implementadas. Seu serviço não foi necessário para concluir a etapa 2.
- Sanctum, os CRUDs, carrinho, pedidos e pagamento simulado ainda não foram implementados.
- O `Dockerfile` da raiz é separado da configuração de desenvolvimento. O Compose usa `Dockerfile.dev` para PHP e `front-end/Dockerfile.dev` para Vue.

## Continuar em outro computador

Copiar ou clonar o projeto incluindo este `gpt.md`, `README.md` e `docs/data-model.md`. Este arquivo transfere o contexto, não o histórico completo do chat nem o ambiente Docker.

As imagens, os volumes do Docker e o `.env` não são transportados pelo Git. No outro computador, siga o `README.md` para preparar o ambiente local. Não envie segredos ao repositório.

Com Docker Desktop aberto, execute no PowerShell, na raiz do projeto:

```powershell
# Cria o arquivo local somente se ele ainda não existir.
if (-not (Test-Path -LiteralPath .env)) {
    Copy-Item -LiteralPath .env.example -Destination .env
}

# Prepara e inicia o backend e o MySQL; instala as dependências PHP.
docker compose up -d --build mysql php

# Acompanha a instalação; Ctrl+C fecha apenas a visualização dos logs.
docker compose logs -f php
```

Depois que o log informar que o servidor iniciou, encerre a visualização com Ctrl+C:

```powershell
# Gera a chave apenas se o novo .env ainda estiver sem uma chave.
if (-not (Select-String -LiteralPath .env -Pattern '^APP_KEY=.+$' -Quiet)) {
    docker compose exec php php artisan key:generate
}

# Atualiza as configurações carregadas e verifica o ambiente.
docker compose exec php php artisan config:clear
docker compose exec php php artisan --version
docker compose exec php php artisan db:show
docker compose exec php php artisan test
```

Não executar `composer run setup`, migrations ou seeders automaticamente agora. O script padrão de setup executa migrations e adianta etapas do roteiro.

## Mensagem sugerida para retomar

> Leia o gpt.md, o README.md e docs/data-model.md deste projeto. As etapas 1 e 2 foram concluídas. Primeiro confira o ambiente e resuma onde paramos. Espere eu dizer “próximo” antes de iniciar a etapa 3: Models. Mantenha a implementação simples, explique antes do código e respeite uma etapa por vez.
