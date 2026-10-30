(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["shipments"],{

/***/ "./src/js/components/shipments.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* unused harmony exports initShipments, initShipmentsMotion, initShipmentsSlider */
/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");
/* harmony import */ var gsap_ScrollTrigger__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("./node_modules/gsap/ScrollTrigger.js");


const {
  getClosestSlideIndex
} = __webpack_require__("./src/js/components/shipments-slider.cjs");
gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.registerPlugin(gsap_ScrollTrigger__WEBPACK_IMPORTED_MODULE_1__.ScrollTrigger);
function initShipmentsMotion(root) {
  const images = [...root.querySelectorAll('[data-shipments-image]')];
  if (!images.length) return;
  const media = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.matchMedia();
  media.add('(prefers-reduced-motion: reduce)', () => {
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(images, {
      clearProps: 'all'
    });
  });
  media.add('(prefers-reduced-motion: no-preference)', () => {
    const animation = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.from(images, {
      autoAlpha: 0,
      xPercent: 18,
      yPercent: 16,
      scale: 0.88,
      duration: 0.85,
      stagger: 0.1,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: root,
        start: 'top 72%',
        once: true
      }
    });
    return () => animation.kill();
  });
}
function initShipmentsSlider(root) {
  const slider = root.querySelector('[data-shipments-slider]');
  const cards = slider ? [...slider.children] : [];
  if (!slider || !cards.length) return;
  let dragging = false;
  let pointerId = null;
  let startX = 0;
  let startScrollLeft = 0;
  let startIndex = 0;
  const getStep = () => cards[1] ? cards[1].offsetLeft - cards[0].offsetLeft : cards[0].getBoundingClientRect().width + 8;
  const snap = function () {
    let direction = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
    if (!window.matchMedia('(max-width: 1024px)').matches) return;
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
    if (event.pointerType === 'touch' || event.button !== 0 || !window.matchMedia('(max-width: 1024px)').matches) return;
    dragging = true;
    pointerId = event.pointerId;
    startX = event.clientX;
    startScrollLeft = slider.scrollLeft;
    startIndex = getClosestSlideIndex(startScrollLeft, getStep(), cards.length);
    slider.setPointerCapture(pointerId);
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.killTweensOf(slider);
  });
  slider.addEventListener('pointermove', event => {
    if (!dragging || event.pointerId !== pointerId) return;
    slider.scrollLeft = startScrollLeft - (event.clientX - startX);
  });
  const release = event => {
    if (!dragging || event.pointerId !== pointerId) return;
    dragging = false;
    if (slider.hasPointerCapture(pointerId)) slider.releasePointerCapture(pointerId);
    pointerId = null;
    const distance = event.clientX - startX;
    if (event.type === 'pointercancel' || Math.abs(distance) < getStep() * 0.25) {
      gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.to(slider, { scrollLeft: startIndex * getStep(), duration: 0.4, overwrite: 'auto' });
    } else {
      const target = Math.min(cards.length - 1, Math.max(0, startIndex + (distance < 0 ? 1 : -1)));
      gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.to(slider, { scrollLeft: target * getStep(), duration: 0.4, ease: 'power3.out', overwrite: 'auto' });
    }
  };
  slider.addEventListener('pointerup', release);
  slider.addEventListener('pointercancel', release);
  slider.addEventListener('keydown', event => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    snap(event.key === 'ArrowRight' ? 1 : -1);
  });
}
function initShipments(root) {
  if (root.dataset.shipmentsReady === 'true') return;
  root.dataset.shipmentsReady = 'true';
  initShipmentsMotion(root);
  initShipmentsSlider(root);
}
document.querySelectorAll('[data-shipments]').forEach(initShipments);


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
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/shipments.js"));
/******/ }
]);
