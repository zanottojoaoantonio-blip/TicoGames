# Etapa 8 — autenticação por sessão com Sanctum

Implementada em 02/11/2026, seguindo os modelos da [Aula 2 — Laravel Sanctum](https://victor-raphael17.github.io/studies/sanctum.html#passo-1) indicada pelo usuário.

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
| Rotas em `routes/api.php` | Cadastro/login públicos com `throttle:6,1`; `me` e `logout` no grupo `auth:sanctum`. |
| `statefulApi()`, CORS e domínio stateful | Configurados para o Vue em `http://localhost:5173` e a API em `http://localhost:8000`. |

Adaptações às regras já combinadas: o catálogo pode ser consultado por visitantes; somente administradores o modificam. `UserResource` inclui também `role` para a interface distinguir cliente e administrador. O cadastro público sempre usa o padrão `customer` do banco: `role` não é validado nem está em `$fillable`. O tutorial protege todo o catálogo e usa frontend na porta 80; essas duas escolhas não se aplicam à nossa loja.

## Arquivos e responsabilidades

- `bootstrap/app.php`: ativa `statefulApi()` e registra o middleware `admin`.
- `config/sanctum.php`: inclui os hosts e portas locais reconhecidos como frontend stateful.
- `config/cors.php`: permite a origem exata do Vue, com credenciais.
- `app/Http/Requests/RegisterRequest.php` e `LoginRequest.php`: validam a entrada.
- `app/Services/AuthService.php`: cria conta e controla autenticação e sessão.
- `app/Http/Controllers/Api/AuthController.php`: organiza o fluxo HTTP.
- `app/Http/Resources/UserResource.php`: expõe id, name, email e role, sem senha ou remember_token.
- `app/Http/Middleware/EnsureAdmin.php`: exige o perfil admin nas operações administrativas.
- `routes/api.php`: reúne as rotas; não existem cópias de login/logout em `routes/web.php`.

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

Os valores já estão no `.env.example`. Os defaults versionados também atendem ao ambiente local atual, sem precisar substituir o `.env` existente:

```dotenv
FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000,localhost,127.0.0.1:8000
SESSION_SECURE_COOKIE=false
```

No HTTP local, mantenha `SESSION_DOMAIN` sem domínio específico. Use o mesmo host no frontend e na API: não misture localhost e 127.0.0.1 na mesma sessão. Se mudar host ou porta do frontend, ajuste tanto CORS quanto os domínios stateful. Em HTTPS de produção, `SESSION_SECURE_COOKIE` deve ser true.

Para consumir a API no Vue, a etapa 9 usará uma instância Axios como a da aula, com os endereços do TicoGames:

```javascript
const api = axios.create({
  baseURL: 'http://localhost:8000',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
});

await api.get('/sanctum/csrf-cookie');
await api.post('/api/login', { email, password });
const me = await api.get('/api/me');
await api.post('/api/logout');
```

Este é um exemplo de consumo; as telas e a integração do Vue ainda não foram implementadas.

## Testar no Thunder Client

Use a mesma sessão de cookies para todas as chamadas. Configure `Accept: application/json` e `Origin: http://localhost:5173`. O header Origin permite que o Sanctum reconheça a requisição como stateful; no navegador ele é enviado automaticamente nas chamadas entre essas origens.

Primeiro faça GET em `http://localhost:8000/sanctum/csrf-cookie`. Para operações que alteram dados, envie `Content-Type: application/json` e o valor URL-decodificado do cookie `XSRF-TOKEN` no header `X-XSRF-TOKEN`. Preserve e envie também o cookie de sessão. Não adicione Authorization Bearer. Após logout, prepare novamente o CSRF antes de outro login.

| Método e rota | Corpo | Resposta |
| --- | --- | --- |
| POST `/api/register` | name, email, password, password_confirmation | 201, usuário em data, sessão iniciada. |
| POST `/api/login` | email, password | 200, usuário em data, sessão iniciada. |
| GET `/api/me` | Sem corpo. | 200 com conta; 401 sem sessão. |
| POST `/api/logout` | Sem campos obrigatórios. | 204 sem corpo. |

Credenciais inválidas retornam 422. Campos inválidos retornam 422 com `errors`. Ausência ou divergência do CSRF nas chamadas stateful retorna 419. Acesso autenticado sem o perfil necessário retorna 403. O throttle da aula limita tentativas de cadastro/login e responde 429 quando excedido.

## Verificações executadas

- [x] Suíte completa: 40 testes aprovados, 511 asserções, nenhum ignorado, em SQLite em memória.
- [x] Cadastro não permite escolher admin, salva hash e inicia sessão.
- [x] Validação do cadastro e limite de tentativas de login testados.
- [x] Login, logout, dados da conta e acesso por perfil testados.
- [x] HTTP real: 204 no CSRF, 419 sem header CSRF, 200 no login/me, 204 no logout e 401 em me após sair.
- [x] CORS real: origem localhost:5173 permitida com credenciais.
- [x] HTTP real: POST vazio de categoria autenticado retorna 422, sem criar registro; sem CSRF retorna 419.
- [x] Pint aplicado aos arquivos PHP desta entrega.

Os testes automatizados não gravaram no MySQL. A verificação HTTP usou a conta administrativa de demonstração existente e somente consultou dados ou enviou entradas rejeitadas; não criou contas, produtos, carrinhos ou pedidos reais. Os cookies não foram registrados na documentação.

Os novos CRUDs e o fluxo de pedidos estão em [account-checkout.md](account-checkout.md). Próxima etapa: telas e integração Vue, somente após solicitação do usuário.
