(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["shipments"],{

/***/ "./src/js/components/shipments.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* unused harmony exports initShipments, initShipmentsMotion */
/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");
/* harmony import */ var gsap_ScrollTrigger__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("./node_modules/gsap/ScrollTrigger.js");


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
function initShipments(root) {
  if (root.dataset.shipmentsReady === 'true') return;
  root.dataset.shipmentsReady = 'true';
  initShipmentsMotion(root);
  // Swiper initializes the responsive card slider separately.
}
document.querySelectorAll('[data-shipments]').forEach(initShipments);


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/shipments.js"));
/******/ }
]);
