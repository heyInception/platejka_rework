import { expect, test } from "@playwright/test";

test("migrated About page preserves its nine-section layout and interactions", async ({
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
  const response = await page.goto(
    `/o-kompanii/?acf-about-builder-smoke=${Date.now()}`,
    { waitUntil: "domcontentloaded" },
  );
  expect(response?.status()).toBe(200);

  const sections = page.locator("#primary > section");
  await expect(sections).toHaveCount(9);
  expect(
    await sections.evaluateAll((nodes) =>
      nodes.map((node) => node.classList.item(0)),
    ),
  ).toEqual([
    "about-hero",
    "location",
    "review-main",
    "infrastructure",
    "financial",
    "employees",
    "exhibitions",
    "developing",
    "call",
  ]);

  await expect(page.locator("[data-about-hero]")).toHaveCount(1);
  await expect(page.locator(".about-hero__proof-link").first()).toHaveAttribute(
    "href",
    /^https:\/\//,
  );
  await expect(page.locator(".about-hero__rating-sites a")).toHaveCount(3);
  await expect(page.locator(".about-hero__media a")).toHaveCount(3);
  for (const selector of [
    ".about-hero__visual[data-about-hero-visual]",
    ".about-hero__proof[data-about-hero-proof]",
    ".location [data-location-map]",
    ".location[data-horizontal-slider]",
    ".review-main[data-review]",
    ".infrastructure",
    ".financial",
    ".employees[data-employees]",
    ".exhibitions[data-horizontal-slider]",
    ".developing[data-horizontal-slider]",
    ".call[data-call] [data-call-form] .wpcf7-form",
  ]) {
    await expect(page.locator(selector).first(), selector).toBeAttached();
  }

  const responsiveImages = [
    page.locator(".about-hero__proof img").first(),
    page.locator("[data-employees-image]"),
  ];
  for (const image of responsiveImages) {
    await expect(image).toHaveAttribute("width", /\d+/);
    await expect(image).toHaveAttribute("height", /\d+/);
    await expect(image).toHaveAttribute("sizes", /.+/);
    await expect(image).toHaveAttribute("decoding", "async");
  }
  await expect(responsiveImages[1]).toHaveAttribute("srcset", /\d+w/);

  const review = page.locator("[data-review]");
  const tabs = review.locator('[role="tab"]');
  await expect(tabs).toHaveCount(2);
  await tabs.nth(1).click();
  await expect(review.locator("[data-review-text-panel]")).toBeVisible();
  await tabs.nth(0).click();
  await review.locator("[data-review-video]").first().click();
  await expect(review.locator("[data-review-dialog]")).toBeVisible();
  await review.locator("[data-review-close]").click();

  for (const selector of [".location", ".exhibitions", ".developing"]) {
    await expect(
      page.locator(`${selector}[data-horizontal-slider]`),
    ).toHaveAttribute("data-horizontal-slider-ready", "true");
  }

  await page.setViewportSize({ width: 390, height: 844 });
  await expect(page.locator("[data-about-hero]")).toBeVisible();
  await expect(page.locator(".call[data-call]")).toBeAttached();
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth + 1,
    ),
  ).toBe(true);
  expect(runtimeErrors).toEqual([]);
});
