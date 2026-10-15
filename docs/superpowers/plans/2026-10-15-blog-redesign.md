# Blog Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a responsive, accessible Platejka blog landing page, taxonomy archives, AJAX search/loading, and long-form single-post experience.

**Architecture:** A focused `inc/blog.php` owns data/query/render helpers and `inc/blog-ajax.php` owns the secure transport boundary. Thin templates reuse card and control template parts, while conditionally enqueued `sections/blog/blog.css` and `blog.js` own the presentation and interaction layer.

**Tech Stack:** WordPress PHP 8.1+, WP_Query/admin-ajax, semantic HTML, vanilla JavaScript, CSS, PHPUnit/static contract tests, Playwright.

**Spec:** `docs/superpowers/specs/2026-10-15-blog-redesign-design.md`

## Global Constraints

- Preserve all published content and existing post/taxonomy URLs.
- Preserve the user's uncommitted blog, breadcrumb, AJAX, and stylesheet work; refactor it in place.
- Do not change `.wpcf7-spinner` declarations.
- Reuse existing Platejka design tokens and the `call` section's MAX, Telegram, and VK visual assets.
- Do not add third-party frontend dependencies.
- Escape output, sanitize request data, and require a WordPress nonce for AJAX.

## Review Focus

- A missing featured image must render the branded placeholder without broken image markup (Task 2 test).
- A missing views plugin or category must not raise PHP warnings (Task 2 test).
- Tampered AJAX nonces and malformed paging/taxonomy inputs must be rejected or normalized safely (Task 3 test).
- Empty and final result sets must expose stable `empty`/`has_more=false` states without duplicate cards (Task 3 test).
- Long headings, tables, media, and breadcrumbs must remain usable at 375px width (Task 5 browser test).

---

### Task 1: Blog helper and asset contracts

**Files:**
- Create: `tests/unit/BlogThemeContractTest.php`
- Create: `themes/platejka_rework/inc/blog.php`
- Modify: `themes/platejka_rework/functions.php`
- Modify: `themes/platejka_rework/inc/assets.php`

**Interfaces:**
- Produces: `platejka_blog_is_surface(): bool`, `platejka_blog_posts_per_page(string $surface): int`, `platejka_blog_reading_time(int $post_id): int`, `platejka_blog_view_count(int $post_id): string`, `platejka_blog_primary_category(int $post_id): ?WP_Term`, and conditional asset data `PlatejkaBlog`.

- [ ] **Step 1: Write the failing helper/asset contract tests** asserting conditional blog registration, 3/2 landing counts, 9/6/4 archive counts, reading-time minimum of one, guarded views access, localized nonce/action/copy, and unchanged `.wpcf7-spinner` source.
- [ ] **Step 2: Run `vendor/bin/phpunit tests/unit/BlogThemeContractTest.php` and verify failures identify the missing blog module.**
- [ ] **Step 3: Implement the helper signatures and conditional enqueue/localization, requiring `inc/blog.php` before `inc/blog-ajax.php`.**
- [ ] **Step 4: Run the focused test and verify it passes.**
- [ ] **Step 5: Commit only Task 1 files with `feat: add blog module foundations`.**

### Task 2: Shared cards, landing page, and archives

**Files:**
- Modify: `tests/unit/BlogThemeContractTest.php`
- Modify: `themes/platejka_rework/index.php`
- Modify: `themes/platejka_rework/archive.php`
- Modify: `themes/platejka_rework/search.php`
- Modify: `themes/platejka_rework/template-parts/content-single-card.php`
- Create: `themes/platejka_rework/template-parts/blog-toolbar.php`
- Create: `themes/platejka_rework/template-parts/blog-empty.php`

**Interfaces:**
- Consumes: Task 1 query and metadata helpers.
- Produces: `.blog-card`, `[data-blog-grid]`, `[data-blog-search]`, and `[data-blog-load-more]` markup contracts shared by initial and dynamic results.

- [ ] **Step 1: Extend the failing contract test for semantic whole-card links, primary category, excerpt, date, reading time, guarded views, image fallback, the three fixed landing categories, archive/search grids, and no sidebar calls.**
- [ ] **Step 2: Run the focused test and verify it fails on the legacy templates.**
- [ ] **Step 3: Replace duplicated landing queries and archive boilerplate with escaped shared card/toolbar/empty templates, using `wp_reset_postdata()` after every secondary query.**
- [ ] **Step 4: Run the focused test and PHP syntax checks for every modified PHP file.**
- [ ] **Step 5: Commit only Task 2 files with `feat: rebuild blog listings`.**

### Task 3: Secure AJAX search and load-more flow

**Files:**
- Modify: `tests/unit/BlogThemeContractTest.php`
- Rewrite: `themes/platejka_rework/inc/blog-ajax.php`
- Create: `tests/e2e/blog.spec.ts`
- Create: `themes/platejka_rework/sections/blog/blog.js`

**Interfaces:**
- Consumes: `[data-blog-*]` markup from Task 2 and localized `PlatejkaBlog` data from Task 1.
- Produces: `platejka_blog_query_args(array $request): array` and JSON success payload `{ html: string, has_more: bool, page: int, found_posts: int }`.

- [ ] **Step 1: Add failing PHP contracts for nonce verification, `wp_send_json_success/error`, sanitized taxonomy/search/page inputs, offset-free paged queries, and JSON `has_more`; add failing Playwright cases for load, search URL state, empty, retry, and final-batch behavior.**
- [ ] **Step 2: Run the PHP contract and targeted Playwright test and verify the expected failures.**
- [ ] **Step 3: Implement the AJAX handler and debounced AbortController-based client with accessible busy/status/error states and duplicate-request prevention.**
- [ ] **Step 4: Run focused PHP and Playwright tests and verify they pass.**
- [ ] **Step 5: Commit only Task 3 files with `feat: add secure blog discovery`.**

### Task 4: Single article, table of contents, sharing, CTA, and related posts

**Files:**
- Modify: `tests/unit/BlogThemeContractTest.php`
- Modify: `themes/platejka_rework/inc/blog.php`
- Rewrite: `themes/platejka_rework/single.php`
- Modify: `themes/platejka_rework/template-parts/content.php`
- Modify: `themes/platejka_rework/sections/blog/blog.js`
- Modify: `tests/e2e/blog.spec.ts`

**Interfaces:**
- Produces: `platejka_blog_add_heading_ids(string $content): string`, `platejka_blog_table_of_contents(string $content): array`, `platejka_blog_related_query(int $post_id): WP_Query`, `[data-blog-toc]`, `[data-blog-share]`, and `[data-blog-contact]`.

- [ ] **Step 1: Add failing tests for stable unique heading IDs, escaped TOC labels, same-category related exclusions/limit, no sidebar/comments, Telegram/VK share URLs, MAX native-share handoff with copy fallback, existing `data-graph-path="call"` contact trigger compatibility, and mobile TOC semantics.**
- [ ] **Step 2: Run focused PHP and Playwright tests and verify failures occur because the article experience is missing.**
- [ ] **Step 3: Implement the article composition, server-generated TOC, share controls using copied `call` visual assets, contact CTA, and three related cards; enhance mobile TOC/share/copy behavior in JS.**
- [ ] **Step 4: Run the focused tests and PHP syntax checks and verify they pass.**
- [ ] **Step 5: Commit only Task 4 files with `feat: redesign blog articles`.**

### Task 5: Blog presentation, SEO directives, and responsive verification

**Files:**
- Modify: `tests/unit/BlogThemeContractTest.php`
- Modify: `themes/platejka_rework/inc/blog.php`
- Create: `themes/platejka_rework/sections/blog/blog.css`
- Modify: `themes/platejka_rework/style.css`
- Modify: `tests/e2e/blog.spec.ts`

**Interfaces:**
- Consumes: all markup/data contracts from Tasks 1–4.
- Produces: `platejka_blog_robots(array $robots): array` and `platejka_blog_canonical_url(): string` for `?blog_search=...` requests.

- [ ] **Step 1: Add failing tests for `noindex, follow`, clean canonical URLs, removal of migrated blog selectors from `style.css`, preservation of `.wpcf7-spinner`, focus visibility, reduced motion, 3/2 and 9/6/4 grids, sticky/collapsible TOC, overflow-safe article media/tables, and 375/768/1440px behavior.**
- [ ] **Step 2: Run the focused PHP and Playwright tests and verify the expected style/SEO/responsive failures.**
- [ ] **Step 3: Implement SEO hooks and the isolated CSS module; remove only confirmed blog rules from `style.css`.**
- [ ] **Step 4: Run `vendor/bin/phpunit`, `npm run test:unit`, relevant PHP theme scripts, `npx playwright test tests/e2e/blog.spec.ts`, PHP parallel lint, and stylesheet/JS lint commands that exist in the repository.**
- [ ] **Step 5: Inspect `/blog`, one category, one tag, an empty search, and a long article at 1440px, 768px, and 375px; fix regressions through failing tests first.**
- [ ] **Step 6: Commit only Task 5 files with `feat: finish responsive blog redesign`.**

### Task 6: Final regression and handoff

**Files:**
- Modify only if a failing regression test requires a fix.

**Interfaces:**
- Consumes: completed blog module.
- Produces: verified implementation and an evidence-based handoff.

- [ ] **Step 1: Run the full PHP, unit, and Playwright suites and record any unrelated pre-existing failures separately.**
- [ ] **Step 2: Confirm `git diff --check`, review the complete diff for accidental content/URL changes, and verify user-owned unrelated changes remain intact.**
- [ ] **Step 3: Capture final desktop and mobile browser screenshots and confirm there are no console errors.**
- [ ] **Step 4: Request a final code review, address actionable findings with test-first fixes, and rerun affected suites.**
- [ ] **Step 5: Present changed files, verification evidence, and any remaining environment limitations.**

