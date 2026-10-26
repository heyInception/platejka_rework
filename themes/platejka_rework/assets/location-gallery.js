(() => {
  document.querySelectorAll('.location').forEach(section => {
    const thumbs = [...section.querySelectorAll('[data-location-gallery-thumb]')];
    const dialog = section.querySelector('[data-location-lightbox]');
    if (!dialog || !thumbs.length) return;
    const image = dialog.querySelector('[data-location-lightbox-image]');
    const count = dialog.querySelector('[data-location-lightbox-count]');
    let index = 0;
    let opener = null;
    let pointerX = null;
    const show = (next, animate = true) => {
      index = (next + thumbs.length) % thumbs.length;
      const thumb = thumbs[index];
      image.src = thumb.dataset.fullSrc;
      image.alt = thumb.dataset.fullAlt || `Фото ${index + 1}`;
      count.textContent = `${index + 1} / ${thumbs.length}`;
      if (animate && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        image.animate([
          { opacity: 0.35, transform: 'translateX(14px) scale(.98)' },
          { opacity: 1, transform: 'translateX(0) scale(1)' },
        ], { duration: 240, easing: 'ease-out' });
      }
    };
    thumbs.forEach((thumb, thumbIndex) => thumb.addEventListener('click', () => {
      opener = thumb;
      show(thumbIndex, false);
      dialog.showModal();
    }));
    dialog.querySelector('[data-location-lightbox-close]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-location-lightbox-prev]').addEventListener('click', () => show(index - 1));
    dialog.querySelector('[data-location-lightbox-next]').addEventListener('click', () => show(index + 1));
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => { opener?.focus(); opener = null; });
    dialog.addEventListener('keydown', event => {
      if (event.key === 'ArrowLeft') { event.preventDefault(); show(index - 1); }
      if (event.key === 'ArrowRight') { event.preventDefault(); show(index + 1); }
    });
    image.addEventListener('pointerdown', event => { pointerX = event.clientX; image.setPointerCapture(event.pointerId); });
    image.addEventListener('pointerup', event => {
      if (pointerX === null) return;
      const delta = event.clientX - pointerX;
      pointerX = null;
      if (Math.abs(delta) > 45) show(index + (delta < 0 ? 1 : -1));
    });
    image.addEventListener('pointercancel', () => { pointerX = null; });
  });
})();
