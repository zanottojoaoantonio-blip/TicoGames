const registerForm = document.getElementById('register-form');
const registerFields = document.getElementById('register-fields');
const registerButton = document.getElementById('register-submit');
const registerStatus = document.getElementById('register-status');

async function submitRegistration(event) {
    event.preventDefault();

    if (
        registerForm.hidden
        || registerButton.disabled
        || Store.authenticating
    ) {
        return;
    }

    const values = Object.fromEntries(new FormData(registerForm));

    registerButton.disabled = true;
    Store.authenticating = true;
    registerFields.disabled = true;
    registerForm.setAttribute('aria-busy', 'true');
    registerStatus.textContent = 'Criando sua conta...';
    Store.fieldErrors(registerForm, {});
    let focusTarget;

    try {
        await Store.ready.catch(() => {});
        const response = await Store.request('post', '/api/register', {
            name: values.name.trim(),
            email: values.email.trim(),
            password: values.password,
            password_confirmation: values.password_confirmation,
        });
        const user = Store.account(response);

        registerForm.reset();
        registerForm.hidden = true;
        registerStatus.textContent = '';
        document.getElementById('login-form').hidden = true;
        document.getElementById('login-password').value = '';
        Store.notice(`Conta criada. Olá, ${user.name}. Você já está conectado.`);

        await Store.acceptSession(user);
        document.getElementById('login-success').hidden = false;
        bootstrap.Tab.getOrCreateInstance(document.getElementById('login-tab')).show();
        document.getElementById('login-continue').focus();
    } catch (error) {
        registerStatus.textContent = Store.error(error);
        focusTarget = Store.fieldErrors(registerForm, error) ?? registerButton;
    } finally {
        Store.authenticating = false;
        registerFields.disabled = false;
        registerButton.disabled = false;
        registerForm.setAttribute('aria-busy', 'false');
        focusTarget?.focus();
    }
}

registerForm.addEventListener('submit', submitRegistration);
registerButton.disabled = false;
