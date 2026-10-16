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

  test("single post hero is split and does not show view counts", async ({
    page,
  }) => {
    await page.goto(
      "/otmyvanie-deneg-polnoe-rukovodstvo-po-problemam-riskam-i-metodam-protivodejstviya",
    );
    const hero = page.locator(".blog-single__header");
    const image = hero.locator(".blog-single__hero img");
    await expect(hero.getByRole("heading", { level: 1 })).toBeVisible();
    await expect(image).toBeVisible();
    const titleBox = await hero
      .getByRole("heading", { level: 1 })
      .boundingBox();
    const imageBox = await image.boundingBox();
    expect(titleBox).not.toBeNull();
    expect(imageBox).not.toBeNull();
    expect(imageBox!.x).toBeGreaterThan(titleBox!.x + titleBox!.width);
    await expect(hero).not.toContainText("просмотров");
    await expect(page.locator(".post-views:visible")).toHaveCount(0);
  });

  test("sticky article aside stays below the fixed header", async ({
    page,
  }) => {
    await page.goto(
      "/otmyvanie-deneg-polnoe-rukovodstvo-po-problemam-riskam-i-metodam-protivodejstviya",
    );
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.evaluate(() => window.scrollTo(0, 900));
    await expect(page.locator(".header__bottom--fixed")).toBeVisible();
    const headerBox = await page
      .locator(".header__bottom--fixed")
      .boundingBox();
    const asideBox = await page.locator(".blog-single__aside").boundingBox();
    expect(headerBox).not.toBeNull();
    expect(asideBox).not.toBeNull();
    expect(asideBox!.y).toBeGreaterThanOrEqual(
      headerBox!.y + headerBox!.height,
    );
  });

  test("article contents use Latin anchors and track the current heading", async ({
    page,
  }) => {
    await page.goto(
      "/otmyvanie-deneg-polnoe-rukovodstvo-po-problemam-riskam-i-metodam-protivodejstviya",
    );
    const links = page.locator("[data-blog-toc] a");
    const firstHref = await links.first().getAttribute("href");
    expect(firstHref).toMatch(/^#[a-z0-9-]+$/);
    await expect(page.locator(firstHref!)).toHaveCount(1);
    await expect(
      page.locator('[data-blog-toc] a[aria-current="location"]'),
    ).toHaveCount(0);

    const secondHref = await links.nth(1).getAttribute("href");
    expect(secondHref).toMatch(/^#[a-z0-9-]+$/);
    await page.locator(secondHref!).evaluate((heading) => {
      window.scrollTo(
        0,
        heading.getBoundingClientRect().top + window.scrollY - 130,
      );
    });
    await expect(links.nth(1)).toHaveAttribute("aria-current", "location");
    await expect(links.first()).not.toHaveAttribute("aria-current", "location");
    await page.locator(firstHref!).evaluate((heading) => {
      window.scrollTo(
        0,
        heading.getBoundingClientRect().top + window.scrollY - 130,
      );
    });
    await expect(links.first()).toHaveAttribute("aria-current", "location");
    expect(
      await page.evaluate(
        () => getComputedStyle(document.documentElement).scrollBehavior,
      ),
    ).toBe("smooth");
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
    const width = await page.locator("body").evaluate((element) => ({
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
