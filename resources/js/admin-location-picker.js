(() => {
  const host = document.querySelector('[data-portfolio-locations]');
  const store = window.vm?.$store;
  if (!host || !store) return;
  const dialog = host.querySelector('[data-location-dialog]');
  const status = host.querySelector('[data-location-status]');
  const error = host.querySelector('[data-location-error]');
  const parent = host.querySelector('[data-location-parent]');
  const title = host.querySelector('[data-location-title]');
  const enTitle = host.querySelector('[data-location-en-title]');
  const save = host.querySelector('[data-location-save]');
  const selected = name => store.state.browser.selected[name] || [];
  let items = [], kind = 'country';
  const headers = {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': host.dataset.csrf};
  async function load() {
    const response = await fetch(host.dataset.optionsUrl, {headers});
    if (!response.ok) throw new Error('Yer listesi yüklenemedi. Sayfayı yenileyip tekrar deneyin.');
    items = (await response.json()).items;
  }
  function setSelection(name, values) {
    store.commit('addBrowsers', {browsers: {[name]: values}});
  }
  function pruneLocations() {
    const countries = new Set(selected('countries').map(item => Number(item.id)));
    const allowed = selected('locations').filter(item => countries.has(Number(items.find(row => Number(row.id) === Number(item.id))?.root_country_id)));
    if (allowed.length !== selected('locations').length) setSelection('locations', allowed);
  }
  load().then(() => {
    store.watch(state => JSON.stringify((state.browser.selected.countries || []).map(item => item.id)), () => pruneLocations());
  }).catch(reason => { status.textContent = reason.message; });
  host.querySelectorAll('[data-add-location]').forEach(button => button.addEventListener('click', async () => {
    kind = button.dataset.addLocation;
    error.textContent = ''; status.textContent = '';
    try { await load(); } catch (reason) { status.textContent = reason.message; return; }
    const countries = new Set(selected('countries').map(item => Number(item.id)));
    if (kind === 'point' && !countries.size) { status.textContent = 'Çekim noktası eklemek için önce ülke seçin.'; return; }
    host.querySelector('[data-location-heading]').textContent = kind === 'country' ? 'Yeni ülke ekle' : 'Yeni çekim noktası ekle';
    host.querySelector('[data-location-parent-label]').style.display = kind === 'country' ? 'none' : 'block';
    parent.replaceChildren();
    if (kind === 'point') items.filter(item => countries.has(Number(item.root_country_id))).forEach(item => {
      const option = document.createElement('option'); option.value = item.id; option.textContent = item.name; parent.append(option);
    });
    title.value = ''; enTitle.value = '';
    dialog.style.display = 'block'; dialog.showModal(); title.focus();
  }));
  dialog.addEventListener('close', () => { dialog.style.display = 'none'; });
  host.querySelector('[data-location-cancel]').addEventListener('click', () => dialog.close());
  save.addEventListener('click', async () => {
    if (!title.value.trim()) { error.textContent = 'Bir ad girin.'; title.focus(); return; }
    save.disabled = true; error.textContent = '';
    try {
      const response = await fetch(host.dataset.createUrl, {method: 'POST', headers, body: JSON.stringify({kind, title: title.value.trim(), en_title: enTitle.value.trim(), ...(kind === 'point' ? {parent_id: Number(parent.value)} : {})})});
      const data = await response.json();
      if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Kayıt eklenemedi.');
      items.push(data.item);
      const name = kind === 'country' ? 'countries' : 'locations';
      setSelection(name, [...selected(name), data.item]);
      status.textContent = data.message; dialog.close();
    } catch (reason) { error.textContent = reason.message; }
    finally { save.disabled = false; }
  });
})();
