"use strict";
(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["about-hero"],{

/***/ "./src/js/components/about-hero.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* unused harmony export initAboutHeroMotion */
/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");

const initAboutHeroMotion = root => {
  if (root.dataset.aboutHeroMotionReady === 'true') return;
  root.dataset.aboutHeroMotionReady = 'true';
  const visual = root.querySelector('[data-about-hero-visual]');
  const copy = [root.querySelector('.about-hero__eyebrow'), root.querySelector('.about-hero__title'), root.querySelector('.about-hero__lead')].filter(Boolean);
  const signatureReveal = root.querySelector('[data-about-hero-signature-reveal]');
  const proofs = [...root.querySelectorAll('[data-about-hero-proof]')];
  const metrics = [...root.querySelectorAll('[data-about-hero-metric]')];
  const animatedElements = [visual, ...copy, ...proofs, ...metrics].filter(Boolean);
  const play = () => {
    if (root.dataset.aboutHeroMotionPlayed === 'true') return;
    root.dataset.aboutHeroMotionPlayed = 'true';
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(animatedElements, {
      willChange: 'transform,opacity'
    });
    if (signatureReveal) {
      gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(signatureReveal, {
        scaleX: 0,
        transformOrigin: 'left center',
        willChange: 'transform'
      });
    }
    const timeline = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.timeline({
      defaults: {
        ease: 'power3.out'
      },
      onComplete: () => {
        gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(animatedElements, {
          clearProps: 'opacity,transform,visibility,willChange'
        });
        if (signatureReveal) gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(signatureReveal, {
          clearProps: 'transform,willChange'
        });
      }
    });
    if (visual) {
      timeline.fromTo(visual, {
        autoAlpha: 0.01
      }, {
        autoAlpha: 1,
        duration: 0.55,
        ease: 'power1.out'
      }, 0);
    }
    timeline.from(copy, {
      autoAlpha: 0,
      y: 24,
      stagger: 0.08,
      duration: 0.58
    }, 0.12);
    if (signatureReveal) {
      timeline.to(signatureReveal, {
        scaleX: 1,
        duration: 1.15,
        ease: 'power1.inOut'
      }, 0.45);
    }
    timeline.from(proofs, {
      autoAlpha: 0,
      y: 28,
      stagger: 0.12,
      duration: 0.68
    }, 0.55);
    timeline.from(metrics, {
      autoAlpha: 0,
      x: 32,
      stagger: 0.08,
      duration: 0.55
    }, 0.32);
  };
  if (document.documentElement.dataset.pageReady === 'true' || !document.querySelector('[data-preloader]')) {
    play();
  } else {
    document.addEventListener('platejka:ready', play, {
      once: true
    });
  }
};
document.querySelectorAll('[data-about-hero]').forEach(initAboutHeroMotion);


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/about-hero.js"));
/******/ }
]);