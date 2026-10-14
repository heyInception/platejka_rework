"use strict";
(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["about"],{

/***/ "./src/js/components/about.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* unused harmony exports initAbout, initAboutDialog */
function initAboutDialog(root) {
  const dialog = root.querySelector('[data-about-dialog]');
  const openButton = root.querySelector('[data-about-dialog-open]');
  const closeButton = dialog?.querySelector('[data-about-dialog-close]');
  if (!dialog || !openButton) return;
  openButton.addEventListener('click', () => {
    if (dialog.open) return;
    dialog.dialogOpener = openButton;
    dialog.showModal();
  });
  closeButton?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener('close', () => {
    dialog.dialogOpener?.focus?.();
    dialog.dialogOpener = null;
  });
}
function initAbout(root) {
  if (root.dataset.aboutReady === 'true') return;
  root.dataset.aboutReady = 'true';
  initAboutDialog(root);
}
document.querySelectorAll('[data-about]').forEach(initAbout);


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/about.js"));
/******/ }
]);