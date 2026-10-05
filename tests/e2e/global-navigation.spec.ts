import { expect, test } from "@playwright/test";

// Catches missing server components, inaccessible controls, focus leaks and duplicate initialization.
for (const width of [1440, 1024, 768, 390, 360]) {
  test(`global navigation at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors: string[] = [];
    page.on("pageerror", (error) => errors.push(error.message));
    page.on("console", (message) => {
      if (message.type() === "error") errors.push(message.text());
    });
    await page.goto(`/?platejka_task6=${Date.now()}`, {
      waitUntil: "domcontentloaded",
    });
    const header = page.locator("header[data-site-header]");
    await expect(header).toBeVisible();
    await expect(page.getByRole("banner")).toHaveCount(1);
    await expect(page.getByRole("contentinfo")).toBeVisible();
    await expect(header.locator(".header__logo-link")).toHaveAttribute(
      "href",
      new URL("/", page.url()).href,
    );
    await expect(header.locator(".header__left")).toHaveCount(1);
    await expect(header.locator(".header__right")).toHaveCount(1);
    await expect(header.locator(".header__wrapper")).toHaveCount(1);
    await expect(header.locator(".header__wrap")).toHaveCount(1);
    await expect(header.locator(".header__logo-mark")).toBeVisible();
    await expect(header.locator(".header__logo-word")).toBeVisible();
    await expect(header.locator(".header__logo-link")).toHaveCSS(
      "opacity",
      "1",
    );
    await expect(page.locator("footer .footer__brand")).toHaveCount(1);
    await expect(page.locator("footer .footer__legal-links")).toHaveCount(1);
    await expect(
      page.locator('header a[href="#"], footer a[href="#"]'),
    ).toHaveCount(0);
    const nav = header.locator("[data-menu]");
    const burger = header.locator("[data-burger]");
    if (width <= 1024) {
      await burger.click();
      await expect(burger).toHaveAttribute("aria-expanded", "true");
      await expect(nav).toBeVisible();
      await expect(nav.locator("a[href]").first()).toBeFocused();
      await page.keyboard.press("Shift+Tab");
      expect(
        await nav.evaluate((el) => el.contains(document.activeElement)),
      ).toBe(true);
      await page.keyboard.press("Tab");
      await expect(nav.locator("a[href]").first()).toBeFocused();
    }
    await expect(nav.locator("a[href]").first()).toBeVisible();
    const toggle = nav.locator("[data-nav-toggle]").first();
    await expect(toggle).toBeVisible();
    await toggle.focus();
    if (width <= 1024) await page.keyboard.press("Enter");
    await expect(toggle).toHaveAttribute("aria-expanded", "true");
    const submenu = page.locator(
      `#${await toggle.getAttribute("aria-controls")}`,
    );
    await expect(submenu).toBeVisible();
    await submenu.locator("a[href]").first().focus();
    await page.keyboard.press("Escape");
    await expect(toggle).toHaveAttribute("aria-expanded", "false");
    await expect(toggle).toBeFocused();
    if (width <= 1024) {
      await page.keyboard.press("Escape");
      await expect(burger).toHaveAttribute("aria-expanded", "false");
      await expect(burger).toBeFocused();
      await burger.click();
      await page.setViewportSize({ width: 1440, height: 900 });
      await expect(burger).toHaveAttribute("aria-expanded", "false");
      expect(await page.evaluate(() => document.body.style.overflow)).toBe("");
      await page.setViewportSize({ width, height: 900 });
    }
    await page.evaluate(() => {
      (window as Window & { platejkaCallCount?: number }).platejkaCallCount = 0;
      document.addEventListener("platejka:open-call", () => {
        const state = window as Window & { platejkaCallCount?: number };
        state.platejkaCallCount = (state.platejkaCallCount || 0) + 1;
      });
    });
    await header
      .locator(".header__button_call")
      .nth(width > 1229 ? 0 : 1)
      .click();
    expect(
      await page.evaluate(
        () =>
          (window as Window & { platejkaCallCount?: number }).platejkaCallCount,
      ),
    ).toBe(1);
    // Running the actual asset twice must not cause two toggles for one click.
    const scriptURL = await page
      .locator('script[src*="components/header.js"]')
      .getAttribute("src");
    expect(scriptURL).toBeTruthy();
    await page.addScriptTag({ url: scriptURL! });
    if (width <= 1024) {
      await burger.click();
      await expect(burger).toHaveAttribute("aria-expanded", "true");
      await page.keyboard.press("Escape");
      await expect(burger).toBeFocused();
    }
    const ids = await page
      .locator("[id]")
      .evaluateAll((els) => els.map((el) => el.id));
    expect(new Set(ids).size).toBe(ids.length);
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    expect(errors).toEqual([]);
  });
}

test("reduced motion keeps mobile navigation functional", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 800 });
  await page.emulateMedia({ reducedMotion: "reduce" });
  await page.goto(`/?platejka_task6=${Date.now()}`, {
    waitUntil: "domcontentloaded",
  });
  const burger = page.locator("[data-burger]");
  await burger.click();
  await expect(burger).toHaveAttribute("aria-expanded", "true");
  await page.keyboard.press("Escape");
  await expect(burger).toBeFocused();
  await expect(burger).toHaveAttribute("aria-expanded", "false");
});
