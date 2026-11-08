(() => {
    const video = document.querySelector('.hero-bg-video');
    if (!video) return;
    const reduce = matchMedia('(prefers-reduced-motion: reduce)');
    let visible = true;
    video.muted = true;
    video.defaultMuted = true;
    video.playsInline = true;
    function resume() {
        if (reduce.matches || !visible || document.hidden || document.querySelector('.film-dialog[open]')) return;
        video.muted = true;
        const attempt = video.play();
        if (attempt) attempt.catch(() => {});
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
    resume();
})();
