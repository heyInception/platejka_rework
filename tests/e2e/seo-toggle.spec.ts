import { expect, test } from "@playwright/test";

for (const target of [
  { name: "home", path: "/" },
  { name: "china", path: "/china/" },
]) {
  test(`${target.name} SEO content is collapsed and can be expanded`, async ({
    page,
  }) => {
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.goto(`${target.path}?seo-toggle-test=${Date.now()}`, {
      waitUntil: "domcontentloaded",
    });

    const section = page.locator("[data-seo]");
    const toggle = section.locator("[data-seo-toggle]");
    const collapsible = section.locator("[data-seo-collapsible]");

    await expect(
      section.locator("[data-seo-content] > h2").first(),
    ).toBeVisible();
    await expect(toggle).toBeVisible();
    await expect(toggle).toHaveText("Показать ещё");
    await expect(toggle).toHaveAttribute("aria-expanded", "false");
    await expect(collapsible).toBeHidden();

    await toggle.click();

    await expect(toggle).toHaveText("Скрыть");
    await expect(toggle).toHaveAttribute("aria-expanded", "true");
    await expect(collapsible).toBeVisible();

    await toggle.click();

    await expect(toggle).toHaveText("Показать ещё");
    await expect(toggle).toHaveAttribute("aria-expanded", "false");
    await expect(collapsible).toBeHidden();
  });
}
