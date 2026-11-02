"use strict";
(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["problems"],{

/***/ "./src/js/components/problems.js":
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {

document.querySelectorAll('.problems').forEach(root => {
  const dialog = root.querySelector('[data-problems-dialog]');
  const openButton = root.querySelector('[data-problems-dialog-open]');
  const closeButton = root.querySelector('[data-problems-dialog-close]');
  if (!dialog || !openButton) return;
  openButton.addEventListener('click', () => {
    if (dialog.open) return;
    dialog.showModal();
  });
  closeButton?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener('close', () => openButton.focus());
});

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/problems.js"));
/******/ }
]);
