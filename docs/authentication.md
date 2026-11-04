# Etapa 8 — autenticação por sessão com Sanctum

Implementada em 02/11/2026, seguindo os modelos da [Aula 2 — Laravel Sanctum](https://victor-raphael17.github.io/studies/sanctum.html#passo-1) indicada pelo usuário.

## Retomada passo a passo

1. O Composer instala o Sanctum 4.3.3 a partir do `composer.lock`. O projeto já possui rotas de API e usa a sessão do Laravel.
2. `statefulApi()` prepara sessão e CSRF para a origem configurada. O proxy serve o Vue e encaminha as chamadas da API e do Sanctum na mesma origem, `http://localhost`.
3. `RequireFrontendSession` retorna 419 antes de cadastro ou login se a requisição não recebeu uma sessão. Com a sessão preparada, os FormRequests validam os campos, o AuthService autentica e o UserResource apresenta a conta.
4. `auth:sanctum` exige uma conta conectada nas rotas privadas. O middleware `admin` exige o perfil administrativo nas escritas do catálogo e na gestão de contas.

## O que identifica a conta

O login usa a sessão do Laravel e um cookie enviado pelo navegador. Não há emissão de Bearer token, nem armazenamento de credenciais em localStorage. O `User` continua sem `HasApiTokens`; o cast `hashed` já existente salva a senha como hash.

O cookie `XSRF-TOKEN` tem outra função: seu valor vai no header `X-XSRF-TOKEN` para proteger POST, PUT, PATCH e DELETE contra CSRF. Ele não faz o login. O cookie de sessão é HttpOnly e não é lido pelo JavaScript.

## Correspondência com o tutorial

| Exemplo da aula | TicoGames |
| --- | --- |
| `RegisterRequest` e `LoginRequest` | Mesmos nomes e regras centrais; cadastro valida nome, email único e senha confirmada de pelo menos 8 caracteres. |
| `AuthService::register`, `login` e `logout` | O Service cria/autentica a conta, regenera a sessão e a invalida ao sair. Recebe o Request para executar as operações de sessão, como na aula. |
| `AuthController` | Delega ao Service, usa `validated()`, devolve `UserResource`, status 201/200 e `Cache-Control: no-store` no cadastro e no login. |
| `GET /api/me` | Retorna a conta autenticada, também com `no-store`. |
| Rotas em `api/routes/api.php` | Cadastro/login públicos com `throttle:6,1`; `me` e `logout` no grupo `auth:sanctum`. |
| `statefulApi()`, CORS e domínio stateful | Vue e Laravel compartilham `http://localhost` pelo proxy. O Sanctum reconhece `localhost` como stateful. |

Adaptações às regras já combinadas: o catálogo pode ser consultado por visitantes; somente administradores o modificam. `UserResource` inclui também `role` para a interface distinguir cliente e administrador. O cadastro público sempre usa o padrão `customer` do banco: `role` não é validado nem está em `$fillable`. O tutorial protege todo o catálogo e usa frontend na porta 80; essas duas escolhas não se aplicam à nossa loja.

## Arquivos e responsabilidades

- `api/bootstrap/app.php`: ativa `statefulApi()` e registra o middleware `admin`.
- `api/config/sanctum.php`: inclui os hosts e portas locais reconhecidos como frontend stateful.
- `api/config/cors.php`: permite a origem exata do Vue, com credenciais.
- `api/app/Http/Requests/RegisterRequest.php` e `LoginRequest.php`: validam a entrada.
- `api/app/Services/AuthService.php`: cria conta e controla autenticação e sessão.
- `api/app/Http/Controllers/Api/AuthController.php`: organiza o fluxo HTTP.
- `api/app/Http/Resources/UserResource.php`: expõe id, name, email e role, sem senha ou remember_token.
- `api/app/Http/Middleware/EnsureAdmin.php`: exige o perfil admin nas operações administrativas.
- `api/routes/api.php`: reúne as rotas; não existem cópias de login/logout em `api/routes/web.php`.

`StoreUserRequest` continua separado: é usado no CRUD administrativo de usuários. O cadastro público usa `RegisterRequest`, como no tutorial.

## Fluxo de uma chamada

1. O navegador pede `GET /sanctum/csrf-cookie`, que responde 204 e prepara os cookies.
2. Envia `POST /api/register` ou `POST /api/login`, com cookies e `X-XSRF-TOKEN`.
3. O middleware stateful prepara a sessão; o FormRequest valida os campos.
4. O Controller chama o AuthService. Cadastro cria a conta; login verifica as credenciais com `Auth::guard('web')->attempt()`.
5. O Service regenera o identificador da sessão; o Controller devolve os dados do usuário em `data`. Cookies seguem nos headers.
6. Nas próximas chamadas, `auth:sanctum` identifica a conta pela sessão.
7. `POST /api/logout` encerra o login, invalida a sessão e regenera o token CSRF. Depois disso, `GET /api/me` responde 401.

## Configuração local

Os valores abaixo estão em `api/.env.example`, que é apenas o modelo. A configuração local fica somente no `.env` da raiz e chega ao Laravel pelo `env_file` do Compose. Ao migrar de uma configuração que usava as portas 5173 ou 8000 no navegador, atualize esse `.env` e aplique as alterações:

```bash
docker compose exec api php artisan config:clear
docker compose up -d --force-recreate --no-deps --wait api
```

```dotenv
APP_URL=http://localhost
FRONTEND_URL=http://localhost
SANCTUM_STATEFUL_DOMAINS=localhost
SESSION_SECURE_COOKIE=false
```

No HTTP local, deixe `SESSION_DOMAIN` sem preencher e use `localhost` em todas as chamadas. No Vue, use URLs relativas, como `/api/me`, para passar pelo mesmo proxy. Em HTTPS de produção, `SESSION_SECURE_COOKIE` deve ser true.

## Testar no navegador

Abra `http://localhost` e execute o exemplo no console do navegador. Use uma conta local; o exemplo usa o cliente criado pelo [seeder](seeders.md).

```javascript
const headers = { Accept: 'application/json' };
const csrf = () => decodeURIComponent(document.cookie
  .split('; ').find(cookie => cookie.startsWith('XSRF-TOKEN='))
  ?.slice('XSRF-TOKEN='.length) ?? '');
const post = (path, body) => fetch(path, {
  method: 'POST',
  headers: { ...headers, 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrf() },
  body: JSON.stringify(body),
});

console.log((await fetch('/sanctum/csrf-cookie', { headers })).status); // 204
console.log((await post('/api/login', {
  email: 'customer@ticogames.test', password: 'password',
})).status); // 200
console.log((await fetch('/api/me', { headers })).status); // 200
console.log((await post('/api/logout')).status); // 204
console.log((await fetch('/api/me', { headers })).status); // 401
```

O navegador envia o cookie de sessão nas requisições à mesma origem. O exemplo lê o `XSRF-TOKEN` antes de cada POST, pois o login regenera a sessão e pode atualizar o cookie.

## Testar no Thunder Client

Use a mesma sessão de cookies para todas as chamadas. Configure `Accept: application/json` e `Origin: http://localhost`. O Sanctum usa o `Origin` ou o `Referer` para reconhecer chamadas do frontend. No navegador esses cabeçalhos são gerenciados automaticamente.

Primeiro faça GET em `http://localhost/sanctum/csrf-cookie`. Para operações que alteram dados, envie `Content-Type: application/json` e o valor URL-decodificado do cookie `XSRF-TOKEN` no header `X-XSRF-TOKEN`. Preserve e envie também o cookie de sessão. Não adicione Authorization Bearer. Após logout, prepare novamente o CSRF antes de outro login.

| Método e rota | Corpo | Resposta |
| --- | --- | --- |
| POST `/api/register` | name, email, password, password_confirmation | 201, usuário em data, sessão iniciada. |
| POST `/api/login` | email, password | 200, usuário em data, sessão iniciada. |
| GET `/api/me` | Sem corpo. | 200 com conta; 401 sem sessão. |
| POST `/api/logout` | Sem campos obrigatórios. | 204 sem corpo. |

Credenciais inválidas retornam 422. Campos inválidos retornam 422 com `errors`. Ausência ou divergência do CSRF nas chamadas stateful retorna 419. Acesso autenticado sem o perfil necessário retorna 403. O throttle da aula limita tentativas de cadastro/login e responde 429 quando excedido.

`GET /api/users` exige uma sessão de administrador: visitante recebe 401, cliente recebe 403 e administrador recebe 200. As rotas privadas da API respondem 401 em JSON também sem `Accept: application/json`, sem tentar redirecionar para uma rota web chamada `login`. No Insomnia, faça o login na mesma sessão de cookies e envie `Origin: http://localhost` para que o Sanctum reconheça essa sessão.

Pelo novo proxy, a conta de demonstração passou pelo fluxo real: CSRF 204, login sem token 419, login com token 200, `/api/me` 200, logout 204 e `/api/me` 401. A suíte Laravel passou com 44 testes e 664 asserções no contêiner FrankenPHP.

## Verificações anteriores à troca do proxy

- [x] Suíte completa: 44 testes aprovados, 664 asserções, nenhum ignorado, em SQLite em memória.
- [x] Cadastro não permite escolher admin, salva hash e inicia sessão.
- [x] Validação do cadastro e limite de tentativas de login testados.
- [x] Login, logout, dados da conta e acesso por perfil testados.
- [x] HTTP real: 419 sem CSRF, 204 em csrf-cookie, 201 no cadastro, 200 no login/me, 403 na escrita do catálogo por cliente, 204 no logout e 401 em me após sair.
- [x] CORS real: origem localhost:5173 permitida com credenciais.
- [x] Cadastro e login sem sessão retornam 419. O teste confirma que o cadastro não cria conta nessa condição.
- [x] Pint aplicado aos arquivos PHP desta entrega.

A suíte usou SQLite em memória. A verificação HTTP criou uma conta descartável em um arquivo SQLite temporário no contêiner. O banco MySQL do projeto ficou intacto.

Os novos CRUDs e o fluxo de pedidos estão em [account-checkout.md](account-checkout.md). Próxima etapa: telas e integração Vue, somente após solicitação do usuário.
