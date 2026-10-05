import { expect, test } from "@playwright/test";

test("local homepage loads theme CSS without filesystem paths", async ({
  page,
}) => {
  const response = await page.goto("/", {
    waitUntil: "domcontentloaded",
  });

  expect(response?.status()).toBe(200);

  const stylesheetUrls = await page
    .locator('link[rel="stylesheet"]')
    .evaluateAll((links) =>
      links.map((link) => (link as HTMLLinkElement).href),
    );

  expect(stylesheetUrls).not.toEqual(
    expect.arrayContaining([expect.stringMatching(/(?:^|[^a-z])[a-z]:[\\/]/i)]),
  );

  await expect(page.locator("body")).toHaveCSS("font-family", /Manrope/i);
});

test("theme changes are not hidden by a post-only 304 response", async ({
  page,
}) => {
  await page.setExtraHTTPHeaders({
    "If-Modified-Since": "Wed, 31 Dec 2099 23:59:59 GMT",
  });

  const response = await page.goto("/", { waitUntil: "domcontentloaded" });

  expect(response?.status()).toBe(200);
  await expect(page.locator(".hero__title")).toContainText(
    "Международные платежи",
  );
});
