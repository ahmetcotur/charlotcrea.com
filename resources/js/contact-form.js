(() => {
    const nav = document.getElementById('nav');
    const toggle = document.querySelector('.mobile-toggle');
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && nav?.classList.contains('open')) { nav.classList.remove('open'); toggle?.setAttribute('aria-expanded', 'false'); } });
    document.querySelectorAll('[data-enquiry-form]').forEach(form => {
        const service = form.querySelector('[data-service-select]');
        const help = form.querySelector('[data-service-help]');
        const update = () => { if (!help || !service?.value) return; help.textContent = service.value === 'Photography' ? help.dataset.photo : service.value === 'Film production' ? help.dataset.film : help.dataset.both; };
        service?.addEventListener('change', update); update();
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (!form.reportValidity()) return;
            const button = form.querySelector('button[type=submit]');
            const success = form.querySelector('[data-form-success]');
            const error = form.querySelector('[data-form-error]');
            const original = button.textContent;
            button.disabled = true; button.textContent = document.documentElement.lang === 'tr' ? 'Gönderiliyor…' : 'Sending…';
            error.hidden = true; success.hidden = true;
            try {
                const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form) });
                const result = await response.json();
                if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Please try again.');
                success.textContent = result.message; success.hidden = false;
                form.querySelectorAll('input,select,textarea').forEach(field => { field.disabled = true; });
                button.textContent = document.documentElement.lang === 'tr' ? 'Gönderildi ✓' : 'Sent ✓';
                success.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } catch (failure) { error.textContent = failure.message; error.hidden = false; button.disabled = false; button.textContent = original; }
        });
    });
})();
