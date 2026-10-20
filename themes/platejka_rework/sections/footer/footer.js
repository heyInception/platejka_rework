(() => {
  const box = document.getElementById('consentBox');
  const button = box?.querySelector('.consentButton');
  if (!box || !button) return;

  const cookieName = 'platejka_cookie_consent';
  if (document.cookie.split('; ').some(cookie => cookie.startsWith(`${cookieName}=`))) {
    box.classList.remove('open');
    box.hidden = true;
    return;
  }

  button.addEventListener('click', () => {
    const expires = new Date();
    expires.setMonth(expires.getMonth() + 3);
    document.cookie = `${cookieName}=accepted; expires=${expires.toUTCString()}; path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
    box.classList.remove('open');
    window.setTimeout(() => { box.hidden = true; }, 300);
  });
})();
