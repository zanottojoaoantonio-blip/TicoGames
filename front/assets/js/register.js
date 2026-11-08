const registerForm = document.getElementById('register-form');
const registerFields = document.getElementById('register-fields');
const registerButton = document.getElementById('register-submit');
const registerStatus = document.getElementById('register-status');

registerForm.addEventListener('submit', async event => {
  event.preventDefault();
  if (registerForm.hidden || registerButton.disabled || Store.authenticating || !registerForm.reportValidity()) return;
  const values = Object.fromEntries(new FormData(registerForm));
  if (values.password !== values.password_confirmation) {
    registerStatus.textContent = 'A confirmação precisa ser igual à senha.';
    document.getElementById('register-password_confirmation').focus();
    return;
  }
  registerButton.disabled = true;
  Store.authenticating = true;
  registerFields.disabled = true;
  registerForm.setAttribute('aria-busy', 'true');
  registerStatus.textContent = 'Criando sua conta...';
  Store.fieldErrors(registerForm, {});
  let focus;
  try {
    await Store.ready.catch(() => {});
    const user = Store.account(await Store.request('post', '/api/register', {
      name: values.name.trim(), email: values.email.trim(),
      password: values.password, password_confirmation: values.password_confirmation,
    }));
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
    focus = Store.fieldErrors(registerForm, error) ?? registerButton;
  } finally {
    Store.authenticating = false;
    registerFields.disabled = false;
    registerButton.disabled = false;
    registerForm.setAttribute('aria-busy', 'false');
    focus?.focus();
  }
});
registerButton.disabled = false;
