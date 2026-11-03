(() => {
  const track = document.querySelector('.hero-filmstrip-track');
  if (!track || !track.children.length) return;
  const originals = [...track.children];
  originals.forEach((item, index) => {
    item.draggable = false;
    item.querySelector('img')?.setAttribute('draggable', 'false');
    const clone = item.cloneNode(true);
    clone.classList.remove('gallery-open');
    clone.dataset.filmstripOriginal = index;
    clone.setAttribute('aria-hidden', 'true');
    clone.tabIndex = -1;
    const image = clone.querySelector('img');
    if (image) image.loading = 'lazy';
    track.append(clone);
  });
  const reduce = matchMedia('(prefers-reduced-motion: reduce)');
  let x = 0, loopPoint = 0, down = false, moved = false, startClientX = 0, startX = 0, hovering = false, visible = true;
  function draw(value) { x = value; track.style.transform = `translateX(${x}px)`; }
  function measure() {
    loopPoint = track.children[originals.length].offsetLeft - originals[0].offsetLeft;
    if (loopPoint > 0 && x <= -loopPoint) draw(x % loopPoint);
  }
  measure();
  window.addEventListener('resize', measure, {passive:true});
  const section = track.closest('.hero');
  if (section) new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; }).observe(section);
  track.addEventListener('mouseenter', () => { hovering = true; });
  track.addEventListener('mouseleave', () => { hovering = false; });
  track.addEventListener('focusin', () => { hovering = true; });
  track.addEventListener('focusout', () => { hovering = false; });
  track.addEventListener('dragstart', event => event.preventDefault());
  track.addEventListener('pointerdown', event => {
    if (event.button !== 0) return;
    down = true; moved = false; startClientX = event.clientX; startX = x;
  });
  track.addEventListener('pointermove', event => {
    if (!down) return;
    const distance = event.clientX - startClientX;
    if (Math.abs(distance) > 4) {
      moved = true;
      if (!track.hasPointerCapture(event.pointerId)) track.setPointerCapture(event.pointerId);
    }
    if (moved) draw(Math.min(0, Math.max(-loopPoint, startX + distance)));
  });
  const end = event => { down = false; if (track.hasPointerCapture(event.pointerId)) track.releasePointerCapture(event.pointerId); };
  track.addEventListener('pointerup', end); track.addEventListener('pointercancel', end);
  track.addEventListener('click', event => {
    if (moved) { event.preventDefault(); event.stopImmediatePropagation(); moved = false; return; }
    const clone = event.target.closest('[data-filmstrip-original]');
    if (clone && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) {
      event.preventDefault(); originals[Number(clone.dataset.filmstripOriginal)].click();
    }
  }, true);
  let last = null;
  function tick(now) {
    const elapsed = last === null ? 0 : Math.min(now - last, 50); last = now;
    if (!down && !hovering && visible && !document.hidden && !reduce.matches && !document.querySelector('.gallery-dialog')?.open && loopPoint > 0) {
      draw(x - elapsed * .05);
      if (x <= -loopPoint) draw(x + loopPoint);
    }
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
})();
