/* Safari can cancel a touch pointer while a horizontal gesture is in progress.
 * Let the existing slider keyboard control complete a swipe that did not move. */
document.querySelectorAll('[data-horizontal-slider]').forEach(root => {
  const viewport = root.querySelector('[data-horizontal-slider-viewport]');
  if (!viewport) return;

  let currentIndex = 0;
  let gesture = null;
  root.addEventListener('horizontal-slider:change', event => {
    currentIndex = event.detail.index;
  });

  viewport.addEventListener('touchstart', event => {
    if (event.touches.length !== 1) {
      gesture = null;
      return;
    }
    const touch = event.touches[0];
    gesture = { x: touch.clientX, y: touch.clientY, index: currentIndex };
  }, { passive: true });

  viewport.addEventListener('touchend', event => {
    if (!gesture || event.changedTouches.length !== 1) return;
    const touch = event.changedTouches[0];
    const deltaX = touch.clientX - gesture.x;
    const deltaY = touch.clientY - gesture.y;
    const startIndex = gesture.index;
    gesture = null;
    if (Math.abs(deltaX) < 40 || Math.abs(deltaX) <= Math.abs(deltaY) * 1.2) return;
    if (currentIndex !== startIndex) return;
    viewport.dispatchEvent(new KeyboardEvent('keydown', {
      key: deltaX < 0 ? 'ArrowRight' : 'ArrowLeft',
      bubbles: true,
      cancelable: true
    }));
  }, { passive: true });

  viewport.addEventListener('touchcancel', () => { gesture = null; }, { passive: true });
});
