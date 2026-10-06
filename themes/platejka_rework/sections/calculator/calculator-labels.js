(() => {
  const forms = document.querySelectorAll(
    "[data-transfer-calculator], [data-hero-calculator]",
  );

  const labelText = (value, fallback) =>
    String(value || fallback).replace(/:\s*$/, "");

  const labelsFor = (form) => {
    try {
      const section = form.closest(
        "[data-transfer-calculator-section], [data-hero]",
      );
      const source = section?.id
        ? document.querySelector(
            `[data-calculator-labels-for="${CSS.escape(section.id)}"]`,
          )
        : null;
      return JSON.parse(
        form.dataset.calculatorLabels || source?.textContent || "{}",
      );
    } catch {
      return {};
    }
  };

  const messageFor = (form) => {
    const labels = labelsFor(form);
    const currency =
      form.querySelector('[data-currency][aria-pressed="true"]')?.dataset
        .currency || "";
    const amount =
      form.querySelector('[data-role="amount"]')?.value.trim() || "";
    const countryFrom =
      form
        .querySelector('[data-role="country-from"]')
        ?.selectedOptions[0]?.textContent.trim() || "";
    const countryTo =
      form
        .querySelector('[data-role="country-to"]')
        ?.selectedOptions[0]?.textContent.trim() || "";
    const commission =
      form.querySelector('[data-role="commission"]')?.textContent.trim() || "";
    const total =
      form.querySelector('[data-role="grand-total"]')?.textContent.trim() || "";

    return [
      `${labelText(labels.currency, "Валюта платежа")}: ${currency}`,
      countryFrom || countryTo
        ? `${labelText(labels.route, "Маршрут")}: ${countryFrom} → ${countryTo}`
        : "",
      `${labelText(labels.amount, "Сумма")}: ${amount} ${currency}`,
      `${labelText(labels.commission, "Комиссия агента")}: ${commission}`,
      `${labelText(labels.total, "Итого в рублях")}: ${total}`,
    ]
      .filter(Boolean)
      .join("\n");
  };

  forms.forEach((form) => {
    const section = form.closest(
      "[data-transfer-calculator-section], [data-hero]",
    );
    const dialog = section?.querySelector("[data-contact-dialog]");

    form.addEventListener("submit", () => {
      const message = messageFor(form);
      const summary = dialog?.querySelector("[data-dialog-summary]");
      const cf7Summary = dialog?.querySelector("[data-cf7-summary]");
      if (summary) summary.textContent = message;
      if (cf7Summary) cf7Summary.value = message;
    });

    form.querySelector('[data-action="telegram"]')?.addEventListener(
      "click",
      (event) => {
        const amount = form.querySelector('[data-role="amount"]');
        const normalized =
          amount?.value.replace(/[\s\u00a0\u202f]/g, "").replace(",", ".") ||
          "";
        if (!/^\d+(?:\.\d+)?$/.test(normalized) || Number(normalized) <= 0)
          return;

        event.preventDefault();
        event.stopImmediatePropagation();
        window.open(
          `https://t.me/platejka_com?text=${encodeURIComponent(messageFor(form))}`,
          "_blank",
          "noopener",
        );
      },
      true,
    );
  });
})();
