const profileForm = document.getElementById('profile-form');
const profileStatus = document.getElementById('profile-status');
const profileFields = document.getElementById('profile-fields');
profileForm.addEventListener('submit', async event => {
  event.preventDefault(); if (Store.busy || !profileForm.reportValidity()) return;
  const data = Object.fromEntries(new FormData(profileForm));
  data.name = data.name.trim(); data.email = data.email.trim();
  if (!data.password) { delete data.password; delete data.password_confirmation; }
  else if (data.password !== data.password_confirmation) { profileStatus.textContent = 'A confirmação precisa ser igual à senha.'; return; }
  Store.fieldErrors(profileForm, {}); profileFields.disabled = true;
  let focus;
  try {
    await Store.run(async () => { Store.user = Store.account(await Store.request('patch', '/api/user', data)); });
    document.getElementById('profile-password').value = ''; document.getElementById('profile-password_confirmation').value = '';
    profileStatus.textContent = 'Dados atualizados.';
  } catch (error) { profileStatus.textContent = Store.error(error); focus = Store.fieldErrors(profileForm, error); }
  finally { profileFields.disabled = false; focus?.focus(); }
});
(async () => {
  try {
    await Store.session;
    if (!Store.user) { window.location.replace('login.html?next=perfil.html'); return; }
    document.getElementById('profile-name').value = Store.user.name;
    document.getElementById('profile-email').value = Store.user.email;
    profileForm.hidden = false; profileStatus.textContent = '';
  } catch (error) { profileStatus.textContent = Store.error(error); }
})();
