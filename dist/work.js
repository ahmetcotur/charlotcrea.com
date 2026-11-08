(() => {
    const lang = document.documentElement.lang;
    const dialog = document.querySelector('.film-dialog');
    if (dialog) {
        const host = dialog.querySelector('.film-player-host');
        const caption = dialog.querySelector('.film-player-caption');
        let opener, previousOverflow = '', backgrounds = [];
        function release() {
            const video = host.querySelector('video');
            if (video) {
                video.pause();
                video.removeAttribute('src');
                video.load();
            }
            host.replaceChildren();
        }
        document.querySelectorAll('[data-video-src],[data-drive-id]').forEach(button => button.addEventListener('click', () => {
            opener = button;
            previousOverflow = document.body.style.overflow;
            release();
            caption.replaceChildren();
            backgrounds = [...document.querySelectorAll('.hero-bg-video,.home-motion-video')].filter(video => !video.paused);
            backgrounds.forEach(video => video.pause());
            const title = button.dataset.filmTitle || 'Charlot Crea';
            if (button.dataset.videoSrc) {
                const video = document.createElement('video');
                video.controls = true;
                video.playsInline = true;
                video.setAttribute('playsinline', '');
                video.preload = 'metadata';
                video.setAttribute('aria-label', title);
                host.append(video);
                dialog.showModal();
                // Start loading only after the player is visible to WebKit.
                video.src = button.dataset.videoSrc;
                video.load();
                video.play().catch(() => { /* Native controls remain available if autoplay is blocked. */ });
                video.addEventListener('error', () => {
                    const retry = document.createElement('button');
                    retry.type = 'button';
                    retry.className = 'btn light';
                    retry.textContent = lang === 'tr' ? 'Videoyu yeniden dene' : 'Retry video';
                    retry.addEventListener('click', () => {
                        retry.remove();
                        video.load();
                        video.play().catch(() => {});
                    });
                    host.append(retry);
                }, {once: true});
            } else {
                const frame = document.createElement('iframe');
                frame.src = 'https://drive.google.com/file/d/' + encodeURIComponent(button.dataset.driveId) + '/preview';
                frame.title = title;
                frame.allow = 'autoplay; fullscreen; encrypted-media';
                frame.allowFullscreen = true;
                frame.referrerPolicy = 'strict-origin-when-cross-origin';
                host.append(frame);
                dialog.showModal();
            }
            caption.append(document.createTextNode(title));
            const original = document.createElement('a');
            original.href = button.dataset.videoSrc || 'https://drive.google.com/file/d/' + encodeURIComponent(button.dataset.driveId) + '/view';
            original.target = '_blank';
            original.rel = 'noopener noreferrer';
            original.textContent = lang === 'tr' ? 'Yeni sekmede aç ↗' : 'Open in new tab ↗';
            caption.append(original);
            document.body.style.overflow = 'hidden';
        }));
        dialog.querySelector('.film-close').addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => {
            release();
            document.body.style.overflow = previousOverflow;
            if (!document.hidden && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
                backgrounds.forEach(video => video.play().catch(() => {}));
            }
            backgrounds = [];
            opener?.focus();
        });
    }
    document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
        document.querySelectorAll('[data-filter]').forEach(b => b.setAttribute('aria-pressed', String(b === button)));
    }));
})();
