(self["webpackChunkgulp_builder"] = self["webpackChunkgulp_builder"] || []).push([["review-main","review"],{

/***/ "./src/js/components/review.js":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
/* unused harmony export initReview */
const {
  getReviewTabIndex
} = __webpack_require__("./src/js/components/review.cjs");
function initReview(root) {
  if (root.dataset.reviewReady === 'true') return;
  const tabs = [...root.querySelectorAll('[role="tab"]')];
  const panels = [...root.querySelectorAll('[role="tabpanel"]')];
  const selectTab = tab => {
    const panelId = tab?.getAttribute('aria-controls');
    if (!tabs.includes(tab) || !panels.some(panel => panel.id === panelId)) return;
    tabs.forEach(item => {
      const active = item === tab;
      item.setAttribute('aria-selected', String(active));
      item.tabIndex = active ? 0 : -1;
    });
    panels.forEach(panel => {
      const active = panel.id === panelId;
      panel.hidden = !active;
      if (active) {
        panel.querySelectorAll('[data-horizontal-slider]').forEach(slider => {
          slider.dispatchEvent(new CustomEvent('horizontal-slider:refresh'));
        });
      }
    });
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => selectTab(tab));
    tab.addEventListener('keydown', event => {
      const targetIndex = getReviewTabIndex(event.key, index, tabs.length);
      if (targetIndex === null) return;
      event.preventDefault();
      const target = tabs[targetIndex];
      selectTab(target);
      target.focus();
    });
  });
  root.dataset.reviewReady = 'true';
  const dialog = root.querySelector('[data-review-dialog]');
  if (!dialog) return;
  const video = dialog.querySelector('video');
  const loading = dialog.querySelector('[data-review-loading]');
  const empty = dialog.querySelector('[data-review-empty]');
  const error = dialog.querySelector('[data-review-error], .review-video-error');
  const title = dialog.querySelector('h2');
  const role = dialog.querySelector('[data-review-dialog-role], .review__dialog-role');
  const dialogCard = dialog.querySelector('[class$="__dialog-card"]');
  if (empty) empty.textContent = 'Видео скоро появится.';
  root.querySelectorAll('[data-review-video]').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('article');
      const source = button.dataset.reviewVideo;
      if (!card) return;
      dialog.dialogOpener = button;
      if (title) title.textContent = card.querySelector('h3')?.textContent || '';
      if (role) role.innerHTML = card.querySelector('p')?.innerHTML || '';
      if (dialogCard) {
        const image = card.style.getPropertyValue('--review-image') || getComputedStyle(card).backgroundImage;
        dialogCard.style.setProperty('--review-image', image);
      }
      if (loading) loading.hidden = !source;
      if (empty) empty.hidden = Boolean(source);
      if (error) error.hidden = true;
      dialog.showModal();
      document.documentElement.classList.add('review-dialog-open');
      if (source && video) {
        video.src = source;
        video.load();
        video.play().catch(() => {});
      }
    });
  });
  video?.addEventListener('playing', () => {
    if (loading) loading.hidden = true;
  });
  video?.addEventListener('error', () => {
    if (!dialog.open) return;
    if (loading) loading.hidden = true;
    if (error) error.hidden = false;
  });
  video?.addEventListener('click', () => {
    if (video.paused) video.play().catch(() => {});else video.pause();
  });
  dialog.querySelector('[data-review-close]')?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener('close', () => {
    document.documentElement.classList.remove('review-dialog-open');
    video?.pause();
    video?.removeAttribute('src');
    video?.load();
    if (loading) loading.hidden = true;
    dialog.dialogOpener?.focus?.();
  });
}
document.querySelectorAll('[data-review]').forEach(initReview);


/***/ }),

/***/ "./src/js/components/review.cjs":
/***/ ((module) => {

function getReviewTabIndex(key, currentIndex, tabCount) {
  if (!Number.isInteger(currentIndex) || tabCount < 1) return null;
  if (key === 'Home') return 0;
  if (key === 'End') return tabCount - 1;
  if (key === 'ArrowRight') return (currentIndex + 1) % tabCount;
  if (key === 'ArrowLeft') return (currentIndex - 1 + tabCount) % tabCount;
  return null;
}

module.exports = { getReviewTabIndex };


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./src/js/components/review.js"));
/******/ }
]);