"use strict";
(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["documents"],{

/***/ "./src/js/components/documents.js":
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {

/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");

document.querySelectorAll('[data-documents-slider]').forEach(slider => {
  const cards = [...slider.querySelectorAll('.document-card')];
  if (cards.length < 2) return;
  const mobile = window.matchMedia('(max-width: 1024px)');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let pointerId = null;
  let startX = 0;
  let startScrollLeft = 0;
  let dragged = false;
  const getStep = () => cards[1].offsetLeft - cards[0].offsetLeft;
  const getIndex = () => Math.round(slider.scrollLeft / getStep());
  const goTo = index => {
    const target = Math.max(0, Math.min(index, cards.length - 1)) * getStep();
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.to(slider, {
      scrollLeft: target,
      duration: reducedMotion.matches ? 0 : 0.4,
      ease: 'power3.out',
      overwrite: 'auto'
    });
  };
  slider.addEventListener('keydown', event => {
    if (!mobile.matches || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
    event.preventDefault();
    goTo(getIndex() + (event.key === 'ArrowRight' ? 1 : -1));
  });
  slider.addEventListener('pointerdown', event => {
    if (!mobile.matches || event.pointerType === 'touch' || event.button !== 0) return;
    pointerId = event.pointerId;
    startX = event.clientX;
    startScrollLeft = slider.scrollLeft;
    dragged = false;
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.killTweensOf(slider);
    slider.setPointerCapture(pointerId);
  });
  slider.addEventListener('pointermove', event => {
    if (event.pointerId !== pointerId) return;
    const distance = event.clientX - startX;
    if (Math.abs(distance) > 5) dragged = true;
    if (dragged) slider.scrollLeft = startScrollLeft - distance;
  });
  const release = event => {
    if (event.pointerId !== pointerId) return;
    slider.releasePointerCapture(pointerId);
    pointerId = null;
    if (dragged) goTo(getIndex());
  };
  slider.addEventListener('pointerup', release);
  slider.addEventListener('pointercancel', release);
  slider.addEventListener('click', event => {
    if (!dragged) return;
    event.preventDefault();
    dragged = false;
  }, true);
});

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/documents.js"));
/******/ }
]);