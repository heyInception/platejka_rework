document.addEventListener('click', (event) => {
  const button = event.target.closest('.review-main [data-review-out-id], .review-main [data-review-out-source], .review-main [data-review-out-legacy]');
  if (!button) return;
  const root = button.closest('.review-main');
  const endpoint = root?.dataset.reviewOutEndpoint;
  if (!endpoint) return;
  const url = new URL(endpoint, window.location.href);
  url.searchParams.set('action', 'platejka_review_out');
  if (button.dataset.reviewOutId) {
    url.searchParams.set('kind', 'review');
    url.searchParams.set('id', button.dataset.reviewOutId);
  } else if (button.dataset.reviewOutLegacy) {
    url.searchParams.set('kind', 'legacy');
    url.searchParams.set('author', button.dataset.reviewOutLegacy);
  } else {
    url.searchParams.set('kind', 'platform');
    url.searchParams.set('source', button.dataset.reviewOutSource);
  }
  window.open(url.toString(), '_blank', 'noopener,noreferrer');
});
