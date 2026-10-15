(function () {
  'use strict';

  class WindowOpener {
    constructor(linkSelector = '.open-window') {
      this.links = document.querySelectorAll(linkSelector);
      this.init();
    }

    init() {
      this.links.forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          const url = this.getSafeUrl(link.dataset.url);
          if (url) this.openSmallWindow(url);
        });
      });
    }

    getSafeUrl(value) {
      if (!value) return '';

      try {
        const url = new URL(value, window.location.origin);
        if (!['http:', 'https:'].includes(url.protocol)) return '';
        return value.startsWith('/') ? `${url.pathname}${url.search}${url.hash}` : url.href;
      } catch (error) {
        return '';
      }
    }

    openSmallWindow(url) {
      const windowWidth = 800;
      const windowHeight = 600;
      const screenLeft = window.screenLeft ?? window.screenX ?? 0;
      const screenTop = window.screenTop ?? window.screenY ?? 0;
      const availableWidth = window.screen.availWidth || window.screen.width;
      const availableHeight = window.screen.availHeight || window.screen.height;
      const left = Math.max(0, Math.round(screenLeft + (availableWidth - windowWidth) / 2));
      const top = Math.max(0, Math.round(screenTop + (availableHeight - windowHeight) / 2));
      const features = [
        `width=${windowWidth}`,
        `height=${windowHeight}`,
        `left=${left}`,
        `top=${top}`,
        'menubar=no',
        'toolbar=no',
        'location=no',
        'status=no',
        'scrollbars=yes',
        'resizable=no',
      ].join(',');
      const openedWindow = window.open(url, '_blank', features);

      if (openedWindow) {
        openedWindow.opener = null;
        openedWindow.focus?.();
      } else {
        window.open(url, '_blank', 'noopener');
      }
    }
  }

  const init = () => new WindowOpener();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
