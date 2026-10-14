(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["preloader"],{

/***/ "./src/js/components/preloader.js":
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {

"use strict";
/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");

const {
  consumePreloaderCookie
} = __webpack_require__("./src/js/components/preloader-state.cjs");
const preloader = document.querySelector('[data-preloader]');
if (preloader) {
  const body = document.body;
  const site = document.querySelector('.site-container');
  const logoTarget = preloader.querySelector('[data-preloader-logo]');
  const logo = document.querySelector('.header__logo-link')?.cloneNode(true);
  const fills = [...preloader.querySelectorAll('.preloader__fill')];
  const curtain = preloader.querySelector('.preloader__curtain');
  const shouldRun = consumePreloaderCookie(document.cookie, cookie => {
    document.cookie = cookie;
  });
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const removePreloader = () => {
    body.classList.remove('page__body--preloading');
    preloader.remove();
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(site, {
      clearProps: 'opacity,transform,visibility'
    });
    document.documentElement.dataset.pageReady = 'true';
    document.dispatchEvent(new CustomEvent('platejka:ready'));
  };
  if (!shouldRun || reduceMotion || !site || !logo || fills.length === 0) {
    removePreloader();
  } else {
    logo.removeAttribute('href');
    logo.setAttribute('tabindex', '-1');
    logoTarget.append(logo);
    body.classList.add('page__body--preloading');
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(site, {
      autoAlpha: 0,
      y: 20
    });
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(fills, {
      scaleX: 0,
      transformOrigin: 'left center'
    });
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(curtain, {
      xPercent: -100
    });
    const timeline = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.timeline({
      defaults: {
        ease: 'none'
      },
      onComplete: removePreloader
    });
    timeline.from(logo, {
      autoAlpha: 0,
      scale: 0.84,
      duration: 0.42,
      ease: 'power3.out'
    });
    for (let index = 0; index < fills.length; index += 2) {
      const pair = fills.slice(index, index + 2);
      timeline.to(pair, {
        scaleX: () => gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.utils.random(0.2, 0.82),
        duration: 0.16
      }).to(pair, {
        scaleX: 1,
        duration: 0.18
      });
    }
    timeline.to(curtain, {
      xPercent: 0,
      duration: 0.52,
      ease: 'power2.inOut'
    }, '+=0.25').to(site, {
      autoAlpha: 1,
      y: 0,
      duration: 0.45,
      ease: 'power3.out'
    }).set(preloader, {
      display: 'none'
    }, '<');
  }
}

/***/ }),

/***/ "./src/js/components/preloader-state.cjs":
/***/ ((module) => {

const PRELOADER_COOKIE_NAME = 'platejka-preloader-viewed';
const PRELOADER_COOKIE_MAX_AGE = 60 * 60 * 24;

const consumePreloaderCookie = (cookieString, writeCookie) => {
  try {
    const hasCookie = cookieString
      .split(';')
      .map((cookie) => cookie.trim())
      .some((cookie) => cookie.startsWith(`${PRELOADER_COOKIE_NAME}=`));

    if (hasCookie) return false;

    writeCookie(
      `${PRELOADER_COOKIE_NAME}=true; Max-Age=${PRELOADER_COOKIE_MAX_AGE}; Path=/; SameSite=Lax`,
    );
    return true;
  } catch (error) {
    return true;
  }
};

module.exports = { consumePreloaderCookie };


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/preloader.js"));
/******/ }
]);