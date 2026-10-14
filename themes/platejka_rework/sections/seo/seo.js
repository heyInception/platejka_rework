(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["seo"],{

/***/ "./src/js/components/seo.js":
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

const {
  initSeoSections
} = __webpack_require__("./src/js/components/seo.cjs");
initSeoSections(document);

/***/ }),

/***/ "./src/js/components/seo.cjs":
/***/ ((module) => {

"use strict";


const ANIMATION_DURATION = 450;

function initSeoSection(root) {
  const content = root.querySelector('[data-seo-content]');
  const toggle = root.querySelector('[data-seo-toggle]');
  if (!content || !toggle) return false;

  const children = [...content.children];
  const heading = children[0];
  const lead = children[1];
  if (heading?.tagName !== 'H2' || lead?.tagName !== 'P' || children.length < 3) return false;

  const collapsible = content.ownerDocument.createElement('div');
  collapsible.dataset.seoCollapsible = '';
  content.insertBefore(collapsible, children[2]);
  children.slice(2).forEach((child) => collapsible.appendChild(child));

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const showLabel = toggle.dataset.seoShowLabel || 'Показать ещё';
  const hideLabel = toggle.dataset.seoHideLabel || 'Скрыть';
  let expanded = false;

  Object.assign(collapsible.style, {
    height: '0px',
    overflow: 'hidden',
    transition: reducedMotion.matches
      ? 'none'
      : `height ${ANIMATION_DURATION}ms cubic-bezier(0.22, 1, 0.36, 1)`,
  });
  collapsible.hidden = true;

  const renderToggle = () => {
    toggle.setAttribute('aria-expanded', String(expanded));
    toggle.textContent = expanded ? hideLabel : showLabel;
  };

  const expand = () => {
    expanded = true;
    collapsible.hidden = false;
    renderToggle();

    if (reducedMotion.matches) {
      collapsible.style.height = 'auto';
      return;
    }

    requestAnimationFrame(() => {
      collapsible.style.height = `${collapsible.scrollHeight}px`;
    });
  };

  const collapse = () => {
    expanded = false;
    renderToggle();

    if (reducedMotion.matches) {
      collapsible.style.height = '0px';
      collapsible.hidden = true;
      return;
    }

    collapsible.style.height = `${collapsible.scrollHeight}px`;
    void collapsible.offsetHeight;
    collapsible.style.height = '0px';
  };

  collapsible.addEventListener('transitionend', (event) => {
    if (event.propertyName !== 'height') return;

    if (expanded) {
      collapsible.style.height = 'auto';
    } else {
      collapsible.hidden = true;
    }
  });

  toggle.addEventListener('click', () => {
    if (expanded) collapse();
    else expand();
  });

  renderToggle();
  toggle.hidden = false;
  return true;
}

function initSeoSections(doc = document) {
  return [...doc.querySelectorAll('[data-seo]')]
    .filter((root) => initSeoSection(root))
    .length;
}

module.exports = { initSeoSection, initSeoSections };


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/seo.js"));
/******/ }
]);