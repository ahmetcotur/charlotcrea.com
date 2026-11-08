(() => {
    const video = document.querySelector('.hero-bg-video');
    const toggle = document.querySelector('.hero-playback');
    if (!video || !toggle) return;
    const reduce = matchMedia('(prefers-reduced-motion: reduce)');
    const tr = document.documentElement.lang === 'tr';
    let userPaused = false;
    let visible = true;
    video.muted = true;
    video.defaultMuted = true;
    video.playsInline = true;
    function label() {
        toggle.textContent = video.paused ? (tr ? 'Videoyu oynat' : 'Play video') : (tr ? 'Videoyu duraklat' : 'Pause video');
        toggle.setAttribute('aria-pressed', String(!video.paused));
    }
    function play() {
        video.muted = true;
        const attempt = video.play();
        if (attempt) attempt.catch(label);
    }
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        if (video.paused) {
            userPaused = false;
            play();
        } else {
            userPaused = true;
            video.pause();
        }
    });
    ['play', 'pause', 'error', 'emptied'].forEach(event => video.addEventListener(event, label));
    function resume() {
        if (!userPaused && !reduce.matches && visible && !document.hidden && !document.querySelector('.film-dialog[open]')) play();
    }
    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        if (visible) resume();
        else video.pause();
    }).observe(video);
    document.addEventListener('visibilitychange', () => document.hidden ? video.pause() : resume());
    window.addEventListener('pageshow', resume);
    video.addEventListener('loadeddata', resume);
    reduce.addEventListener('change', () => reduce.matches ? video.pause() : resume());
    if (reduce.matches) video.pause();
    label();
    resume();
})();
