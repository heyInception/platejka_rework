import { expect, test } from "@playwright/test";

test("live WordPress calculator and CF7 call path at 1440px", async ({ page, request }) => {
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto(`/?platejka_task7_live=${Date.now()}`, { waitUntil: "domcontentloaded" });

  const calculator = page.locator("[data-transfer-calculator]");
  const call = page.locator('[data-call-component][data-variant="default"]');
  await expect(calculator).toBeVisible();
  await expect(call).toBeVisible();
  await expect(page.locator('link[id="platejka-rework-section-calculator-css"]')).toHaveCount(1);
  await expect(page.locator('link[id="platejka-rework-section-call-css"]')).toHaveCount(1);
  await expect(call.locator('[data-call-form="transfer"] input[name="platejka_calculation_summary"]')).toHaveCount(1);
  await expect(call.locator('input[name="_wpcf7"][value="4966"]')).toHaveCount(1);

  const amount = calculator.locator('[data-role="amount"]');
  for (const currency of ["CNY", "USD", "EUR"]) {
    const response = await request.post("/wp-json/platejka/v1/calculation", { data: { currency, amount: 5000 } });
    expect(response.ok()).toBe(true);
    const expected = await response.json();
    await calculator.locator(`[data-currency="${currency}"]`).click();
    await amount.fill("5000");
    await expect(calculator.locator('[data-role="status"]')).toHaveAttribute("data-state", "success");
    await expect(calculator.locator('[data-role="official-rate"]')).toHaveAttribute("data-value", String(expected.rate));
    await expect(calculator.locator('[data-role="commission"]')).toHaveAttribute("data-value", String(expected.commission));
    await expect(calculator.locator('[data-role="grand-total"]')).toHaveAttribute("data-value", String(expected.total));
  }

  await page.evaluate(() => { document.body.style.overflow = "clip"; document.body.style.paddingRight = "7px"; });
  const opener = calculator.locator('[data-action="request"]');
  await opener.click();
  const dialog = call.locator("[data-call-dialog]");
  await expect(dialog).toBeVisible();
  const summary = dialog.locator('[name="platejka_calculation_summary"]');
  await expect(summary).toHaveValue(/^EUR: 5[\s\u00a0]?000; курс /);

  await page.evaluate(() => {
    document.dispatchEvent(new CustomEvent("platejka:open-call", { detail: { mode: "transfer", summary: "second-open", opener: document.querySelector("[data-dialog-close]") } }));
  });
  await expect(summary).toHaveValue("second-open");
  await dialog.locator("[data-dialog-close]").click();
  await expect(dialog).not.toBeVisible();
  await expect(opener).toBeFocused();
  expect(await page.evaluate(() => document.body.style.overflow)).toBe("clip");
  expect(await page.evaluate(() => document.body.style.paddingRight)).toBe("7px");

  await opener.click();
  await dialog.locator(".wpcf7").last().evaluate((element) => element.dispatchEvent(new CustomEvent("wpcf7mailsent", { bubbles: true, detail: { apiResponse: { message: "Заявка принята." } } })));
  await expect(dialog.locator("[data-call-success]")).toHaveText("Заявка принята.");
  await dialog.locator("[data-dialog-close]").click();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});
