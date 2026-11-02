const assert = require("node:assert/strict");
const { chromium } = require("playwright");

async function swipe(page, selector) {
  const target = page.locator(selector);
  await target.scrollIntoViewIfNeeded();
  const box = await target.boundingBox();
  const x = box.x + Math.min(box.width - 24, 250);
  const y = box.y + Math.min(box.height / 2, 120);
  const cdp = await page.context().newCDPSession(page);
  await cdp.send("Input.dispatchTouchEvent", {
    type: "touchStart",
    touchPoints: [{ x, y }],
  });
  for (let step = 1; step <= 8; step++) {
    await cdp.send("Input.dispatchTouchEvent", {
      type: "touchMove",
      touchPoints: [{ x: x - step * 25, y }],
    });
  }
  await cdp.send("Input.dispatchTouchEvent", {
    type: "touchEnd",
    touchPoints: [],
  });
  await page.waitForTimeout(550);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    for (const path of ["/", "/o-kompanii", "/china"]) {
      const page = await browser.newPage({
        viewport: { width: 390, height: 844 },
        isMobile: true,
        hasTouch: true,
      });
      const errors = [];
      page.on("pageerror", (error) => errors.push(error.message));
      await page.goto(`http://localhost:8883${path}`, {
        waitUntil: "domcontentloaded",
      });
      const initialized = await page.locator(".swiper-initialized").count();
      assert(initialized > 0, `${path}: no initialized Swiper`);
      if (path === "/o-kompanii") {
        const metrics = await page
          .locator(".about-hero__metrics")
          .evaluate((element) => ({
            left: element.getBoundingClientRect().left,
            first: element
              .querySelector(".about-hero__metric")
              .getBoundingClientRect().left,
            scroll: element.scrollLeft,
          }));
        assert.equal(
          metrics.left,
          0,
          "about-hero metrics shifted outside viewport",
        );
        assert(metrics.first >= 0, "about-hero first metric is clipped");
        assert.equal(
          metrics.scroll,
          0,
          "about-hero retained native scroll offset",
        );
      }
      const geometry = await page.evaluate(() => {
        const heights = (selector) =>
          [...document.querySelectorAll(selector)].map((element) =>
            Math.round(element.getBoundingClientRect().height),
          );
        return {
          cases: heights(".case-card"),
          serves: heights(".serves__card"),
          reviews: heights(".review-main__text-card, .review__text-card"),
          location: heights(".location__thumb"),
          shipments: heights(".shipment-card"),
          linkOffset: (() => {
            const link = document.querySelector(".shipment-card__link");
            return link
              ? link.getBoundingClientRect().top -
                  link.closest(".shipment-card").getBoundingClientRect().top
              : null;
          })(),
        };
      });
      for (const key of [
        "cases",
        "serves",
        "reviews",
        "location",
        "shipments",
      ]) {
        if (geometry[key].length)
          assert.equal(
            new Set(geometry[key]).size,
            1,
            `${path}: unequal ${key} heights`,
          );
      }
      if (geometry.location.length) assert.equal(geometry.location[0], 192);
      if (geometry.linkOffset !== null)
        assert(geometry.linkOffset > 300, `${path}: shipment link moved up`);
      const selectors =
        path === "/o-kompanii"
          ? [
              ".about-hero__metrics",
              ".location__viewport",
              ".exhibitions__viewport",
              ".developing__viewport",
            ]
          : path === "/china"
            ? [
                ".shipments__list",
                ".guarantees__slider",
                ".documents__cards",
                ".review__viewport",
                ".problems__viewport",
                ".cases__viewport",
              ]
            : [
                ".shipments__list",
                ".guarantees__slider",
                ".documents__cards",
                ".review-main__viewport",
                ".with-us__viewport",
                ".problems__viewport",
                ".serves__viewport",
                ".cases__viewport",
              ];
      for (const slider of selectors) {
        const element = page.locator(slider).first();
        const canMove = await element.evaluate(
          (node) => node.swiper && node.swiper.maxTranslate() < -1,
        );
        if (!canMove) continue;
        const before = await element.evaluate((node) => node.swiper.realIndex);
        await swipe(page, slider);
        const after = await element.evaluate((node) => node.swiper.realIndex);
        assert.notEqual(
          after,
          before,
          `${path}: touch swipe did not move ${slider}`,
        );
      }
      assert.deepEqual(errors, [], `${path}: JavaScript errors`);
      console.log(`${path}: ${initialized} Swipers, touch checks passed`);
      await page.close();
    }
    const page = await browser.newPage({
      viewport: { width: 390, height: 844 },
      isMobile: true,
      hasTouch: true,
    });
    await page.goto("http://localhost:8883/", {
      waitUntil: "domcontentloaded",
    });
    for (const width of [768, 1024]) {
      await page.setViewportSize({ width, height: 900 });
      assert.equal(
        await page
          .locator("[data-shipments-slider].swiper-initialized")
          .count(),
        1,
      );
      assert.equal(
        await page.locator("[data-problems-slider].swiper-initialized").count(),
        1,
      );
    }
    await page.setViewportSize({ width: 1440, height: 900 });
    const withUs = await page
      .locator(".with-us__card")
      .first()
      .evaluate((element) => element.getBoundingClientRect().width);
    assert(
      Math.abs(withUs - 640) < 1,
      "with-us should show two 640px grid cards on desktop",
    );
    assert.equal(
      await page.locator(".with-us__viewport.swiper-initialized").count(),
      0,
    );
    const before = await page
      .locator(".cases__viewport")
      .evaluate((element) => element.swiper.activeIndex);
    await page.locator(".cases [data-horizontal-slider-next]").click();
    const after = await page
      .locator(".cases__viewport")
      .evaluate((element) => element.swiper.activeIndex);
    assert(after > before, "cases next arrow did not advance");
    assert.equal(
      await page.locator("[data-shipments-slider].swiper-initialized").count(),
      0,
      "shipments should be a desktop grid",
    );
    assert.equal(
      await page.locator("[data-problems-slider].swiper-initialized").count(),
      0,
      "problems should be a desktop grid",
    );
    console.log("arrows and desktop breakpoint: OK");
    await page.close();
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
