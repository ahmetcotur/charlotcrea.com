document.addEventListener('alpine:init', () => {
    Alpine.data('copyLink', (endpoint) => ({
        copied: false,
        loading: false,
        async copy() {
            this.loading = true;
            try {
                const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                await navigator.clipboard.writeText(data.url);
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            } finally {
                this.loading = false;
            }
        },
    }));
});

function initKanban(board) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const urlTemplate = board.dataset.statusUrl;
    let dragged = null;

    board.querySelectorAll('.kanban-card').forEach((card) => {
        card.addEventListener('dragstart', () => {
            dragged = card;
            card.classList.add('dragging');
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            dragged = null;
        });
    });

    board.querySelectorAll('.kanban-column').forEach((column) => {
        column.addEventListener('dragover', (event) => {
            event.preventDefault();
            column.classList.add('drag-over');
        });
        column.addEventListener('dragleave', () => column.classList.remove('drag-over'));
        column.addEventListener('drop', async (event) => {
            event.preventDefault();
            column.classList.remove('drag-over');
            if (!dragged) return;

            const card = dragged;
            const newStatus = column.dataset.status;
            const previousStatus = card.dataset.status;
            if (newStatus === previousStatus) return;

            const emptyNotice = column.querySelector('.kanban-empty');
            if (emptyNotice) emptyNotice.remove();
            column.querySelector('.kanban-list').appendChild(card);
            card.dataset.status = newStatus;
            updateCounts(board);

            try {
                const response = await fetch(urlTemplate.replace('__id__', card.dataset.id), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ status: newStatus }),
                });
                if (!response.ok) throw new Error('status update failed');
            } catch (error) {
                location.reload();
            }
        });
    });
}

function updateCounts(board) {
    board.querySelectorAll('.kanban-column').forEach((column) => {
        const count = column.querySelectorAll('.kanban-card').length;
        const badge = column.querySelector('.kanban-column-head .count');
        if (badge) badge.textContent = count;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-kanban]').forEach(initKanban);

    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(initImageUpload);
    document.querySelectorAll('[data-destination-picker]').forEach(initDestinationPicker);

    document.querySelectorAll('form[data-loading-submit]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('[data-submit-button]');
            if (!button) return;
            button.disabled = true;
            button.classList.add('btn-loading');
            const label = button.querySelector('[data-submit-label]');
            const spinner = button.querySelector('[data-submit-spinner]');
            if (label) label.hidden = true;
            if (spinner) spinner.hidden = false;
        });
    });
});

function initImageUpload(input) {
    const zone = document.createElement('div');
    zone.className = 'image-dropzone';
    input.before(zone);
    if (!input.hasAttribute('aria-label')) input.setAttribute('aria-label', input.multiple ? 'Galeri görsellerini seç' : 'Görsel seç');
    const hint = document.createElement('p');
    hint.className = 'upload-hint';
    hint.textContent = input.multiple ? 'Fotoğrafları buraya sürükleyin veya dosya seçin' : 'Görseli buraya sürükleyin veya dosya seçin';
    const status = document.createElement('p');
    status.className = 'upload-status';
    status.setAttribute('aria-live', 'polite');
    const previews = document.createElement('div');
    previews.className = 'upload-selection';
    zone.append(hint, input, status, previews);
    let files = [];
    let urls = [];
    const sync = () => {
        const transfer = new DataTransfer();
        files.forEach(file => transfer.items.add(file));
        input.files = transfer.files;
        urls.forEach(url => URL.revokeObjectURL(url));
        urls = [];
        previews.replaceChildren();
        files.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'upload-selection-card';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            urls.push(img.src);
            img.alt = file.name;
            const name = document.createElement('span');
            name.textContent = file.name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'Kaldır';
            remove.setAttribute('aria-label', file.name + ' seçimini kaldır');
            remove.addEventListener('click', () => { files.splice(index, 1); sync(); input.dispatchEvent(new Event('input', { bubbles: true })); });
            card.append(img, name, remove);
            previews.append(card);
        });
        status.textContent = files.length ? files.length + ' görsel seçildi · Kaydettiğinizde yüklenecek' : 'PNG, JPG veya GIF · Görsel başına en fazla 8 MB';
        const original = input.dataset.previewTarget && document.querySelector(input.dataset.previewTarget);
        if (original) original.hidden = files.length > 0 || !original.getAttribute('src');
    };
    const select = incoming => {
        const valid = [];
        const errors = [];
        Array.from(incoming).forEach(file => {
            if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type) || file.size > 8 * 1024 * 1024) {
                errors.push(file.name + ': PNG, JPG veya GIF ve en fazla 8 MB olmalı.');
            } else valid.push(file);
        });
        files = input.multiple ? [...files, ...valid].filter((file, i, all) => all.findIndex(other => other.name === file.name && other.size === file.size && other.lastModified === file.lastModified) === i) : (valid.length ? valid.slice(0, 1) : files);
        sync();
        if (errors.length) status.textContent += ' · ' + errors.join(' ');
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };
    input.addEventListener('change', () => select(input.files));
    zone.addEventListener('dragover', event => { event.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', event => { if (!zone.contains(event.relatedTarget)) zone.classList.remove('drag-over'); });
    zone.addEventListener('drop', event => { event.preventDefault(); zone.classList.remove('drag-over'); select(event.dataTransfer.files); });
    sync();
}

function initDestinationPicker(picker) {
    const search = picker.querySelector('[data-destination-search]');
    const chips = picker.querySelector('[data-destination-chips]');
    const items = Array.from(picker.querySelectorAll('.destination-picker-item'));
    const groups = Array.from(picker.querySelectorAll('.destination-group'));
    const normalize = value => value.toLocaleLowerCase('tr').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ı/g, 'i');
    const refresh = () => {
        chips.replaceChildren();
        items.forEach(item => {
            const checkbox = item.querySelector('input');
            item.classList.toggle('is-selected', checkbox.checked);
            if (!checkbox.checked) return;
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'destination-chip';
            chip.textContent = item.textContent.trim() + ' ×';
            chip.setAttribute('aria-label', item.textContent.trim() + ' seçimini kaldır');
            chip.addEventListener('click', () => { checkbox.checked = false; checkbox.dispatchEvent(new Event('change', { bubbles: true })); });
            chips.append(chip);
        });
        if (!chips.childElementCount) chips.textContent = 'Henüz seçim yok · Ülke veya çekim noktası seçin';
    };
    picker.addEventListener('change', refresh);
    search.addEventListener('input', () => {
        const query = normalize(search.value.trim());
        groups.forEach(group => {
            const countryMatches = normalize(group.querySelector('summary').textContent).includes(query);
            group.querySelectorAll('.destination-picker-item').forEach(item => {
                item.hidden = !(countryMatches || normalize(item.textContent).includes(query));
            });
            group.hidden = !Array.from(group.querySelectorAll('.destination-picker-item')).some(item => !item.hidden);
            if (query) group.open = true;
        });
        picker.querySelector('[data-destination-empty]').hidden = groups.some(group => !group.hidden);
    });
    refresh();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-gallery-sort]').forEach(grid => {
        grid.addEventListener('click', event => {
            const button = event.target.closest('[data-gallery-move]');
            if (!button) return;
            const card = button.closest('.gallery-grid-item');
            const direction = Number(button.dataset.galleryMove);
            const neighbor = direction < 0 ? card.previousElementSibling : card.nextElementSibling;
            if (!neighbor) return;
            if (direction < 0) grid.insertBefore(card, neighbor);
            else grid.insertBefore(neighbor, card);
            grid.dispatchEvent(new Event('change', { bubbles: true }));
            button.focus();
        });
    });
    document.querySelectorAll('form[data-loading-submit]').forEach(form => {
        let dirty = false;
        let submitting = false;
        const notice = document.createElement('span');
        notice.className = 'unsaved-status';
        notice.setAttribute('role', 'status');
        form.querySelector('.form-actions')?.append(notice);
        const mark = () => { dirty = true; notice.textContent = 'Kaydedilmemiş değişiklikler var'; };
        form.addEventListener('input', mark);
        form.addEventListener('change', mark);
        form.addEventListener('submit', () => { submitting = true; });
        window.addEventListener('beforeunload', event => {
            if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
        });
    });
    document.querySelectorAll('.rich-editor').forEach(editor => {
        const counter = document.createElement('div');
        counter.className = 'help editor-word-count';
        editor.after(counter);
        const count = () => {
            const text = editor.textContent.trim();
            const words = text ? text.split(/\s+/).length : 0;
            counter.textContent = words + ' kelime · Yaklaşık ' + Math.max(1, Math.ceil(words / 200)) + ' dk okuma';
        };
        editor.addEventListener('input', count);
        editor.addEventListener('paste', event => {
            event.preventDefault();
            document.execCommand('insertText', false, event.clipboardData.getData('text/plain'));
            editor.dispatchEvent(new Event('input', { bubbles: true }));
        });
        count();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-content-preview]').forEach(button => {
        button.addEventListener('click', async () => {
            const form = button.closest('form');
            const group = button.closest('[data-preview-url]');
            const status = group.querySelector('[data-preview-status]');
            const preview = window.open('', '_blank');
            if (!preview) { status.textContent = 'Önizleme için açılır pencereye izin verin.'; return; }
            preview.opener = null;
            preview.document.body.textContent = 'Önizleme hazırlanıyor…';
            form.querySelectorAll('.rich-editor-field').forEach(field => {
                field.querySelector('.rich-editor-value').value = field.querySelector('.rich-editor').innerHTML;
            });
            const data = new FormData(form);
            data.delete('_method');
            Array.from(data.keys()).forEach(key => { if (data.get(key) instanceof File) data.delete(key); });
            data.set('preview_locale', button.dataset.contentPreview);
            button.disabled = true;
            status.textContent = 'Hazırlanıyor…';
            try {
                const response = await fetch(group.dataset.previewUrl, { method: 'POST', body: data, headers: { Accept: 'text/html' } });
                if (!response.ok || response.redirected) throw new Error('Önizleme hazırlanamadı. Alanları ve oturumunuzu kontrol edin.');
                const html = await response.text();
                if (preview.closed) return;
                preview.document.open();
                preview.document.write(html);
                preview.document.close();
                status.textContent = 'Önizleme ayrı sekmede açıldı.';
            } catch (error) {
                if (!preview.closed) preview.close();
                status.textContent = error.message;
            } finally { button.disabled = false; }
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-home-sections]').forEach(manager => {
        const mark = () => manager.dispatchEvent(new Event('change', { bubbles: true }));
        manager.addEventListener('click', event => {
            const button = event.target.closest('[data-section-move], [data-record-move]');
            if (!button) return;
            const isSection = button.hasAttribute('data-section-move');
            const card = button.closest(isSection ? '[data-home-section-card]' : '[data-section-record]');
            const direction = Number(isSection ? button.dataset.sectionMove : button.dataset.recordMove);
            const sibling = direction < 0 ? card.previousElementSibling : card.nextElementSibling;
            if (!sibling || !sibling.matches(isSection ? '[data-home-section-card]' : '[data-section-record]')) return;
            if (direction < 0) card.parentNode.insertBefore(card, sibling);
            else card.parentNode.insertBefore(sibling, card);
            manager.querySelectorAll('[data-section-position]').forEach((input, index) => { input.value = index + 1; });
            mark();
            button.focus();
        });
        manager.querySelectorAll('[data-home-section-card]').forEach(card => {
            const mode = card.querySelector('[data-section-mode]');
            const selection = card.querySelector('[data-section-selection]');
            const toggle = () => { if (selection) selection.hidden = mode.value !== 'manual'; };
            if (mode) { mode.addEventListener('change', toggle); toggle(); }
            const search = card.querySelector('[data-section-search]');
            search?.addEventListener('input', () => {
                const query = search.value.toLocaleLowerCase('tr');
                card.querySelectorAll('[data-section-record]').forEach(row => {
                    row.hidden = !row.querySelector('label').textContent.toLocaleLowerCase('tr').includes(query);
                });
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-home-section-card]').forEach(card => {
        const checkbox = card.querySelector('[data-section-visible]');
        const badge = document.createElement('span');
        badge.className = 'section-visibility-badge';
        card.querySelector('summary .muted').before(badge);
        const update = () => { badge.textContent = checkbox.checked ? 'Görünür' : 'Gizli'; badge.classList.toggle('is-hidden', !checkbox.checked); };
        checkbox.addEventListener('change', update);
        update();
    });
});
