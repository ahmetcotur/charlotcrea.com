(() => {
  const input = document.querySelector('[data-page-search]');
  if (input) {
    const normalize = text => text.toLocaleLowerCase('tr-TR').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ı/g, 'i');
    const cards = [...document.querySelectorAll('[data-page-card]')];
    input.addEventListener('input', () => {
      const query = normalize(input.value.trim());
      let count = 0;
      cards.forEach(card => { card.hidden = !normalize(card.dataset.search).includes(query); if (!card.hidden) count++; });
      document.querySelectorAll('[data-page-group]').forEach(group => { group.hidden = ![...group.querySelectorAll('[data-page-card]')].some(card => !card.hidden); });
      document.querySelector('[data-page-count]').textContent = count + ' sayfa';
      document.querySelector('[data-page-empty]').hidden = count !== 0;
    });
  }
  const button = document.querySelector('[data-page-preview]');
  const dialog = document.querySelector('[data-page-preview-dialog]');
  if (!button || !dialog) return;
  const frame = dialog.querySelector('[data-page-preview-frame]');
  const status = document.querySelector('[data-page-preview-status]');
  button.addEventListener('click', async () => {
    const store = window.vm?.$store;
    if (!store) return;
    button.disabled = true; status.textContent = 'Önizleme hazırlanıyor…';
    try {
      await store.dispatch('getCurrentContent');
      frame.srcdoc = store.state.revision.currentContent;
      dialog.style.display = 'flex'; dialog.style.flexDirection = 'column'; dialog.showModal();
      status.textContent = '';
    } catch (reason) {
      const data = reason?.response?.data;
      status.textContent = data && typeof data === 'object' ? Object.values(data).flat().join(' ') : 'Önizleme açılamadı. Alanları kontrol edip tekrar deneyin.';
    } finally { button.disabled = false; }
  });
  dialog.querySelector('[data-page-preview-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => { frame.srcdoc = ''; dialog.style.display = 'none'; });
})();
