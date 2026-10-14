"use strict";
(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["compliance"],{

/***/ "./src/js/components/compliance.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* unused harmony export initCompliance */
function initCompliance(root) {
  if (root.dataset.complianceReady === 'true') return;
  const dialog = root.querySelector('[data-compliance-dialog]');
  const opener = root.querySelector('[data-compliance-dialog-open]');
  const closer = dialog?.querySelector('[data-compliance-dialog-close]');
  if (!dialog || !opener) return;
  root.dataset.complianceReady = 'true';
  opener.addEventListener('click', () => {
    dialog.dialogOpener = opener;
    dialog.showModal();
  });
  closer?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener('close', () => dialog.dialogOpener?.focus?.());
}
document.querySelectorAll('[data-compliance]').forEach(initCompliance);


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/compliance.js"));
/******/ }
]);