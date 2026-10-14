"use strict";
(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["destinations"],{

/***/ "./src/js/components/destinations.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* unused harmony export initDestinations */
/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");

function initDestinations(root) {
  if (!root || root.dataset.destinationsReady === 'true') return;
  const list = root.querySelector('[data-destinations-list]');
  const toggle = root.querySelector('[data-destinations-toggle]');
  const label = root.querySelector('[data-destinations-label]');
  const extraItems = [...root.querySelectorAll('[data-destinations-extra]')];
  if (!list || !toggle || !label || !extraItems.length) return;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let expanded = false;
  const finish = () => {
    toggle.disabled = false;
    gsap__WEBPACK_IMPORTED_MODULE_0__["default"].set(list, {
      clearProps: 'height,overflow'
    });
    gsap__WEBPACK_IMPORTED_MODULE_0__["default"].set(extraItems, {
      clearProps: 'opacity,visibility,transform'
    });
  };
  const updateControl = isExpanded => {
    toggle.setAttribute('aria-expanded', String(isExpanded));
    toggle.classList.toggle('is-expanded', isExpanded);
    label.textContent = isExpanded ? 'Скрыть' : 'Показать ещё';
  };
  const expand = () => {
    const collapsedHeight = list.offsetHeight;
    extraItems.forEach(item => {
      item.hidden = false;
    });
    updateControl(true);
    if (reducedMotion.matches) {
      finish();
      return;
    }
    const expandedHeight = list.offsetHeight;
    toggle.disabled = true;
    gsap__WEBPACK_IMPORTED_MODULE_0__["default"].set(list, {
      height: collapsedHeight,
      overflow: 'hidden'
    });
    gsap__WEBPACK_IMPORTED_MODULE_0__["default"].fromTo(extraItems, {
      autoAlpha: 0,
      y: -8
    }, {
      autoAlpha: 1,
      y: 0,
      duration: 0.28,
      ease: 'power1.out',
      stagger: 0.018,
      overwrite: 'auto',
      onComplete: finish
    });
    gsap__WEBPACK_IMPORTED_MODULE_0__["default"].to(list, {
      height: expandedHeight,
      duration: 0.45,
      ease: 'power3.inOut',
      overwrite: 'auto'
    });
  };
  const collapse = () => {
    const expandedHeight = list.offsetHeight;
    extraItems.forEach(item => {
      item.hidden = true;
    });
    const collapsedHeight = list.offsetHeight;
    extraItems.forEach(item => {
      item.hidden = false;
    });
    updateControl(false);
    if (reducedMotion.matches) {
      extraItems.forEach(item => {
        item.hidden = true;
      });
      finish();
      return;
    }
    toggle.disabled = true;
    gsap__WEBPACK_IMPORTED_MODULE_0__["default"].set(list, {
      height: expandedHeight,
      overflow: 'hidden'
    });
    gsap__WEBPACK_IMPORTED_MODULE_0__["default"].to(extraItems, {
      autoAlpha: 0,
      y: -8,
      duration: 0.18,
      ease: 'power1.in',
      stagger: {
        each: 0.01,
        from: 'end'
      },
      overwrite: 'auto',
      onComplete: () => {
        extraItems.forEach(item => {
          item.hidden = true;
        });
        finish();
      }
    });
    gsap__WEBPACK_IMPORTED_MODULE_0__["default"].to(list, {
      height: collapsedHeight,
      duration: 0.4,
      ease: 'power3.inOut',
      overwrite: 'auto'
    });
  };
  toggle.addEventListener('click', () => {
    expanded = !expanded;
    if (expanded) expand();else collapse();
  });
  root.dataset.destinationsReady = 'true';
}
document.querySelectorAll('[data-destinations]').forEach(initDestinations);


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/destinations.js"));
/******/ }
]);