import { expect, test } from "@playwright/test";

test.beforeEach(async ({ page }) => {
  await page.goto(`/?section-interactions=${Date.now()}`, {
    waitUntil: "domcontentloaded",
  });
});

test("section dialogs keep their public interaction hooks", async ({
  page,
}) => {
  for (const interaction of [
    {
      open: "[data-about-dialog-open]",
      dialog: "[data-about-dialog]",
      close: "[data-about-dialog-close]",
    },
    {
      open: "[data-compliance-dialog-open]",
      dialog: "[data-compliance-dialog]",
      close: "[data-compliance-dialog-close]",
    },
    {
      open: "[data-problems-dialog-open]",
      dialog: "[data-problems-dialog]",
      close: "[data-problems-dialog-close]",
    },
  ]) {
    await page.locator(interaction.open).first().click();
    await expect(page.locator(interaction.dialog)).toBeVisible();
    await page.locator(interaction.close).click();
    await expect(page.locator(interaction.dialog)).toBeHidden();
  }

  const compliance = page.locator("[data-compliance]");
  const complianceOpeners = compliance.locator("[data-compliance-dialog-open]");
  await expect(complianceOpeners).toHaveCount(2);
  await complianceOpeners.nth(1).click();
  await expect(compliance.locator("[data-compliance-dialog]")).toBeVisible();
  await compliance.locator("[data-compliance-dialog-close]").click();
});

test("compliance dialog uses the shared modal presentation", async ({
  page,
}) => {
  const styles = await page.evaluate(() => {
    const read = (selector: string) => {
      const element = document.querySelector(selector);
      if (!(element instanceof HTMLDialogElement)) return null;
      const style = getComputedStyle(element);
      const close = getComputedStyle(
        element.querySelector('button[type="button"]') as HTMLElement,
      );
      return {
        borderRadius: style.borderRadius,
        maxWidth: style.width,
        boxShadow: style.boxShadow,
        closeBorderRadius: close.borderRadius,
        closeWidth: close.width,
        closeHeight: close.height,
      };
    };

    return {
      about: read("[data-about-dialog]"),
      compliance: read("[data-compliance-dialog]"),
    };
  });

  expect(styles.compliance).toEqual(styles.about);
});

test("FAQ opens the first question on initial load", async ({ page }) => {
  const firstItem = page.locator("[data-faq-item]").first();
  const secondItem = page.locator("[data-faq-item]").nth(1);

  await expect(firstItem).toHaveClass(/\bis-open\b/);
  await expect(firstItem.locator("[data-faq-trigger]")).toHaveAttribute(
    "aria-expanded",
    "true",
  );
  await expect(firstItem.locator("[data-faq-panel]")).not.toHaveAttribute(
    "hidden",
    "",
  );
  await expect(secondItem).not.toHaveClass(/\bis-open\b/);
});

test("registry links hide their destinations and open centered windows", async ({
  page,
}) => {
  const openedWindows: Array<{
    url: string;
    target: string;
    features: string;
  }> = [];
  await page.addInitScript(() => {
    const calls: Array<{ url: string; target: string; features: string }> = [];
    Object.defineProperty(window, "__openedRegistryWindows", { value: calls });
    window.open = ((url?: string | URL, target?: string, features?: string) => {
      calls.push({
        url: String(url ?? ""),
        target: String(target ?? ""),
        features: String(features ?? ""),
      });
      return window;
    }) as typeof window.open;
  });
  await page.reload({ waitUntil: "domcontentloaded" });

  const registryLink = page
    .locator(".hero__trust-card_registry .open-window")
    .first();
  await expect(registryLink).toHaveAttribute("href", "#");
  await expect(registryLink).toHaveAttribute(
    "data-url",
    "https://cbr.ru/finorg/foinfo/?ogrn=1257700335628",
  );
  await registryLink.click();

  openedWindows.push(
    ...(await page.evaluate(
      () =>
        (
          window as typeof window & {
            __openedRegistryWindows: Array<{
              url: string;
              target: string;
              features: string;
            }>;
          }
        ).__openedRegistryWindows,
    )),
  );
  expect(openedWindows).toHaveLength(1);
  expect(openedWindows[0].url).toBe(
    "https://cbr.ru/finorg/foinfo/?ogrn=1257700335628",
  );
  expect(openedWindows[0].target).toBe("_blank");
  expect(openedWindows[0].features).toContain("width=800");
  expect(openedWindows[0].features).toContain("height=600");

  const aboutLinks = page.locator(".about-card__link.open-window");
  await expect(aboutLinks).toHaveCount(2);
  await expect(aboutLinks.nth(0)).toHaveAttribute(
    "data-url",
    "/wp-content/uploads/2026/02/vypiska_iz_reestra_sro_ooo_platezhka.pdf",
  );
  await expect(aboutLinks.nth(1)).toHaveAttribute(
    "data-url",
    "https://cbr.ru/finorg/foinfo/?ogrn=1257700335628",
  );
});

test("review tabs and video dialog remain operable", async ({ page }) => {
  const section = page.locator("[data-review]");
  const tabs = section.locator('[role="tab"]');
  await expect(tabs).toHaveCount(2);
  await expect(section.locator(".review-main__video-card")).toHaveCount(5);
  await expect(section.locator(".review-main__rating")).toHaveCount(3);
  await expect(
    section.locator("[data-horizontal-slider-controls]").first(),
  ).toBeAttached();

  const preview = section.locator(".review-main__video-card-image").first();
  await expect(preview).toHaveAttribute("srcset", /\d+w/);
  await expect(preview).toHaveAttribute("sizes", /416px/);

  const firstVideoButton = section.locator("[data-review-video]").first();
  await expect(firstVideoButton).toHaveAttribute("data-review-video", /\.mp4$/);

  await tabs.nth(1).click();
  await expect(section.locator("[data-review-text-panel]")).toBeVisible();
  await expect(section.locator("[data-review-video-panel]")).toBeHidden();

  await tabs.nth(0).click();
  await firstVideoButton.click();
  await expect(section.locator("[data-review-dialog]")).toBeVisible();
  await expect(section.locator("[data-review-dialog] video")).toHaveAttribute(
    "src",
    /\.mp4$/,
  );
  await section.locator("[data-review-close]").click();
  await expect(section.locator("[data-review-dialog]")).toBeHidden();
});

test("calculator preserves searchable countries and editable message labels", async ({
  page,
}) => {
  const calculator = page.locator("[data-transfer-calculator]");
  await expect(
    calculator.locator('[data-role="country-to"] option'),
  ).toHaveCount(79);
  await expect(calculator.locator('[data-role="country-to"]')).toHaveAttribute(
    "data-calculator-select",
    "country",
  );
  await expect(calculator).toHaveAttribute(
    "data-calculator-labels",
    /commission/,
  );

  const hero = page.locator("[data-hero]");
  await hero.locator("[data-hero-calculator] button[type=submit]").click();
  const heroSummary = hero.locator("[data-dialog-summary]");
  await expect(heroSummary).toContainText("Сумма:");
  await expect(heroSummary).not.toContainText("Сумма::");
});

test("ACF rendering preserves the original home layout contract", async ({
  page,
}) => {
  for (const selector of [
    ".hero__blur[data-hero-blur]",
    ".hero-calculator[data-hero-calculator]",
    ".hero__trust-card_registry .hero__trust-decor_registry",
    ".about__title-accent",
    ".about-card__heading .about-card__logo",
    ".shipment-card--main .shipment-card__link",
    ".guarantee-card--main-featured",
    ".guarantee-card--main-secondary",
    ".document-card--main .document-card__download",
    ".review-main__lead-desktop",
    ".review-main__lead-tablet",
    ".work__document .work__wrap",
    ".work__link--desktop",
    ".work__link--mobile",
    ".destinations__globe",
    ".comparison__brand",
    ".comparison td[data-label] .comparison__line",
    ".call__title[data-call-title]",
    ".call__trust .hero__trust-card_registry",
    ".call__form .ui-input",
    ".call__form .call__submit",
    ".call__form .custom-checkbox__field",
  ]) {
    await expect(page.locator(selector).first(), selector).toBeAttached();
  }

  const callForm = page.locator(".call__form");
  await callForm.scrollIntoViewIfNeeded();
  await expect(callForm).toBeVisible();
  const callFormBox = await callForm.boundingBox();
  expect(callFormBox?.width ?? 0).toBeGreaterThan(300);
});
