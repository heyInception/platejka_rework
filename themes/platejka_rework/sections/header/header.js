"use strict";
(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["header"],{

/***/ "./src/js/components/header.js":
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {

/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");

const header = document.querySelector('.header');
if (header) {
  const logo = header.querySelector('.header__logo-link');
  const headerTop = header.querySelector('.header__top');
  const headerBottom = header.querySelector('.header__bottom');
  const logoMarkParts = header.querySelectorAll('.header__logo-mark > path');
  const logoLetters = header.querySelectorAll('.header__logo-word > path');
  const menuToggles = [...header.querySelectorAll('.nav__toggle')];
  const menuControllers = [];
  const media = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.matchMedia();
  const reduceMotionQuery = '(prefers-reduced-motion: reduce)';
  const prefersReducedMotion = window.matchMedia(reduceMotionQuery).matches;
  const pageIsReady = () => document.documentElement.dataset.pageReady === 'true' || !document.querySelector('[data-preloader]');
  if (headerTop && headerBottom) {
    let isSticky = false;
    let stickyTween;
    const syncStickyHeader = () => {
      const shouldStick = header.getBoundingClientRect().top < 0;
      header.style.setProperty('--header-top-height', String(headerTop.offsetHeight) + 'px');
      header.style.setProperty('--header-sticky-height', String(headerTop.offsetHeight + headerBottom.offsetHeight) + 'px');
      if (shouldStick === isSticky) return;
      isSticky = shouldStick;
      stickyTween?.kill();
      header.classList.toggle('header--sticky', isSticky);
      headerTop.classList.toggle('header__top--fixed', isSticky);
      headerBottom.classList.toggle('header__bottom--fixed', isSticky);
      if (!isSticky) {
        gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(headerBottom, {
          clearProps: 'opacity,visibility,transform'
        });
        return;
      }
      const skipMotion = prefersReducedMotion || document.documentElement.classList.contains('menu-open');
      if (skipMotion) {
        gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(headerBottom, {
          autoAlpha: 1,
          clearProps: 'transform'
        });
        return;
      }
      stickyTween = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.fromTo(headerBottom, {
        autoAlpha: 0,
        y: -12
      }, {
        autoAlpha: 1,
        y: 0,
        duration: 0.28,
        ease: 'power2.out',
        overwrite: 'auto',
        onComplete: () => gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(headerBottom, {
          clearProps: 'opacity,visibility,transform'
        })
      });
    };
    syncStickyHeader();
    window.addEventListener('scroll', syncStickyHeader, {
      passive: true
    });
    window.addEventListener('resize', syncStickyHeader);
  }
  const getMenuDepth = toggle => {
    let depth = 1;
    let list = toggle.closest('ul');
    while (list?.classList.contains('nav__submenu')) {
      depth += 1;
      list = list.parentElement.closest('ul');
    }
    return depth;
  };
  const closeMenu = function (controller) {
    let closeDescendants = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
    const {
      item,
      toggle,
      label,
      title,
      timeline
    } = controller;
    if (closeDescendants) {
      menuControllers.filter(other => other !== controller && item.contains(other.item)).forEach(other => closeMenu(other, false));
    }
    toggle.setAttribute('aria-expanded', 'false');
    label.textContent = `Открыть подменю «${title}»`;
    item.classList.remove('nav__item--open');
    timeline.reverse();
  };
  const openMenu = controller => {
    menuControllers.filter(other => other !== controller && other.item.parentElement === controller.item.parentElement).forEach(other => closeMenu(other));
    const {
      item,
      toggle,
      label,
      title,
      timeline
    } = controller;
    toggle.setAttribute('aria-expanded', 'true');
    label.textContent = `Закрыть подменю «${title}»`;
    item.classList.add('nav__item--open');
    timeline.play();
  };
  menuToggles.forEach((toggle, index) => {
    const menuDepth = getMenuDepth(toggle);
    if (menuDepth > 2) return;
    const heading = toggle.parentElement;
    const item = heading.closest('li');
    const submenu = heading.nextElementSibling;
    if (!item || !submenu?.classList.contains('nav__submenu')) return;
    if (!submenu.id) submenu.id = `nav-submenu-${index + 1}`;
    toggle.setAttribute('aria-controls', submenu.id);
    const label = toggle.querySelector('.visually-hidden');
    const title = heading.querySelector('a').textContent.trim();
    const entries = submenu.querySelectorAll(':scope > li > .nav__sublink, :scope > li > .nav__subheading');
    const isNested = menuDepth === 2;
    const duration = prefersReducedMotion ? 0 : 0.28;
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(submenu, {
      autoAlpha: 0,
      height: window.matchMedia('(max-width: 1024px)').matches ? 0 : 'auto',
      x: isNested && !prefersReducedMotion ? -10 : 0,
      y: !isNested && !prefersReducedMotion ? -10 : 0
    });
    gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(entries, {
      autoAlpha: 0,
      y: prefersReducedMotion ? 0 : -6
    });
    const timeline = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.timeline({
      paused: true,
      defaults: {
        ease: 'power2.out'
      },
      onStart: () => gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(submenu, {
        pointerEvents: 'auto'
      }),
      onReverseComplete: () => gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(submenu, {
        pointerEvents: 'none'
      })
    }).to(submenu, {
      autoAlpha: 1,
      height: 'auto',
      x: 0,
      y: 0,
      duration
    }).to(entries, {
      autoAlpha: 1,
      y: 0,
      duration: prefersReducedMotion ? 0 : 0.2,
      stagger: prefersReducedMotion ? 0 : 0.035,
      onComplete: () => gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(entries, {
        clearProps: 'opacity'
      })
    }, '<0.06');
    const controller = {
      item,
      toggle,
      submenu,
      label,
      title,
      timeline
    };
    menuControllers.push(controller);
    toggle.addEventListener('click', () => {
      const isOpen = toggle.getAttribute('aria-expanded') === 'true';
      isOpen ? closeMenu(controller) : openMenu(controller);
    });
    item.addEventListener('pointerenter', event => {
      if (event.pointerType !== 'touch' && window.matchMedia('(min-width: 1025px)').matches) {
        openMenu(controller);
      }
    });
    item.addEventListener('pointerleave', event => {
      if (event.pointerType !== 'touch' && window.matchMedia('(min-width: 1025px)').matches) {
        closeMenu(controller);
      }
    });
    item.addEventListener('focusin', () => {
      if (window.matchMedia('(min-width: 1025px)').matches) {
        openMenu(controller);
      }
    });
    item.addEventListener('focusout', event => {
      if (window.matchMedia('(min-width: 1025px)').matches && !item.contains(event.relatedTarget)) {
        closeMenu(controller);
      }
    });
    item.addEventListener('keydown', event => {
      if (event.key === 'Escape' && window.matchMedia('(min-width: 1025px)').matches) {
        event.stopPropagation();
        closeMenu(controller);
        toggle.focus();
      }
    });
  });
  document.addEventListener('pointerdown', event => {
    if (!header.contains(event.target)) {
      menuControllers.forEach(controller => closeMenu(controller));
    }
  });
  media.add({
    motion: '(prefers-reduced-motion: no-preference)',
    reduceMotion: reduceMotionQuery
  }, context => {
    if (context.conditions.reduceMotion) {
      const showLogo = () => gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(logo, {
        autoAlpha: 1
      });
      if (pageIsReady()) showLogo();else document.addEventListener('platejka:ready', showLogo, {
        once: true
      });
      return () => document.removeEventListener('platejka:ready', showLogo);
    }
    const playLogoIntro = () => {
      const intro = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.timeline({
        defaults: {
          ease: 'power3.out'
        }
      });
      intro.set(logo, {
        autoAlpha: 1
      }).from(logoMarkParts, {
        autoAlpha: 0,
        scale: 0.8,
        transformOrigin: '50% 50%',
        duration: 0.25,
        stagger: 0.035
      }).from(logoLetters, {
        autoAlpha: 0,
        y: 8,
        duration: 0.22,
        stagger: 0.06
      }, 0.18);
      return intro;
    };
    let intro;
    const startLogoIntro = () => {
      intro?.kill();
      intro = playLogoIntro();
    };
    if (pageIsReady()) startLogoIntro();else document.addEventListener('platejka:ready', startLogoIntro, {
      once: true
    });
    const hover = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.timeline({
      paused: true,
      defaults: {
        duration: 0.24,
        ease: 'power2.out'
      }
    }).to(logoMarkParts, {
      scale: 1.06,
      stagger: {
        each: 0.035,
        from: 'center'
      },
      transformOrigin: '50% 50%'
    }).to(logoMarkParts, {
      scale: 1,
      stagger: 0.025
    }, '>');
    const playLogoHover = () => hover.restart();
    logo.addEventListener('pointerenter', playLogoHover);
    return () => {
      document.removeEventListener('platejka:ready', startLogoIntro);
      logo.removeEventListener('pointerenter', playLogoHover);
      intro?.kill();
      hover.kill();
    };
  });
}

/***/ }),

/***/ "./src/js/functions/burger.js":
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {

/* harmony import */ var gsap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("./node_modules/gsap/index.js");

const header = document.querySelector('.header');
const burger = header?.querySelector('[data-burger]');
const menu = header?.querySelector('[data-menu]');
const menuContent = menu?.querySelector('[data-menu-content]');
const headerBottom = header?.querySelector('.header__bottom');
const mobileQuery = window.matchMedia('(max-width: 1024px)');
const reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
if (header && burger && menu && menuContent) {
  let isOpen = false;
  let menuTween;
  const setMenuOffset = () => {
    const visibleBottom = headerBottom?.getBoundingClientRect().bottom ?? header.getBoundingClientRect().bottom;
    menu.style.setProperty('--mobile-menu-top', String(visibleBottom) + 'px');
  };
  const closeSubmenus = () => {
    menu.querySelectorAll('[data-nav-toggle][aria-expanded="true"]').forEach(toggle => {
      toggle.click();
    });
  };
  const setOpenState = function (nextOpen) {
    let returnFocus = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    if (!mobileQuery.matches && nextOpen) return;
    isOpen = nextOpen;
    menuTween?.kill();
    burger.classList.toggle('burger__active', isOpen);
    burger.setAttribute('aria-expanded', String(isOpen));
    burger.setAttribute('aria-label', isOpen ? 'Закрыть меню' : 'Открыть меню');
    document.documentElement.classList.toggle('menu-open', isOpen);
    if (isOpen) {
      if (headerBottom) {
        gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.killTweensOf(headerBottom);
        gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(headerBottom, {
          clearProps: 'opacity,visibility,transform'
        });
      }
      setMenuOffset();
      menu.classList.add('header__navigation--open');
      menuTween = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.fromTo(menu, {
        autoAlpha: 0,
        y: reduceMotionQuery.matches ? 0 : -12
      }, {
        autoAlpha: 1,
        y: 0,
        duration: reduceMotionQuery.matches ? 0 : 0.3,
        ease: 'power2.out',
        overwrite: 'auto'
      });
    } else {
      closeSubmenus();
      menuTween = gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.to(menu, {
        autoAlpha: 0,
        y: reduceMotionQuery.matches ? 0 : -12,
        duration: reduceMotionQuery.matches ? 0 : 0.22,
        ease: 'power2.in',
        overwrite: 'auto',
        onComplete: () => menu.classList.remove('header__navigation--open')
      });
      if (returnFocus) burger.focus();
    }
  };
  burger.addEventListener('click', () => setOpenState(!isOpen));
  menu.addEventListener('click', event => {
    if (!mobileQuery.matches) return;
    const link = event.target.closest('a');
    if (link) setOpenState(false);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && isOpen) {
      setOpenState(false, true);
    }
  });
  const resetForDesktop = event => {
    if (!event.matches) {
      isOpen = false;
      menuTween?.kill();
      closeSubmenus();
      burger.classList.remove('burger__active');
      burger.setAttribute('aria-expanded', 'false');
      burger.setAttribute('aria-label', 'Открыть меню');
      document.documentElement.classList.remove('menu-open');
      menu.classList.remove('header__navigation--open');
      gsap__WEBPACK_IMPORTED_MODULE_0__.gsap.set(menu, {
        clearProps: 'opacity,visibility,transform'
      });
    }
  };
  mobileQuery.addEventListener('change', resetForDesktop);
  window.addEventListener('resize', () => {
    if (isOpen) setMenuOffset();
  });
}

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/header.js"), __webpack_exec__("./src/js/functions/burger.js"));
/******/ }
]);
