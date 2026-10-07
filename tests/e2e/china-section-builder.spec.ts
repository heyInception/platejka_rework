import { expect, test } from "@playwright/test";

test("migrated China page keeps its section order, layout hooks, and interactions", async ({
  page,
}) => {
  const runtimeErrors: string[] = [];
  page.on("pageerror", (error) => runtimeErrors.push(error.message));
  page.on("console", (message) => {
    if (
      message.type() === "error" &&
      !message.text().startsWith("Failed to load resource:")
    ) {
      runtimeErrors.push(message.text());
    }
  });

  await page.setViewportSize({ width: 1440, height: 1000 });
  const response = await page.goto(`/china/?acf-builder-smoke=${Date.now()}`, {
    waitUntil: "domcontentloaded",
  });
  expect(response?.status()).toBe(200);

  const sections = page.locator("#primary > section");
  await expect(sections).toHaveCount(13);
  expect(
    await sections.evaluateAll((nodes) =>
      nodes.map((node) => node.classList.item(0)),
    ),
  ).toEqual([
    "hero",
    "about",
    "shipments",
    "guarantees",
    "documents",
    "protection",
    "review",
    "work",
    "problems",
    "calculator",
    "seo",
    "faq",
    "call",
  ]);

  await expect(page.locator("[data-hero]")).toHaveCount(1);
  await expect(page.locator(".shipments--default")).toHaveCount(1);
  await expect(page.locator(".guarantees--default")).toHaveCount(1);
  await expect(page.locator(".documents--default")).toHaveCount(1);

  for (const selector of [
    ".hero__blur[data-hero-blur]",
    ".hero-calculator[data-hero-calculator]",
    ".hero__trust-card_registry .hero__trust-decor_registry",
    ".protection__cards .protection__card",
    ".review__tabs[data-review-tabs]",
    ".review__intro > p",
    ".work__document .work__wrap",
    ".calculator__panel[data-transfer-calculator]",
    ".call__form .ui-input",
  ]) {
    await expect(page.locator(selector).first(), selector).toBeAttached();
  }

  const heroImage = page.locator("[data-hero-image]");
  await expect(heroImage).toHaveAttribute("srcset", /\d+w/);
  for (const image of [
    heroImage,
    page.locator(".protection__card img").first(),
  ]) {
    await expect(image).toHaveAttribute("width", /\d+/);
    await expect(image).toHaveAttribute("height", /\d+/);
    await expect(image).toHaveAttribute("sizes", /.+/);
    await expect(image).toHaveAttribute("decoding", "async");
  }

  const hero = page.locator("[data-hero]");
  await hero.locator("[data-hero-calculator] button[type=submit]").click();
  await expect(hero.locator("[data-contact-dialog]")).toBeVisible();
  await expect(hero.locator("[data-dialog-summary]")).toContainText("Сумма:");
  await hero.locator("[data-dialog-close]").click();

  const review = page.locator("[data-review]");
  const tabs = review.locator('[role="tab"]');
  await expect(tabs).toHaveCount(2);
  await tabs.nth(1).click();
  await expect(review.locator("[data-review-text-panel]")).toBeVisible();
  await tabs.nth(0).click();
  await review.locator("[data-review-video]").first().click();
  await expect(review.locator("[data-review-dialog]")).toBeVisible();
  await review.locator("[data-review-close]").click();

  await page.setViewportSize({ width: 390, height: 844 });
  await expect(page.locator("[data-hero]")).toBeVisible();
  await expect(page.locator(".call")).toBeAttached();
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth + 1,
    ),
  ).toBe(true);
  expect(runtimeErrors).toEqual([]);
});
