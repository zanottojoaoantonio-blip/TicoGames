# Login e cadastro no frontend

login.html carrega os formulários e login.js/register.js. Ambos usam validação nativa, mensagens por campo e bloqueio de controles durante o envio. Senhas não são registradas no console nem salvas em localStorage.

## Sequência

1. Aguarda a preparação da sessão/carrinho, para preservar o visitante.
2. GET /sanctum/csrf-cookie prepara cookies e CSRF.
3. POST /api/login envia email/password; cadastro envia name/email/password/password_confirmation.
4. AuthService valida, funde o visitante e inicia a sessão.
5. Store.acceptSession lê GET /api/cart, apresenta ajustes e atualiza a navegação.

O login pela API tem o mesmo comportamento: a fusão pertence ao Service. O frontend não chama POST /api/cart/merge.

## Estado da interface

Durante o envio, fieldset/botão ficam desabilitados e aria-busy sinaliza espera. Erros 422 aparecem nos campos, preservam os valores e levam o foco ao primeiro inválido. Credenciais inválidas não consomem o carrinho.

Falhas 419/429/rede não repetem a senha automaticamente. Após sucesso, o formulário é ocultado, senha é limpa e o link de continuação recebe foco. O parâmetro next aceita somente destinos locais previstos: checkout, pedidos, admin e perfil.

O cadastro sempre recebe role customer; o navegador não pode escolher admin. Se a fusão falhar, o cadastro é desfeito. Consulte [autenticação](authentication.md) para cookies, CSRF e detalhes do backend.

Testes: login.test.cjs, pages.test.cjs e store.test.cjs, além das regressões de API em AccountAndCheckoutTest.
