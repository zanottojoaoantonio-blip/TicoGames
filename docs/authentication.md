# Autenticação por sessão com Sanctum

A API usa Sanctum com sessão do Laravel. O cookie de sessão HttpOnly identifica o usuário; `XSRF-TOKEN` fornece o valor do header `X-XSRF-TOKEN`. Não há emissão de Bearer token ou armazenamento de senha no navegador.

## Fluxo

1. Consulte `GET /sanctum/csrf-cookie` pela origem configurada.
2. Envie `POST /api/login` com email/senha ou `POST /api/register` com nome, email, senha e confirmação.
3. O middleware `RequireFrontendSession` exige sessão stateful; os FormRequests validam os campos.
4. No login, `AuthService` usa `attemptWhen`: valida credenciais e executa `CartService::merge` antes de efetivar a autenticação. Falha na fusão não inicia o login.
5. No cadastro, criação do cliente e fusão pertencem à mesma transação; falha desfaz a conta.
6. Após sucesso, o Service regenera a sessão e remove `guest_cart_token`. A primeira consulta `GET /api/cart` retorna também os avisos da fusão.
7. `GET /api/me` retorna a conta; `POST /api/logout` invalida a sessão e regenera CSRF.

O frontend chama `Store.acceptSession` e apenas consulta o carrinho. Clientes diretos da API recebem o mesmo resultado, sem precisar chamar `POST /api/cart/merge`.

## Rotas e respostas

| Rota | Resultado |
| --- | --- |
| POST `/api/register` | 201, cliente em `data` e sessão iniciada |
| POST `/api/login` | 200, conta em `data` e sessão iniciada |
| GET `/api/me` | 200 autenticado; 401 visitante |
| POST `/api/logout` | 204, sessão encerrada |

Cadastro público sempre cria `customer`. `role` não é aceito para promoção a admin; senha e remember_token não são expostos. Login/cadastro/me usam `Cache-Control: no-store`.

Credenciais/campos inválidos retornam 422; sessão/CSRF inválidos, 419; após seis tentativas por minuto, 429. Conflito de limite na fusão retorna 409 preservando os carrinhos. Admin é exigido para modificar catálogo e administrar usuários/pedidos.

## Configuração local

```dotenv
APP_URL=http://localhost
FRONTEND_URL=http://localhost
SANCTUM_STATEFUL_DOMAINS=localhost
SESSION_SECURE_COOKIE=false
```

Deixe `SESSION_DOMAIN` vazio no ambiente HTTP local. Em outra origem, configure CORS com a origem exata e credenciais; inclua host/porta no Sanctum. Em HTTPS, use cookie seguro.

## Testar no navegador ou Swagger

No Swagger, execute primeiro `GET /sanctum/csrf-cookie`, depois login/cadastro. A interface envia os cookies e lê o token CSRF para as próximas operações.

Com Axios na loja:

```javascript
await axios.get('/sanctum/csrf-cookie');
await axios.post('/api/login', {
  email: 'customer@ticogames.test', password: 'password'
});
const cart = await axios.get('/api/cart');
```

No Thunder Client, mantenha o cookie jar, envie `Origin: http://localhost` e `Accept: application/json`; copie o valor decodificado de `XSRF-TOKEN` para `X-XSRF-TOKEN` nas escritas. Use contas fictícias conforme [seeders](seeders.md).

Regressões de login, cadastro, fusão automática, senha inválida e rollback estão em `AccountAndCheckoutTest`. PHPUnit não comprova CSRF real; a verificação HTTP usa o ambiente QA.
