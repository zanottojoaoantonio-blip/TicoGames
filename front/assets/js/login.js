const loginForm = document.getElementById('login-form');
const loginFields = document.getElementById('login-fields');
const loginButton = document.getElementById('login-submit');
const loginStatus = document.getElementById('login-status');
const loginEmail = document.getElementById('login-email');
const loginPassword = document.getElementById('login-password');

function showLoginSuccess(user) {
    loginPassword.value = '';
    loginStatus.className = 'small text-success mb-3';
    loginStatus.textContent = `Login realizado. Olá, ${user.name}.`;
    loginForm.hidden = true;
    document.getElementById('login-success').hidden = false;

    const next = new URLSearchParams(window.location.search).get('next');
    const allowedPages = ['checkout.html', 'pedidos.html', 'admin.html', 'perfil.html'];
    const continueLink = document.getElementById('login-continue');

    if (allowedPages.includes(next)) {
        continueLink.href = next;
    }

    continueLink.focus();
}

function showLoginError(error) {
    // O erro completo do Axios pode conter a senha: não registrá-lo no console.
    loginStatus.className = 'small text-danger mb-3';
    const status = error.response?.status;

    if (status === 422) {
        loginStatus.textContent = 'Confira os dados informados.';
        return Store.fieldErrors(loginForm, error) ?? loginButton;
    }

    if (status === 419) {
        loginStatus.textContent = 'Sua sessão de acesso expirou. Tente entrar novamente.';
    } else if (status === 429) {
        loginStatus.textContent = 'Muitas tentativas de acesso. Aguarde um minuto e tente novamente.';
    } else {
        loginStatus.textContent = 'Não foi possível entrar agora. Tente novamente.';
    }

    return loginButton;
}

async function submitLogin(event) {
    event.preventDefault();

    if (
        loginForm.hidden
        || loginButton.disabled
        || Store.authenticating
    ) {
        return;
    }

    const credentials = {
        email: loginEmail.value.trim(),
        password: loginPassword.value,
    };
    let focusTarget;

    Store.fieldErrors(loginForm, {});
    loginFields.disabled = true;
    Store.authenticating = true;
    loginButton.disabled = true;
    loginButton.textContent = 'Entrando...';
    loginForm.setAttribute('aria-busy', 'true');
    loginStatus.className = 'small text-secondary mb-3';
    loginStatus.textContent = 'Verificando seus dados...';

    try {
        await Store.ready.catch(() => {});
        const user = await Store.request('post', '/api/login', credentials);
        await Store.acceptSession(user);
        showLoginSuccess(user);
    } catch (error) {
        focusTarget = showLoginError(error);
    } finally {
        Store.authenticating = false;
        loginFields.disabled = false;
        loginButton.disabled = false;
        loginButton.textContent = 'Entrar';
        loginForm.setAttribute('aria-busy', 'false');
        focusTarget?.focus();
    }
}

loginForm.addEventListener('submit', submitLogin);
loginButton.disabled = false;
