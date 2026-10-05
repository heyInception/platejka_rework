import { expect, test } from "@playwright/test";

const pages = [
  {
    name: "home",
    path: "/",
    main: ".platejka-home",
    calculator: true,
    sections: [
      "sections/hero/hero-main.html",
      "sections/about/about.html",
      "sections/shipments/shipments.html",
      "sections/guarantees/guarantees.html",
      "sections/documents/documents.html",
      "sections/compliance/compliance.html",
      "sections/review-main/review-main.html",
      "sections/work/work.html",
      "sections/calculator/calculator.html",
      "sections/with-us/with-us.html",
      "sections/destinations/destinations.html",
      "sections/seo/seo.html",
      "sections/problems/problems.html",
      "sections/serves/serves.html",
      "sections/cases/cases.html",
      "sections/table/table.html",
      "sections/faq/faq.html",
      "sections/call/call.html",
    ],
  },
  {
    name: "china",
    path: "/china/",
    main: ".platejka-china",
    calculator: true,
    sections: [
      "sections/hero/hero.html",
      "sections/about/about.html",
      "sections/shipments/shipments.html",
      "sections/guarantees/guarantees.html",
      "sections/documents/documents.html",
      "sections/protection/protection.html",
      "sections/review/review.html",
      "sections/work/work.html",
      "sections/problems/problems.html",
      "sections/calculator/calculator.html",
      "sections/seo/seo.html",
      "sections/faq/faq.html",
      "sections/call/call.html",
    ],
  },
  {
    name: "about",
    path: "/o-kompanii/",
    main: ".platejka-about",
    calculator: false,
    sections: [
      "sections/about-hero/about-hero.html",
      "sections/location/location.html",
      "sections/review-main/review-main.html",
      "sections/infrastructure/infrastructure.html",
      "sections/financial/financial.html",
      "sections/employees/employees.html",
      "sections/exhibitions/exhibitions.html",
      "sections/developing/developing.html",
      "sections/call/call-about.html",
    ],
  },
] as const;

test("three real pages keep the approved section order and shared components", async ({
  page,
}) => {
  test.setTimeout(120_000);
  await page.setViewportSize({ width: 1440, height: 1000 });

  for (const target of pages) {
    const runtimeErrors: string[] = [];
    const releaseResourceErrors: string[] = [];
    const onPageError = (error: Error) =>
      runtimeErrors.push(error.stack || error.message);
    const onConsole = (message: {
      type(): string;
      text(): string;
      location(): { url?: string; lineNumber?: number; columnNumber?: number };
    }) => {
      if (
        message.type() === "error" &&
        !message.text().startsWith("Failed to load resource:")
      ) {
        const location = message.location();
        runtimeErrors.push(
          `${message.text()} @ ${location.url || "inline"}:${location.lineNumber ?? 0}:${location.columnNumber ?? 0}`,
        );
      }
    };
    const onResponse = (response: { status(): number; url(): string }) => {
      const url = new URL(response.url());
      const isOwnedAsset =
        url.pathname.startsWith("/wp-content/themes/platejka_rework/") ||
        url.pathname.startsWith("/wp-content/plugins/platejka-core/");
      if (
        url.hostname === "localhost" &&
        (response.status() >= 500 || (isOwnedAsset && response.status() >= 400))
      ) {
        releaseResourceErrors.push(`${response.status()} ${response.url()}`);
      }
    };
    const onRequestFailed = (request: {
      failure(): { errorText: string } | null;
      url(): string;
    }) => {
      const url = new URL(request.url());
      const isOwnedAsset =
        url.pathname.startsWith("/wp-content/themes/platejka_rework/") ||
        url.pathname.startsWith("/wp-content/plugins/platejka-core/");
      if (url.hostname === "localhost" && isOwnedAsset) {
        releaseResourceErrors.push(
          `${request.failure()?.errorText || "request failed"} ${request.url()}`,
        );
      }
    };
    page.on("pageerror", onPageError);
    page.on("console", onConsole);
    page.on("response", onResponse);
    page.on("requestfailed", onRequestFailed);

    const response = await page.goto(
      `${target.path}?platejka_task12=${target.name}-${Date.now()}`,
      { waitUntil: "domcontentloaded" },
    );
    expect(response?.status(), `${target.name} HTTP status`).toBe(200);
    await expect(page.locator(target.main)).toHaveCount(1);
    await expect(page.locator("header[data-site-header]")).toHaveCount(1);
    await expect(page.locator("footer.footer")).toHaveCount(1);
    await expect(page.locator("[data-call-component]")).toHaveCount(1);
    await expect(page.locator("[data-transfer-calculator]")).toHaveCount(
      target.calculator ? 1 : 0,
    );

    const renderedSections = await page
      .locator(`${target.main} > [data-export-source]`)
      .evaluateAll((elements) =>
        elements.map((element) => element.getAttribute("data-export-source")),
      );
    expect(renderedSections, `${target.name} section order`).toEqual([
      ...target.sections,
    ]);
    await expect(
      page.locator('a[href="#"], a[href^="javascript:"]'),
    ).toHaveCount(0);
    expect(
      releaseResourceErrors,
      `${target.name} release resource errors`,
    ).toEqual([]);
    expect(runtimeErrors, `${target.name} browser errors`).toEqual([]);

    page.off("pageerror", onPageError);
    page.off("console", onConsole);
    page.off("response", onResponse);
    page.off("requestfailed", onRequestFailed);
  }
});
