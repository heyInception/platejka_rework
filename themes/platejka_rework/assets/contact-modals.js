(() => {
  const dialog = document.querySelector('[data-consultation-dialog]');
  if (!dialog) return;

  const formWrap = dialog.querySelector('[data-contact-modal-form]');
  const success = dialog.querySelector('[data-contact-modal-success]');
  const configNode = document.querySelector('[data-contact-modal-config]');
  let settings = {};
  try { settings = JSON.parse(configNode?.textContent || '{}'); } catch (error) { settings = {}; }
  const config = settings.contexts || {};

  const setField = (root, name, value) => {
    const field = root?.querySelector(`[name="${name}"]`);
    if (field) field.value = value || '';
  };
  const contextFor = opener => opener.dataset.modalContext || 'header';
  const open = opener => {
    if (dialog.open) return;
    const context = contextFor(opener);
    const copy = config[context] || {};
    const title = dialog.querySelector('[data-contact-modal-title]');
    const text = dialog.querySelector('[data-contact-modal-text]');
    if (copy.title) title.textContent = copy.title;
    if (copy.text) text.textContent = copy.text;
    setField(dialog, 'platejka-request-source', opener.dataset.modalSource || context);
    setField(dialog, 'platejka-page-title', document.title);
    setField(dialog, 'platejka-page-url', window.location.href);
    dialog.dialogOpener = opener;
    dialog.showModal();
  };

  document.addEventListener('click', event => {
    const opener = event.target.closest('[data-contact-modal-open], .blog-button');
    if (!opener) return;
    event.preventDefault();
    open(opener);
  });
  dialog.querySelector('[data-contact-modal-close]')?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  dialog.addEventListener('close', () => {
    formWrap.hidden = false;
    success.hidden = true;
    formWrap.querySelector('form')?.reset();
    dialog.dialogOpener?.focus?.();
    dialog.dialogOpener = null;
  });
  document.addEventListener('wpcf7mailsent', event => {
    if (!dialog.open || !dialog.contains(event.target)) return;
    formWrap.hidden = true;
    success.hidden = false;
    success.focus?.();
  });

  document.querySelectorAll('[data-contact-dialog]').forEach(calculatorDialog => {
    const mount = calculatorDialog.querySelector('[data-cf7-mount]');
    if (!mount) return;
    const calculatorSuccess = document.createElement('div');
    calculatorSuccess.className = 'contact-modal__success';
    calculatorSuccess.hidden = true;
    const calculatorSuccessText = document.createElement('p');
    calculatorSuccessText.textContent = settings.success || 'Спасибо, заявка отправлена — скоро свяжемся.';
    calculatorSuccess.append(calculatorSuccessText);
    mount.after(calculatorSuccess);
    calculatorDialog.addEventListener('close', () => {
      mount.hidden = false;
      calculatorSuccess.hidden = true;
      mount.querySelector('form')?.reset();
    });
    document.addEventListener('wpcf7mailsent', event => {
      if (!calculatorDialog.open || !calculatorDialog.contains(event.target)) return;
      mount.hidden = true;
      calculatorSuccess.hidden = false;
    });
  });
})();
