"use strict";
(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["problems"],{

/***/ "./src/js/components/problems.js":
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {

/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");

document.querySelectorAll('[data-problems-slider]').forEach(viewport => {
  const track = viewport.querySelector('.problems__track');
  const cards = [...track.children];
  if (cards.length < 2) return;
  const tablet = window.matchMedia('(max-width: 1024px)');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let index = 0;
  let pointerId = null;
  let startX = 0;
  let startPosition = 0;
  let moved = false;
  const positionFor = target => {
    const maximum = Math.max(0, track.scrollWidth - viewport.clientWidth);
    return -Math.min(cards[target].offsetLeft - cards[0].offsetLeft, maximum);
  };
  const goTo = target => {
    index = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.utils.clamp(0, cards.length - 1, target);
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.to(track, {
      x: positionFor(index),
      duration: reducedMotion.matches ? 0 : 0.4,
      ease: 'power2.out',
      overwrite: true
    });
  };
  viewport.addEventListener('pointerdown', event => {
    if (!tablet.matches || event.pointerType === 'mouse' && event.button !== 0) return;
    pointerId = event.pointerId;
    startX = event.clientX;
    startPosition = Number(gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.getProperty(track, 'x'));
    moved = false;
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.killTweensOf(track);
    viewport.setPointerCapture(pointerId);
  });
  viewport.addEventListener('pointermove', event => {
    if (event.pointerId !== pointerId) return;
    const delta = event.clientX - startX;
    if (Math.abs(delta) > 5) moved = true;
    if (!moved) return;
    const minimum = positionFor(cards.length - 1);
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(track, {
      x: gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.utils.clamp(minimum, 0, startPosition + delta)
    });
  });
  const release = event => {
    if (event.pointerId !== pointerId) return;
    if (viewport.hasPointerCapture(pointerId)) viewport.releasePointerCapture(pointerId);
    pointerId = null;
    const delta = event.clientX - startX;
    goTo(moved && Math.abs(delta) > 40 ? index + (delta < 0 ? 1 : -1) : index);
  };
  viewport.addEventListener('pointerup', release);
  viewport.addEventListener('pointercancel', release);
  let touchStart = null;
  viewport.addEventListener('touchstart', event => {
    if (!tablet.matches || event.touches.length !== 1) return;
    const touch = event.touches[0];
    touchStart = { x: touch.clientX, y: touch.clientY, index };
  }, { passive: true });
  viewport.addEventListener('touchend', event => {
    if (!touchStart || event.changedTouches.length !== 1) return;
    const touch = event.changedTouches[0];
    const deltaX = touch.clientX - touchStart.x;
    const deltaY = touch.clientY - touchStart.y;
    const startIndex = touchStart.index;
    touchStart = null;
    if (index === startIndex && Math.abs(deltaX) > 40 && Math.abs(deltaX) > Math.abs(deltaY) * 1.2) {
      goTo(index + (deltaX < 0 ? 1 : -1));
    }
  }, { passive: true });
  viewport.addEventListener('touchcancel', () => { touchStart = null; }, { passive: true });
  viewport.addEventListener('keydown', event => {
    if (!tablet.matches || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
    event.preventDefault();
    goTo(index + (event.key === 'ArrowRight' ? 1 : -1));
  });
  const onResize = () => {
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.killTweensOf(track);
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(track, {
      x: tablet.matches ? positionFor(index) : 0
    });
  };
  window.addEventListener('resize', onResize);
  onResize();
});
document.querySelectorAll('.problems').forEach(root => {
  const dialog = root.querySelector('[data-problems-dialog]');
  const openButton = root.querySelector('[data-problems-dialog-open]');
  const closeButton = root.querySelector('[data-problems-dialog-close]');
  if (!dialog || !openButton) return;
  openButton.addEventListener('click', () => {
    if (dialog.open) return;
    dialog.showModal();
  });
  closeButton?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener('close', () => openButton.focus());
});

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/problems.js"));
/******/ }
]);
