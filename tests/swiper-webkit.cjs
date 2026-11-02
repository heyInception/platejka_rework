const assert = require("node:assert/strict");
const { webkit } = require("playwright");

(async () => {
  const browser = await webkit.launch({ headless: true });
  try {
    for (const path of ["/", "/o-kompanii", "/china"]) {
      const page = await browser.newPage({
        viewport: { width: 390, height: 844 },
        isMobile: true,
        hasTouch: true,
      });
      await page.route("https://localhost:8883/**", async (route) => {
        const response = await route.fetch({
          url: route.request().url().replace("https://", "http://"),
        });
        await route.fulfill({ response });
      });
      const errors = [];
      page.on("pageerror", (error) => errors.push(error.message));
      await page.goto(`http://localhost:8883${path}`, {
        waitUntil: "domcontentloaded",
      });
      const count = await page.locator(".swiper-initialized").count();
      assert(count > 0, `${path}: no Swiper initialized`);
      const selector =
        path === "/o-kompanii" ? ".exhibitions__viewport" : ".cases__viewport";
      const target = page.locator(selector);
      await target.scrollIntoViewIfNeeded();
      const box = await target.boundingBox();
      const x = box.x + Math.min(250, box.width - 24);
      const y = box.y + Math.min(100, box.height / 2);
      const before = await target.evaluate(
        (element) => element.swiper.realIndex,
      );
      await page.mouse.move(x, y);
      await page.mouse.down();
      for (let step = 1; step <= 8; step++)
        await page.mouse.move(x - step * 25, y);
      await page.mouse.up();
      await page.waitForTimeout(500);
      const after = await target.evaluate(
        (element) => element.swiper.realIndex,
      );
      assert.notEqual(
        after,
        before,
        `${path}: WebKit drag did not move ${selector}`,
      );
      assert.deepEqual(errors, [], `${path}: JavaScript errors`);
      console.log(
        `${path}: WebKit ${count} Swipers, drag ${before} -> ${after}`,
      );
      await page.unrouteAll({ behavior: "ignoreErrors" });
      await page.close();
    }
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
