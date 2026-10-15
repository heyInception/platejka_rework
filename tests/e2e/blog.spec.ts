import { expect, test } from "@playwright/test";

test.describe("Platejka blog", () => {
  test("landing uses three accessible category grids with uniform cards", async ({
    page,
  }) => {
    await page.goto("/blog");

    await expect(
      page.getByRole("heading", { level: 1, name: "Блог" }),
    ).toBeVisible();
    await expect(page.locator("[data-blog-section]")).toHaveCount(3);
    await expect(page.locator(".blog-card").first()).toBeVisible();
    await expect(page.locator(".blog-card__excerpt").first()).toBeVisible();
    await expect(
      page.locator(".blog-card__reading-time").first(),
    ).toContainText("мин");
    await expect(page.locator("[data-blog-search]")).toBeVisible();
  });

  test("single post exposes article navigation, sharing, CTA, and related posts", async ({
    page,
  }) => {
    await page.goto(
      "/otmyvanie-deneg-polnoe-rukovodstvo-po-problemam-riskam-i-metodam-protivodejstviya",
    );

    await expect(page.locator("[data-blog-toc]")).toBeVisible();
    await expect(page.locator('[data-blog-share="max"]')).toBeVisible();
    await expect(page.locator('[data-blog-share="telegram"]')).toBeVisible();
    await expect(page.locator('[data-blog-share="vk"]')).toBeVisible();
    await expect(page.locator('[data-graph-path="call"]').last()).toBeVisible();
    await expect(page.locator("[data-blog-related] .blog-card")).toHaveCount(3);
  });

  test("landing search is global, updates the URL, and load more appends cards", async ({
    page,
  }) => {
    await page.goto("/blog");
    const news = page.locator('[data-blog-section][data-term="news"]');
    const loadMore = news.locator("[data-blog-load-more]");
    if (await loadMore.isVisible()) {
      const before = await news.locator(".blog-card").count();
      await loadMore.click();
      await expect(news.locator(".blog-card")).toHaveCount(before + 3);
    }

    await page.locator("[data-blog-search] input").fill("отмывание денег");
    await expect(page).toHaveURL(/blog_search=/);
    await expect(page.locator("[data-blog-section]:visible")).toHaveCount(1);
    await expect(
      page.locator("[data-blog-section]:visible .blog-card__title").first(),
    ).toContainText(/Отмывание денег/i);
  });

  test("mobile layouts remain readable and the table of contents expands", async ({
    page,
  }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(
      "/otmyvanie-deneg-polnoe-rukovodstvo-po-problemam-riskam-i-metodam-protivodejstviya",
    );
    const toc = page.locator("[data-blog-toc]");
    await toc.locator("button").click();
    await expect(toc.locator("ol")).toBeVisible();
    const width = await page
      .locator("body")
      .evaluate((element) => ({
        scroll: element.scrollWidth,
        client: element.clientWidth,
      }));
    expect(width.scroll).toBeLessThanOrEqual(width.client);

    await page.goto("/blog");
    await expect(
      page.locator("[data-blog-section]").first().locator(".blog-card"),
    ).toHaveCount(2);
  });

  test("dynamic search state is not indexed and has one clean canonical", async ({
    page,
  }) => {
    await page.goto("/blog?blog_search=деньги");
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute(
      "content",
      /noindex/i,
    );
    await expect(page.locator('link[rel="canonical"]')).toHaveCount(1);
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute(
      "href",
      /\/blog\/?$/,
    );
  });
});
