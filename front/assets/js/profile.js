const profileForm = document.getElementById('profile-form');
const profileStatus = document.getElementById('profile-status');
const profileFields = document.getElementById('profile-fields');

async function saveProfile(event) {
    event.preventDefault();

    if (Store.busy) {
        return;
    }

    const data = Object.fromEntries(new FormData(profileForm));
    data.name = data.name.trim();
    data.email = data.email.trim();

    if (!data.password) {
        delete data.password;
        delete data.password_confirmation;
    }

    Store.fieldErrors(profileForm, {});
    profileFields.disabled = true;
    let focusTarget;

    try {
        await Store.run(async () => {
            const user = await Store.request('patch', '/api/user', data);
            Store.user = Store.account(user);
        });

        document.getElementById('profile-password').value = '';
        document.getElementById('profile-password_confirmation').value = '';
        profileStatus.textContent = 'Dados atualizados.';
    } catch (error) {
        profileStatus.textContent = Store.error(error);
        focusTarget = Store.fieldErrors(profileForm, error);
    } finally {
        profileFields.disabled = false;
        focusTarget?.focus();
    }
}

async function initializeProfile() {
    try {
        await Store.session;

        if (!Store.user) {
            window.location.replace('login.html?next=perfil.html');
            return;
        }

        document.getElementById('profile-name').value = Store.user.name;
        document.getElementById('profile-email').value = Store.user.email;
        profileForm.hidden = false;
        profileStatus.textContent = '';
    } catch (error) {
        profileStatus.textContent = Store.error(error);
    }
}

profileForm.addEventListener('submit', saveProfile);

initializeProfile();
