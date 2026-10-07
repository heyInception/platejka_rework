# Default Page ACF Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the internal-page `hero`, `protection`, and `review` layouts to the existing ACF builder and migrate only `/china/` (post ID 1873) to the approved 13-section composition without changing its public layout.

**Architecture:** Keep the existing builder and global-options model. Add a focused default-page seed and migration command, map the `review` template to canonical `review-main` content in the resolver, and convert the three static templates to the established resolved-data helpers while retaining their DOM contracts.

**Tech Stack:** WordPress 6.x, PHP 8.1+, ACF Pro 6.2.6.1 Local JSON, WP-CLI through Studio CLI, PHPUnit/integration eval files, Playwright.

**Spec:** `docs/superpowers/specs/2026-10-07-default-page-acf-migration-design.md`

## Global Constraints

- Migrate only post ID 1873; leave every other `page.php` page on fallback.
- Preserve the exact 13-section order from the spec and explicitly select `default` for shipments, guarantees, and documents.
- Add canonical data only for `hero` and `protection`; `review` must read `review-main` canonical data.
- Do not change existing CSS classes, `data-*` hooks, DOM ordering, calculator behavior, review behavior, or the SEO title/`the_content()` branch.
- Store editable images as attachment IDs and render responsive WordPress image markup; hero viewport images use eager/high priority.
- New builder layouts default disabled; migration explicitly enables all 13 rows for post 1873.
- Migration is additive, preview-first, and idempotent; do not apply until the dry-run report is reviewed.
- Use only `studio wp --path C:\Users\Inception\Studio\platejka ...` for WordPress commands.

## Review Focus

- A rerun after an editor changes seeded content must preserve that edit and perform no seed overwrite; pin this in Task 5.
- `review` local overrides must unwrap the `review-main` clone correctly while rendering `review.php`; pin this in Task 3.
- Empty or invalid media IDs must fail closed without broken `<img>` output; pin this in Task 4.
- A second hero-family row, including disabled rows submitted by ACF, must still be rejected consistently with the current validation contract; pin this in Task 3.
- A failed apply must not write the completion marker, and post 1873 must remain on fallback until all target writes verify; pin this in Task 5.

---

### Task 1: Lock the new schema and seed contracts

**Files:**
- Create: `plugins/platejka-core/data/default-page-defaults-v1.json`
- Create: `plugins/platejka-core/src/Acf/DefaultPageSeed.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Modify: `plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json`
- Modify: `plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json`
- Modify: `tests/integration/acf-section-builder-schema.php`
- Create: `tests/integration/default-page-seed.php`

**Interfaces:**
- Produces: `Platejka\Core\Acf\DefaultPageSeed::get(): array{schema_version:int,sections:array<string,mixed>,page_rows:array<int,mixed>}`.
- Produces: ACF defaults fields `field_platejka_defaults_hero_v1` and `field_platejka_defaults_protection_v1`.
- Produces: builder layouts `hero`, `protection`, and `review`; `review` clones `field_platejka_defaults_review_main_v1`.

- [ ] **Step 1: Extend the schema test and add the seed test so they fail.**

Assert that the existing 18 layouts remain in their current order with `hero`, `protection`, and `review` appended; each new layout contains `enabled` default `0`, `anchor`, and the correct clone target. Assert exactly two seed sections (`hero`, `protection`) and exactly 13 enabled `page_rows` in the approved order with the three explicit `default` variants.

- [ ] **Step 2: Run the focused tests and verify the missing fields/class fail.**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\acf-section-builder-schema.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\default-page-seed.php
```

Expected: FAIL because the three layouts and `DefaultPageSeed` do not exist.

- [ ] **Step 3: Add the minimal Local JSON fields and immutable default-page seed.**

Model hero copy, trust cards, links, and images from `sections/hero/hero.php`; model protection intro/link/cards from `sections/protection/protection.php`. Use globally unique `field_platejka_*_v1` keys, attachment-ID image fields, ACF link fields, groups, and repeaters already supported by ACF 6.2.6.1. Keep the original home seed unchanged.

- [ ] **Step 4: Run both focused tests and `git diff --check`.**

Expected: both tests PASS; JSON parses; every key is unique; all new layout toggles default disabled.

- [ ] **Step 5: Commit.**

```powershell
git add plugins/platejka-core/data/default-page-defaults-v1.json plugins/platejka-core/src/Acf/DefaultPageSeed.php plugins/platejka-core/platejka-core.php plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json tests/integration/acf-section-builder-schema.php tests/integration/default-page-seed.php
git commit -m "feat: add default page ACF layouts"
```

### Task 2: Resolve template slugs separately from canonical content

**Files:**
- Modify: `themes/platejka_rework/inc/section-builder.php`
- Modify: `themes/platejka_rework/inc/section-content.php`
- Modify: `tests/theme/section-builder.php`
- Modify: `tests/theme/section-content.php`

**Interfaces:**
- Consumes: builder layouts from Task 1.
- Produces: `platejka_section_content_slug(string $template_slug): string`, mapping only `review` to `review-main` and returning every other valid slug unchanged.
- Produces: normalized section configs with `content_slug`; templates continue to use `slug` for registry/template lookup.

- [ ] **Step 1: Add failing registry, hero-validation, and review-alias assertions.**

Assert `hero`, `protection`, and `review` rows resolve; `hero-main` plus `hero` returns `multiple_hero_sections`; an unknown slug remains omitted; and a `review` row resolves canonical `review-main` data plus a local cloned override without changing its template slug.

- [ ] **Step 2: Run the focused builder/content tests.**

Expected: FAIL on missing registry entries and `review-main` content aliasing.

- [ ] **Step 3: Implement the registry and content-slug boundary.**

Add the three slugs to `platejka_section_builder_registry()`. Store `content_slug` in normalized configs and use it for options-field lookup, seed lookup, and clone unwrapping in `platejka_resolve_section_data()`. Keep `slug` authoritative for section assets and PHP template selection.

- [ ] **Step 4: Run the focused tests.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-builder.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-content.php
```

Expected: PASS, including unknown-slug fail-closed and both direct/ACF-key hero validation cases.

- [ ] **Step 5: Commit.**

```powershell
git add themes/platejka_rework/inc/section-builder.php themes/platejka_rework/inc/section-content.php tests/theme/section-builder.php tests/theme/section-content.php
git commit -m "feat: resolve shared review section content"
```

### Task 3: Convert the three internal templates without changing layout

**Files:**
- Modify: `themes/platejka_rework/sections/hero/hero.php`
- Modify: `themes/platejka_rework/sections/protection/protection.php`
- Modify: `themes/platejka_rework/sections/review/review.php`
- Modify: `tests/theme/section-template-content.php`
- Modify: `tests/theme/china-page.php`

**Interfaces:**
- Consumes: `$section_data`, `$section_instance`, and `platejka_section_*` helpers from the renderer.
- Produces: escaped dynamic HTML with the same root classes and interaction hooks as the static templates.

- [ ] **Step 1: Add failing template contract assertions.**

Assert the three templates render fixture overrides, unique per-instance IDs/ARIA links, all existing root classes and `data-*` hooks, no demo `href="#"`/inline rates/fake dialog copy, and no `theme://` value. Assert missing media emits no broken image. Assert hero editorial images include dimensions plus eager/high priority and non-hero images use lazy loading.

- [ ] **Step 2: Run the template and China-page tests.**

Expected: FAIL because the templates still contain static copy and relative media.

- [ ] **Step 3: Convert `hero.php` to resolved data.**

Retain `.hero`, `.hero__layout`, `.hero__wrap_top`, trust-card classes, and all calculator hooks. Reuse the same calculator data/form integration already used by `hero-main.php`; create instance-safe input/dialog IDs. Escape scalar output and use attachment helpers for editable images.

- [ ] **Step 4: Convert `protection.php` and `review.php` to resolved data.**

Keep their wrapper hierarchy and slider/dialog hooks. Render review labels, `video_items`, and `items` from canonical `review-main`; intentionally omit ratings. Use `wp_kses_post()` only for approved rich-text fields and validate links/files through existing helpers.

- [ ] **Step 5: Run the focused template tests and PHP syntax checks.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-template-content.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\china-page.php
vendor\bin\parallel-lint themes\platejka_rework plugins\platejka-core tests
```

Expected: PASS with the static layout hooks preserved.

- [ ] **Step 6: Commit.**

```powershell
git add themes/platejka_rework/sections/hero/hero.php themes/platejka_rework/sections/protection/protection.php themes/platejka_rework/sections/review/review.php tests/theme/section-template-content.php tests/theme/china-page.php
git commit -m "feat: render internal sections from ACF"
```

### Task 4: Add a dedicated preview-first migration for post 1873

**Files:**
- Create: `plugins/platejka-core/src/Cli/DefaultPageMigrationCommand.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Create: `tests/integration/default-page-migration.php`
- Create: `tests/integration/default-page-migration-applied.php`

**Interfaces:**
- Consumes: `DefaultPageSeed::get()` and ACF field keys from Task 1.
- Produces: `wp platejka section-builder migrate-default-page` and the optional `--apply` flag.
- Produces: migration marker `platejka_default_page_builder_version=1` only after verified option and post writes.

- [ ] **Step 1: Write the failing read-only preview contract.**

Snapshot post 1873 meta, the two target option groups, seed-source media, and the marker. Assert deterministic reports target only ID 1873, contain exactly the approved 13 rows, plan only hero/protection defaults plus required media, and perform no writes.

- [ ] **Step 2: Run the preview test and verify the command is missing.**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\default-page-migration.php
```

Expected: FAIL because `DefaultPageMigrationCommand` is unavailable.

- [ ] **Step 3: Implement the dedicated command and registration.**

Use `preview(): array` and `apply(): array`. Reuse media by `_platejka_section_seed_source` or byte-identical attachment; import only missing seed media. Verify both option groups, the builder toggle, and all 13 page rows before writing the marker. If the marker already equals `1`, never overwrite editor-modified values or rows.

- [ ] **Step 4: Add the applied-state and failure-path contracts.**

Assert first apply writes the two groups and post 1873, a second apply reports `writes.performed=false`, editor-modified seeded content survives rerun, no media duplicates appear, all other pages remain unchanged, and a forced verification failure leaves the marker unset and the builder fallback usable.

- [ ] **Step 5: Run preview tests and static verification.**

Expected: preview PASS and unchanged database snapshot; do not run the real `--apply` yet.

- [ ] **Step 6: Commit.**

```powershell
git add plugins/platejka-core/src/Cli/DefaultPageMigrationCommand.php plugins/platejka-core/platejka-core.php tests/integration/default-page-migration.php tests/integration/default-page-migration-applied.php
git commit -m "feat: add China page builder migration"
```

### Task 5: Review and apply the local migration

**Files:**
- Modify: local WordPress database and Media Library only after report approval.

**Interfaces:**
- Consumes: the WP-CLI command from Task 4.
- Produces: migrated post 1873 and verified canonical hero/protection option values.

- [ ] **Step 1: Run and save the dry-run output for review.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-default-page
```

Expected: `mode=dry-run`, page ID `1873`, 13 rows in order, only hero/protection option targets, and `writes.performed=false`.

- [ ] **Step 2: Present the exact write/media summary and pause for approval.**

Do not run `--apply` until the user confirms the dry-run report.

- [ ] **Step 3: Apply once and immediately run applied-state verification.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-default-page --apply
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\default-page-migration-applied.php
```

Expected: marker `1`, exact 13 enabled rows, responsive attachment IDs, and PASS.

- [ ] **Step 4: Run the command a second time.**

Expected: no writes and no imported media, proving idempotency.

### Task 6: Full regression, browser parity, and documentation

**Files:**
- Create: `tests/e2e/china-section-builder.spec.ts`
- Modify: `README.md`
- Modify: `docs/content-editing-guide.md`

**Interfaces:**
- Consumes: migrated local page from Task 5.
- Produces: documented editorial workflow and end-to-end regression coverage.

- [ ] **Step 1: Add browser assertions for `/china/`.**

Cover exact section order, desktop/mobile overflow, one hero, default variants, hero calculator submission, review tab switching, slider/dialog behavior, responsive image attributes, unique IDs, absence of console/page errors, and representative pre-migration layout hooks.

- [ ] **Step 2: Run all focused WordPress and browser checks.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\acf-section-builder-schema.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-builder.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-content.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-template-content.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\default-page-migration-applied.php
npx playwright test tests/e2e/china-section-builder.spec.ts tests/e2e/seo-toggle.spec.ts
vendor\bin\phpunit --configuration phpunit.xml.dist
```

Expected: all PASS; visual inspection at desktop and mobile widths shows no regression.

- [ ] **Step 3: Document the new layouts, inheritance, rollback, and command.**

State that only ID 1873 is migrated; document that `review` reads `review-main`, all new rows default disabled, and disabling the builder restores the fallback.

- [ ] **Step 4: Run formatting/status checks and commit.**

```powershell
npm run lint
git diff --check
git status --short
git add tests/e2e/china-section-builder.spec.ts README.md docs/content-editing-guide.md
git commit -m "test: verify China ACF page migration"
```

- [ ] **Step 5: Request a final whole-branch code review, address findings, rerun affected checks, then push `main` only when the worktree is clean.**

