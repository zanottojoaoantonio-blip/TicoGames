const checkoutStatus = document.getElementById('checkout-status');
const addressForm = document.getElementById('address-form');
const addressFields = document.getElementById('address-fields');
const addressStatus = document.getElementById('address-status');
const createOrderButton = document.getElementById('create-order');
const addressNames = Validacoes.addressFields;

let addresses = [];
let selectedAddress = null;
let editingAddress = null;
let orderUncertain = false;

function renderCheckoutSummary() {
    const target = document.getElementById('checkout-summary');
    const items = Store.cart.map(item => {
        const name = item.product?.name ?? 'Produto indisponível';
        const price = item.product ? Store.money(Validacoes.cents(item.product.price)) : '—';
        const subtotal = Store.money(Validacoes.cents(item.subtotal));
        const text = document.createElement('p');
        text.className = 'small';
        text.textContent = `${item.quantity} × ${name} · ${price} cada · subtotal ${subtotal}`;
        return text;
    });

    target.replaceChildren(...items);

    const shipping = document.createElement('p');
    const shippingCost = Store.money(Validacoes.cents(Store.shippingCost));
    shipping.textContent = `Frete: ${shippingCost} (gratuito para todos os pedidos).`;
    target.append(shipping);

    document.getElementById('checkout-total').textContent = Store.cartLoaded
        ? Store.money(Store.total())
        : '—';

    const hasUnavailableProduct = Store.cart.some(item => {
        return !item.product?.is_active || item.quantity > item.product.stock;
    });

    createOrderButton.disabled = orderUncertain
        || Store.busy
        || !Store.cartLoaded
        || !Store.cart.length
        || !selectedAddress
        || hasUnavailableProduct;

    addressFields.disabled = Store.busy;
}

function resetAddressForm() {
    editingAddress = null;
    addressForm.reset();
    Store.fieldErrors(addressForm, {});
    addressStatus.textContent = '';
    document.getElementById('address-title').textContent = 'Novo endereço';
}

async function loadAddresses() {
    const data = await UsuarioAPI.addresses();

    Validacoes.addresses(data);

    addresses = data;

    if (!addresses.some(address => address.id === selectedAddress)) {
        selectedAddress = null;
    }

    renderAddresses();
    renderCheckoutSummary();
}

function createAddressRow(address) {
    const row = document.createElement('div');
    row.className = 'border rounded p-3';

    const label = document.createElement('label');
    label.className = 'd-block mb-3';

    const radio = document.createElement('input');
    radio.type = 'radio';
    radio.name = 'selected-address';
    radio.value = address.id;
    radio.className = 'form-check-input me-2';
    radio.checked = selectedAddress === address.id;
    radio.disabled = Store.busy;
    radio.addEventListener('change', () => {
        selectedAddress = address.id;
        renderCheckoutSummary();
    });

    const complement = address.complement ? `, ${address.complement}` : '';
    const description = `${address.recipient_name} — ${address.street}, ${address.number}${complement}`
        + ` · ${address.neighborhood} · ${address.city}/${address.state} · ${address.postal_code}`;

    label.append(radio, document.createTextNode(description));

    const editButton = document.createElement('button');
    editButton.type = 'button';
    editButton.className = 'btn btn-sm btn-outline-primary me-2';
    editButton.textContent = 'Editar';
    editButton.disabled = Store.busy;
    editButton.addEventListener('click', () => editAddress(address.id));

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-sm btn-outline-danger';
    removeButton.textContent = 'Excluir';
    removeButton.disabled = Store.busy;
    removeButton.addEventListener('click', () => removeAddress(address));

    row.append(label, editButton, removeButton);
    return row;
}

function renderAddresses() {
    const target = document.getElementById('address-list');
    target.replaceChildren(...addresses.map(createAddressRow));

    if (!addresses.length) {
        const text = document.createElement('p');
        text.textContent = 'Cadastre um endereço para continuar.';
        target.append(text);
    }
}

async function editAddress(id) {
    try {
        await Store.run(async () => {
            const address = await UsuarioAPI.address(id);

            Validacoes.selectedAddress(address, id);

            resetAddressForm();
            editingAddress = address.id;

            for (const field of [...addressNames, 'complement']) {
                document.getElementById(`address-${field}`).value = address[field] ?? '';
            }

            document.getElementById('address-title').textContent = `Editar endereço #${address.id}`;
            addressForm.scrollIntoView({ block: 'center' });
        });

        document.getElementById('address-recipient_name').focus({ preventScroll: true });
    } catch (error) {
        addressStatus.textContent = Store.error(error);
    }
}

async function removeAddress(address) {
    const confirmed = await Store.confirm(
        'Excluir endereço?',
        `${address.street}, ${address.number} será removido dos seus endereços. Pedidos anteriores preservam sua cópia.`,
        'Excluir',
    );

    if (!confirmed) {
        return;
    }

    try {
        await Store.run(async () => {
            await UsuarioAPI.removeAddress(address.id);

            if (editingAddress === address.id) {
                resetAddressForm();
            }

            await loadAddresses();
        });

        addressStatus.textContent = 'Endereço excluído.';
    } catch (error) {
        addressStatus.textContent = Store.error(error);
    }
}

async function saveAddress(event) {
    event.preventDefault();

    if (Store.busy || addressFields.disabled) {
        return;
    }

    const payload = {};

    for (const [name, value] of new FormData(addressForm)) {
        payload[name] = value.trim();
    }

    payload.state = payload.state.toUpperCase();
    Store.fieldErrors(addressForm, {});
    addressStatus.textContent = 'Salvando endereço...';
    let focusTarget;

    try {
        await Store.run(async () => {
            const address = await UsuarioAPI.saveAddress(editingAddress, payload);
            Validacoes.savedAddress(address);

            selectedAddress = address.id;
            resetAddressForm();
            await loadAddresses();
        });

        addressStatus.textContent = 'Endereço salvo e selecionado.';
    } catch (error) {
        addressStatus.textContent = Store.error(error);
        focusTarget = Store.fieldErrors(addressForm, error);
    } finally {
        addressFields.disabled = false;
        focusTarget?.focus();
    }
}

async function createCheckoutOrder() {
    if (createOrderButton.disabled || Store.busy || !selectedAddress) {
        return;
    }

    try {
        await Store.run(async () => {
            const response = await UsuarioAPI.createOrder(selectedAddress);
            const order = Validacoes.createdOrder(response);

            window.location.assign(`pedidos.html?id=${order.id}`);
        });
    } catch (error) {
        orderUncertain = !error.response || error.response.status >= 500;
        checkoutStatus.textContent = Store.error(error);

        if (orderUncertain) {
            checkoutStatus.textContent += ' Confira Meus pedidos antes de continuar.';
        }

        renderCheckoutSummary();
    }
}

async function initializeCheckout() {
    try {
        await Store.session;

        if (!Store.user) {
            window.location.replace('login.html?next=checkout.html');
            return;
        }

        await Store.ready;
        await loadAddresses();
        document.getElementById('checkout-content').hidden = false;
        checkoutStatus.textContent = Store.cart.length
            ? 'Escolha um endereço e confira os produtos.'
            : 'Seu carrinho está vazio. Volte ao catálogo.';
        renderCheckoutSummary();
    } catch (error) {
        checkoutStatus.textContent = Store.error(error);
    }
}

addressForm.addEventListener('submit', saveAddress);

createOrderButton.addEventListener('click', createCheckoutOrder);

document.getElementById('address-cancel').addEventListener('click', () => {
    if (!Store.busy) {
        resetAddressForm();
    }
});

window.addEventListener('store:change', () => {
    renderCheckoutSummary();
    renderAddresses();
});

initializeCheckout();
