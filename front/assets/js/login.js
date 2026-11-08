// Este arquivo é carregado somente na página de acesso.
const loginForm = document.getElementById('login-form');
const loginFields = document.getElementById('login-fields');
const loginButton = document.getElementById('login-submit');
const loginStatus = document.getElementById('login-status');
const loginEmail = document.getElementById('login-email');
const loginPassword = document.getElementById('login-password');

loginForm.addEventListener('submit', async event => {
  event.preventDefault();
  if (loginForm.hidden || loginButton.disabled || Store.authenticating || !loginForm.reportValidity()) return;

  const credentials = { email: loginEmail.value.trim(), password: loginPassword.value };
  const headers = { Accept: 'application/json' };
  let focusTarget;
  for (const field of ['email', 'password']) {
    const input = document.getElementById(`login-${field}`);
    input.classList.remove('is-invalid');
    input.removeAttribute('aria-invalid');
    document.getElementById(`login-${field}-error`).textContent = '';
  }
  loginFields.disabled = true;
  Store.authenticating = true;
  loginButton.disabled = true;
  loginButton.textContent = 'Entrando...';
  loginForm.setAttribute('aria-busy', 'true');
  loginStatus.className = 'small text-secondary mb-3';
  loginStatus.textContent = 'Verificando seus dados...';

  try {
    await Store.ready.catch(() => {});
    // Preparar CSRF antes do POST; o Axios envia os cookies e o cabeçalho na mesma origem.
    await axios.get('/sanctum/csrf-cookie', { headers, timeout: 15000 });
    const response = await axios.post('/api/login', credentials, { headers, timeout: 15000 });
    const user = response.data.data;
    if (!Number.isSafeInteger(user?.id) || user.id <= 0 || typeof user.name !== 'string') {
      throw new Error('A API não retornou os dados esperados da conta.');
    }

    await Store.acceptSession(user);
    loginPassword.value = '';
    loginStatus.className = 'small text-success mb-3';
    loginStatus.textContent = `Login realizado. Olá, ${user.name}.`;
    loginForm.hidden = true;
    document.getElementById('login-success').hidden = false;
    const next = new URLSearchParams(window.location.search).get('next');
    if (['checkout.html', 'pedidos.html', 'admin.html', 'perfil.html'].includes(next)) document.getElementById('login-continue').href = next;
    document.getElementById('login-continue').focus();
  } catch (error) {
    // O erro completo do Axios pode conter a senha: não registrá-lo no console.
    loginStatus.className = 'small text-danger mb-3';
    const status = error.response?.status;
    focusTarget = loginButton;
    if (status === 422) {
      loginStatus.textContent = 'Confira os dados informados.';
      let firstInvalid;
      for (const field of ['email', 'password']) {
        const messages = error.response?.data?.errors?.[field];
        if (Array.isArray(messages) && typeof messages[0] === 'string') {
          const input = document.getElementById(`login-${field}`);
          input.classList.add('is-invalid');
          input.setAttribute('aria-invalid', 'true');
          document.getElementById(`login-${field}-error`).textContent = messages[0];
          firstInvalid ??= input;
        }
      }
      focusTarget = firstInvalid ?? loginButton;
    } else if (status === 419) {
      loginStatus.textContent = 'Sua sessão de acesso expirou. Tente entrar novamente.';
    } else if (status === 429) {
      loginStatus.textContent = 'Muitas tentativas de acesso. Aguarde um minuto e tente novamente.';
    } else {
      loginStatus.textContent = 'Não foi possível entrar agora. Tente novamente.';
    }
  } finally {
    Store.authenticating = false;
    loginFields.disabled = false;
    loginButton.disabled = false;
    loginButton.textContent = 'Entrar';
    loginForm.setAttribute('aria-busy', 'false');
    focusTarget?.focus();
  }
});

loginButton.disabled = false;
