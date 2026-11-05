// Interações de apresentação: não há chamadas à API nem gravação de dados.
const previewModal = document.getElementById('preview-modal');

previewModal.addEventListener('show.bs.modal', (event) => {
  const button = event.relatedTarget;

  document.getElementById('preview-modal-title').textContent = button.dataset.previewTitle;
  document.getElementById('preview-modal-text').textContent = button.dataset.previewText;
  document.getElementById('preview-modal-confirm').textContent = button.dataset.previewConfirm;
});

// A edição apenas leva até a área visual do formulário, sem carregar ou alterar produtos.
document.querySelectorAll('[data-scroll-to]').forEach((button) => {
  button.addEventListener('click', () => {
    document.getElementById(button.dataset.scrollTo).scrollIntoView({
      behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
      block: 'start',
    });
  });
});
