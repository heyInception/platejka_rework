(() => {
  'use strict';
  const format = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 });
  const symbols = { USD: '$', EUR: '€', CNY: '¥' };
  const parse = (root, name) => {
    try { return JSON.parse(root.getAttribute(name) || '{}'); } catch (_) { return {}; }
  };
  const calculate = (amount, rate, action, commissions) => {
    const value = Number(amount);
    const quote = Number(rate);
    const commission = Number(commissions[action]);
    if (!Number.isFinite(value) || value <= 0 || !Number.isFinite(quote) || quote <= 0 || !Number.isFinite(commission) || commission < 0 || commission >= 100) return null;
    const base = Math.round((value * quote + Number.EPSILON) * 100) / 100;
    const fee = Math.round((base * commission / 100 + Number.EPSILON) * 100) / 100;
    return { base, fee, total: action === 'sell' ? base - fee : base + fee };
  };
  const money = value => `${format.format(value)} ₽`;
  const init = root => {
    if (root.dataset.exchangeReady) return;
    root.dataset.exchangeReady = 'true';
    const rates = parse(root, 'data-exchange-rates');
    const commissions = parse(root, 'data-exchange-commissions');
    const input = root.querySelector('[data-role="exchange-amount"]');
    const range = root.querySelector('[data-role="exchange-range"]');
    const total = root.querySelector('[data-role="exchange-total"]');
    const detail = root.querySelector('[data-role="exchange-detail"]');
    const prompt = root.querySelector('[data-role="exchange-prompt"]');
    const symbol = root.querySelector('[data-role="exchange-symbol"]');
    const value = root.querySelector('[data-role="exchange-value"]');
    let currency = 'USD';
    let action = 'buy';
    let result = null;
    const update = () => {
      result = calculate(input.value, rates[currency], action, commissions);
      const amount = Number(input.value);
      total.textContent = result ? money(result.total) : '—';
      detail.textContent = result ? `Курс ЦБ ${money(rates[currency])} · комиссия ${commissions[action]}% (${money(result.fee)})` : 'Введите сумму больше нуля';
      prompt.textContent = action === 'buy' ? 'Сколько вы хотите купить?' : 'Сколько вы хотите продать?';
      symbol.textContent = symbols[currency] || currency;
      value.textContent = `${format.format(amount || 0)} ${symbols[currency] || currency}`;
      range.value = String(Math.max(Number(range.min), Math.min(Number(range.max), amount || 1)));
      range.style.setProperty('--value', range.value);
      input.setAttribute('aria-invalid', result ? 'false' : 'true');
      return result;
    };
    const message = () => [`Обмен валюты: ${action === 'buy' ? 'купить' : 'продать'} ${currency}`, `Сумма: ${format.format(Number(input.value))} ${currency}`, `Курс ЦБ: ${money(rates[currency])}`, `Комиссия: ${commissions[action]}% (${money(result.fee)})`, `${action === 'buy' ? 'К оплате' : 'К получению'}: ${money(result.total)}`].join('\n');
    const activate = (button, selector) => root.querySelectorAll(selector).forEach(item => {
      const selected = item === button;
      item.classList.toggle('is-active', selected);
      item.classList.toggle('calculate__button_active', selected);
      item.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
    root.querySelectorAll('[data-currency]').forEach(button => button.addEventListener('click', () => {
      if (!rates[button.dataset.currency]) return;
      currency = button.dataset.currency;
      activate(button, '[data-currency]');
      update();
    }));
    root.querySelectorAll('[data-action]').forEach(button => button.addEventListener('click', () => {
      action = button.dataset.action;
      activate(button, '[data-action]');
      update();
    }));
    input.addEventListener('input', update);
    range.addEventListener('input', () => { input.value = range.value; update(); });
    root.querySelector('[data-exchange-request]')?.addEventListener('click', event => {
      if (!update()) { input.focus(); return; }
      const dialog = document.querySelector('[data-consultation-dialog]');
      if (!dialog || dialog.open) return;
      const summary = message();
      dialog.querySelector('[data-contact-modal-title]').textContent = 'Заявка на обмен валюты';
      dialog.querySelector('[data-contact-modal-text]').textContent = summary;
      const fields = {
        'platejka-request-source': 'cross-exchange',
        'platejka-calculator-summary': summary,
        'platejka-calculator-currency': currency,
        'platejka-calculator-amount': input.value,
        'platejka-calculator-rate': String(rates[currency]),
        'platejka-calculator-commission': String(commissions[action]),
        'platejka-calculator-total': String(result.total),
      };
      Object.entries(fields).forEach(([name, val]) => { const field = dialog.querySelector(`[name="${name}"]`); if (field) field.value = val; });
      dialog.dialogOpener = event.currentTarget;
      dialog.showModal();
    });
    root.querySelector('[data-exchange-telegram]')?.addEventListener('click', () => {
      if (!update()) { input.focus(); return; }
      window.open(`https://t.me/share/url?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(message())}`, '_blank', 'noopener,noreferrer');
    });
    update();
  };
  if (typeof module !== 'undefined' && module.exports) module.exports = { calculate };
  if (typeof document !== 'undefined') document.querySelectorAll('[data-cb-exchange-calculator]').forEach(init);
})();
