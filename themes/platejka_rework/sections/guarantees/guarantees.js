(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["guarantees"],{

/***/ "./src/js/components/guarantees.js":
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");

const {
  getClosestSlideIndex
} = __webpack_require__("./src/js/components/shipments-slider.cjs");
document.querySelectorAll('[data-guarantees-slider]').forEach(slider => {
  const cards = [...slider.children];
  if (!cards.length) return;
  let pointerId = null;
  let startX = 0;
  let startScrollLeft = 0;
  let startIndex = 0;
  const isMobile = () => window.matchMedia('(max-width: 1024px)').matches;
  const getStep = () => cards[1] ? cards[1].offsetLeft - cards[0].offsetLeft : 288;
  const snap = function () {
    let direction = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
    if (!isMobile()) return;
    const step = getStep();
    const current = getClosestSlideIndex(slider.scrollLeft, step, cards.length);
    const index = Math.min(cards.length - 1, Math.max(0, current + direction));
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.to(slider, {
      scrollLeft: index * step,
      duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 0.45,
      ease: 'power3.out',
      overwrite: 'auto'
    });
  };
  slider.addEventListener('pointerdown', event => {
    if (event.pointerType === 'touch' || event.button !== 0 || !isMobile()) return;
    pointerId = event.pointerId;
    startX = event.clientX;
    startScrollLeft = slider.scrollLeft;
    startIndex = getClosestSlideIndex(startScrollLeft, getStep(), cards.length);
    slider.setPointerCapture(pointerId);
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.killTweensOf(slider);
  });
  slider.addEventListener('pointermove', event => {
    if (event.pointerId !== pointerId) return;
    slider.scrollLeft = startScrollLeft - (event.clientX - startX);
  });
  const release = event => {
    if (event.pointerId !== pointerId) return;
    if (slider.hasPointerCapture(pointerId)) slider.releasePointerCapture(pointerId);
    pointerId = null;
    const distance = event.clientX - startX;
    const target = event.type === 'pointercancel' || Math.abs(distance) < getStep() * 0.25
      ? startIndex
      : Math.min(cards.length - 1, Math.max(0, startIndex + (distance < 0 ? 1 : -1)));
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.to(slider, {
      scrollLeft: target * getStep(),
      duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 0.45,
      ease: 'power3.out',
      overwrite: 'auto'
    });
  };
  slider.addEventListener('pointerup', release);
  slider.addEventListener('pointercancel', release);
  slider.addEventListener('keydown', event => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    snap(event.key === 'ArrowRight' ? 1 : -1);
  });
});

/***/ }),

/***/ "./src/js/components/shipments-slider.cjs":
/***/ ((module) => {

function getClosestSlideIndex(scrollLeft, step, count) {
  if (!Number.isFinite(step) || step <= 0 || !Number.isFinite(count) || count <= 0) return 0;
  return Math.min(count - 1, Math.max(0, Math.round(scrollLeft / step)));
}

module.exports = { getClosestSlideIndex };


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/guarantees.js"));
/******/ }
]);
