# Section Page Assembly Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Assemble the three page templates from existing PHP sections while loading section-specific CSS and JavaScript only for sections declared by the active template.

**Architecture:** Each page declares one ordered section configuration before `get_header()`. Helpers in `inc/assets.php` normalize that configuration, enqueue existing section assets, and render the same list with mode filtering and relative image URL rewriting.

**Tech Stack:** WordPress classic theme, PHP 8.1+, WordPress enqueue APIs, WordPress Studio CLI.

**Spec:** `docs/superpowers/specs/2026-10-05-section-page-assembly-design.md`

## Global Constraints

- Keep `common.css`, `common.js`, and `style.css` globally enabled and do not split them.
- Keep the existing section markup and copy unchanged.
- `page.php` is intentionally the common template for all ordinary pages.
- Header, preloader, and footer are global components.
- Missing optional section CSS/JS must not fail the request.
- Do not render a separate `the_content()` outside `sections/seo/seo.php`.
- Do not render the sidebar from the three section-based page templates.
- Keep automated verification to one focused smoke test plus PHP syntax checks.

## Review Focus

- A section slug containing traversal characters must be rejected rather than resolve outside `sections`.
- A section with no JavaScript file must render and enqueue only its CSS.
- An undeclared section must enqueue neither its CSS nor JavaScript.
- `main` mode must output only the main export branch; default mode must output only the default branch.
- Relative `img/...` references must become section URLs while absolute, root-relative, anchor, and data URLs remain unchanged.

---

### Task 1: Section registry, asset loading, and renderer

**Files:**
- Modify: `themes/platejka_rework/inc/assets.php`
- Create: `tests/theme/section-loader.php`

**Interfaces:**
- Consumes: WordPress `wp_enqueue_style()`, `wp_enqueue_script()`, theme path/URL helpers, `_S_VERSION`.
- Produces: `platejka_use_sections( array $sections ): array`, `platejka_render_sections( ?array $sections = null ): void`, and `platejka_render_section( string|array $section ): void`.
- Section entries are either a slug string or an array with `slug` and optional `mode`; aliases map `hero-main` to `sections/hero/hero-main.php` and `call-about` to `sections/call/call-about.php`, while their assets come from the containing directory.

- [ ] **Step 1: Write the focused failing Studio smoke test**

Create `tests/theme/section-loader.php` with assertions that normalization preserves order, aliases resolve to the correct directory/template, traversal slugs are rejected, missing JS is ignored, undeclared assets are absent, `shipments` selects exactly one mode branch, and relative image URLs are rewritten without changing absolute/root/data/anchor values.

- [ ] **Step 2: Run the smoke test and verify the helper contract is not implemented**

Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php`

Expected: the output reports failure because the new helper interface or its expected behavior does not exist yet. Inspect output text because this Studio version may return exit code 0 for a failing eval file.

- [ ] **Step 3: Implement section normalization and conditional enqueuing in `inc/assets.php`**

Leave the global `common.css`, `common.js`, and `style.css` registrations in `functions.php` unchanged. Replace the current hard-coded hero enqueue in `inc/assets.php` with the declared-section registry, stable `platejka-rework-section-<slug>` handles, file-existence checks, footer scripts, and `filemtime()` versions with `_S_VERSION` fallback. Always include header, preloader, and footer in the asset declaration without adding them to a page's body list.

- [ ] **Step 4: Implement buffered rendering in `inc/assets.php`**

Resolve templates strictly below `sections`, expose `$mode` to included section PHP, support only the supplied `@if (mode === 'main')` / `@if (mode !== 'main')` export branches, rewrite relative section asset references, and skip invalid/missing templates safely with a debug-only log message.

- [ ] **Step 5: Run the focused smoke test**

Run the Step 2 command.

Expected: a `WP_CLI::success` line with zero failures.

- [ ] **Step 6: Commit the loader**

```bash
git add themes/platejka_rework/inc/assets.php tests/theme/section-loader.php
git commit -m "feat: add conditional section loader"
```

### Task 2: Assemble the three page templates

**Files:**
- Modify: `themes/platejka_rework/page-home.php`
- Modify: `themes/platejka_rework/page-about.php`
- Modify: `themes/platejka_rework/page.php`

**Interfaces:**
- Consumes: `platejka_use_sections()` and `platejka_render_sections()` from Task 1.
- Produces: the exact Home, About, and default-page sequences listed in the spec.

- [ ] **Step 1: Extend the focused smoke test with static template assertions**

Assert that each page template declares every required slug exactly once and in the specified order, calls declaration before `get_header()`, renders through `platejka_render_sections()`, and contains neither `get_sidebar()` nor a direct content template call.

- [ ] **Step 2: Run the smoke test and verify the old templates fail the new assertions**

Run the Task 1 smoke command.

Expected: failures identify the missing declarations and renderer calls.

- [ ] **Step 3: Replace each template body with its approved section declaration and render call**

Use `mode => 'main'` only for Home `shipments`, `guarantees`, and `documents`. Use `hero-main` on Home, `hero` on the default page, and `call-about` only on About. Retain each WordPress template header and the normal post loop so current-page functions used by `seo.php` have the active post.

- [ ] **Step 4: Run the focused smoke test**

Run the Task 1 smoke command.

Expected: `WP_CLI::success` with zero failures.

- [ ] **Step 5: Commit the page assembly**

```bash
git add themes/platejka_rework/page-home.php themes/platejka_rework/page-about.php themes/platejka_rework/page.php tests/theme/section-loader.php
git commit -m "feat: assemble page templates from sections"
```

### Task 3: Render global preloader and supplied footer

**Files:**
- Modify: `themes/platejka_rework/header.php`
- Modify: `themes/platejka_rework/footer.php`
- Test: `tests/theme/section-loader.php`

**Interfaces:**
- Consumes: `platejka_render_section()` from Task 1.
- Produces: one global preloader after `wp_body_open()` and the supplied footer section before `wp_footer()`.

- [ ] **Step 1: Add focused assertions for global components**

Assert that `header.php` renders `preloader` exactly once after `wp_body_open()`, and `footer.php` renders the supplied `footer` section while retaining the closing site wrapper and `wp_footer()`.

- [ ] **Step 2: Run the smoke test and verify the global-component assertions fail**

Run the Task 1 smoke command.

Expected: failures identify the missing preloader and supplied footer calls.

- [ ] **Step 3: Wire the global components**

Call `platejka_render_section( 'preloader' )` immediately after `wp_body_open()`. Replace only the Underscores placeholder footer markup with `platejka_render_section( 'footer' )`; preserve document closing structure and hooks.

- [ ] **Step 4: Run minimal final verification**

Run:

```powershell
$files = @(
  'themes/platejka_rework/inc/assets.php',
  'themes/platejka_rework/page-home.php',
  'themes/platejka_rework/page-about.php',
  'themes/platejka_rework/page.php',
  'themes/platejka_rework/header.php',
  'themes/platejka_rework/footer.php',
  'tests/theme/section-loader.php'
)
$files | ForEach-Object { php -l $_ }
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php
```

Expected: every file reports `No syntax errors detected`; the smoke test reports zero failures. If the local site is running, open Home, About, and one ordinary page once and confirm section order plus absence of visible PHP/export directives.

- [ ] **Step 5: Commit global components**

```bash
git add themes/platejka_rework/header.php themes/platejka_rework/footer.php tests/theme/section-loader.php
git commit -m "feat: wire global theme sections"
```
