# About Page ACF Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a dedicated ACF section builder for page ID 22 and migrate its current nine-section About composition without changing the public layout.

**Architecture:** A new Local JSON group owns an About-only switch and flexible-content field. Eight layouts store content only in their page rows; `review-main` merges page overrides into the existing shared canonical group. A preview-first, idempotent WP-CLI command seeds current content and media, verifies normalized values, enables the builder, and writes its completion marker last.

**Tech Stack:** WordPress, PHP 8.1+, ACF Pro 6.2.6.1 Local JSON, WordPress Studio CLI, Playwright 1.63, existing classic theme section renderer.

**Spec:** `docs/superpowers/specs/2026-10-07-about-page-acf-migration-design.md`

## Global Constraints

- Target only page ID 22 (`/o-kompanii/`); do not change or delete `o_kompanii`.
- Do not add the eight new content groups to `platejka_section_defaults` or their layouts to the generic `platejka_sections` ACF field.
- The exact migrated order is `about-hero`, `location`, `review-main`, `infrastructure`, `financial`, `employees`, `exhibitions`, `developing`, `call-about`.
- Newly added rows default disabled; the nine migration rows are explicitly enabled.
- `about-hero` has `max: 1`; the other layouts can repeat and reorder.
- Preserve current HTML hierarchy, classes, data hooks, accessibility relationships, slider structure, map hook, and form behavior.
- Preserve semantic H1/H2 output; highlighted/decorative headings use structured values and `platejka_section_heading()`.
- Editable raster media use attachment IDs and responsive WordPress image markup; decorative SVGs remain theme assets.
- Migration apply requires a reviewed dry-run and explicit user confirmation.
- Keep verification at the requested medium scope: focused PHP contracts plus one desktop/mobile browser smoke.

## Review Focus

- A page other than ID 22 must not expose or resolve the About-only ACF group, and ID 22 must not show the generic builder beside it; Task 1 pins both location rules and Task 2 pins fallback behavior.
- An empty local `review-main` override must inherit shared values while a non-empty repeater replaces the shared repeater; Task 2 tests both cases.
- A partial migration or failed value verification must leave the builder disabled and omit the version marker; Task 5 tests marker ordering.
- Repeated layouts must generate unique IDs without breaking aria-controls or slider/dialog hooks; Tasks 3 and 4 test two instances.
- Existing editor values and imported media must survive repeated apply runs without overwrite or duplication; Tasks 5 and 6 test preservation and idempotency.

---

### Task 1: About-only ACF schema and immutable seed

**Files:**
- Create: `plugins/platejka-core/acf-json/group_platejka_about_page_builder_v1.json`
- Modify: `plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json`
- Create: `plugins/platejka-core/data/about-page-defaults-v1.json`
- Create: `plugins/platejka-core/src/Acf/AboutPageSeed.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Create: `tests/integration/acf-about-page-builder-schema.php`
- Create: `tests/integration/about-page-seed.php`

**Interfaces:**
- Consumes: shared group field `field_platejka_defaults_review_main_v1`; ACF Pro field types verified on the live site.
- Produces: `Platejka\Core\Acf\AboutPageSeed::get(): array{schema_version:int,sections:array<string,mixed>,page_rows:array<int,mixed>}` and stable field keys `field_platejka_about_page_builder_v1` / `field_platejka_about_sections_v1`.

- [ ] **Step 1: Write failing schema and seed contracts.**

Assert that the new group location is exactly post ID 22, the generic builder explicitly excludes ID 22, the two top-level field names are About-specific, the flexible field has exactly nine approved layouts, every `enabled` default is `0`, `about-hero.max` is `1`, and only the `review-main` row clones `field_platejka_defaults_review_main_v1`. Assert that the seed has schema version 1, exact row order, nine enabled migration rows, and no decorative SVG media sources.

- [ ] **Step 2: Run the focused contracts and verify RED.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\acf-about-page-builder-schema.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\about-page-seed.php
```

Expected: failures for the missing group, seed, and class.

- [ ] **Step 3: Add the Local JSON group.**

Define the exact page-local fields from the spec. Use `overrides` as the row content group name so the existing merge interface can consume each row. Use attachment-ID return formats, array-format links, a CF7 post-object restricted to `wpcf7_contact_form`, repeaters for collections, and stable `field_platejka_about_*_v1` keys. Add an AND location rule excluding post ID 22 from the generic builder so editors see only the dedicated builder there.

- [ ] **Step 4: Add and load `AboutPageSeed`.**

Read `data/about-page-defaults-v1.json`, reject malformed versions or shapes, and return an empty version-1 structure on unreadable/invalid input. The seed represents the current public content; use `theme://` only for editable raster assets.

- [ ] **Step 5: Run the contracts and verify GREEN.**

Expected: both commands report `0 failures` and explicit success lines.

- [ ] **Step 6: Commit.**

```powershell
git add plugins/platejka-core/acf-json/group_platejka_about_page_builder_v1.json plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json plugins/platejka-core/data/about-page-defaults-v1.json plugins/platejka-core/src/Acf/AboutPageSeed.php plugins/platejka-core/platejka-core.php tests/integration/acf-about-page-builder-schema.php tests/integration/about-page-seed.php
git commit -m "feat: add About page ACF schema"
```

### Task 2: About-only row resolver and review inheritance

**Files:**
- Modify: `themes/platejka_rework/inc/section-builder.php`
- Modify: `themes/platejka_rework/inc/section-content.php`
- Modify: `themes/platejka_rework/page-about.php`
- Create: `tests/theme/about-section-builder.php`
- Modify: `tests/theme/section-content.php`

**Interfaces:**
- Consumes: `field_platejka_about_page_builder_v1`, `field_platejka_about_sections_v1`, and row key `overrides` from Task 1.
- Produces: `platejka_get_about_page_sections(int $post_id, array $fallback): array`; validation through `acf/validate_value/key=field_platejka_about_sections_v1`; normalized configs with `slug`, `content_slug`, `mode`, `row`, `instance`, and `anchor`.

- [ ] **Step 1: Write failing resolver contracts.**

Cover disabled-builder fallback, exact enabled-row order, default-disabled omission, repeatable section instances, one-hero validation, duplicate/invalid anchor rejection, and no effect on a non-22 post. For `review-main`, assert empty local values inherit shared scalars/repeaters and non-empty local collections replace shared collections.

- [ ] **Step 2: Run the contracts and verify RED.**

Expected: missing About resolver and unresolved new slugs.

- [ ] **Step 3: Register the nine renderer slugs and implement the About resolver.**

Keep the renderer allowlist separate from ACF editor availability. Read only the About-specific switch and rows for post 22; use the fallback for every other post or when disabled. Preserve unique instance names based on post ID and row index. Attach the existing row validator to the About flexible field so duplicate heroes and invalid/duplicate anchors are rejected before save.

- [ ] **Step 4: Route `page-about.php` through `platejka_get_about_page_sections()`.**

Do not alter the fallback array or the generic resolver used by other templates.

- [ ] **Step 5: Run the focused contracts and verify GREEN.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\about-section-builder.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-content.php
```

- [ ] **Step 6: Commit.**

```powershell
git add themes/platejka_rework/inc/section-builder.php themes/platejka_rework/inc/section-content.php themes/platejka_rework/page-about.php tests/theme/about-section-builder.php tests/theme/section-content.php
git commit -m "feat: resolve About page ACF sections"
```

### Task 3: Dynamic About hero, location, and infrastructure templates

**Files:**
- Modify: `themes/platejka_rework/sections/about-hero/about-hero.php`
- Modify: `themes/platejka_rework/sections/location/location.php`
- Modify: `themes/platejka_rework/sections/infrastructure/infrastructure.php`
- Create: `tests/theme/about-section-templates-primary.php`

**Interfaces:**
- Consumes: resolved `$section_data`, `$section_instance`, and `$section_anchor` from Task 2.
- Produces: the existing DOM contracts rendered from page-local values with instance-derived IDs and responsive attachment images.

- [ ] **Step 1: Write failing template contracts.**

Render fixtures for each template and assert current root/nested classes and data hooks, escaped content, H1/H2 semantics, two-instance unique IDs, map hook preservation, slider markup, and image `width`, `height`, `sizes`, and `srcset` where WordPress has alternate sizes.

- [ ] **Step 2: Run the contract and verify RED.**

Expected: fixture values do not appear because the templates are still static.

- [ ] **Step 3: Convert `about-hero.php` without restructuring its DOM.**

Replace content nodes and editable raster images only. Keep the signature SVG and animation hooks in place. Generate proof and metric items from repeaters and use instance-derived IDs/clip IDs.

- [ ] **Step 4: Convert `location.php` without restructuring its DOM.**

Preserve `data-location-map`, the horizontal-slider hierarchy, phone/mail protocols, and address semantics. Generate gallery images through `platejka_section_image()`.

- [ ] **Step 5: Convert `infrastructure.php` without restructuring its DOM.**

Render specializations, facts, and entities from repeaters. Preserve active/thailand modifier behavior as explicit sanitized row choices, not editor-supplied class strings.

- [ ] **Step 6: Run the contract and verify GREEN.**

- [ ] **Step 7: Commit.**

```powershell
git add themes/platejka_rework/sections/about-hero/about-hero.php themes/platejka_rework/sections/location/location.php themes/platejka_rework/sections/infrastructure/infrastructure.php tests/theme/about-section-templates-primary.php
git commit -m "feat: render primary About sections from ACF"
```

### Task 4: Dynamic financial, employees, exhibitions, developing, and call templates

**Files:**
- Modify: `themes/platejka_rework/sections/financial/financial.php`
- Modify: `themes/platejka_rework/sections/employees/employees.php`
- Modify: `themes/platejka_rework/sections/exhibitions/exhibitions.php`
- Modify: `themes/platejka_rework/sections/developing/developing.php`
- Modify: `themes/platejka_rework/sections/call/call-about.php`
- Create: `tests/theme/about-section-templates-secondary.php`

**Interfaces:**
- Consumes: Task 2 template variables and Task 1 field shapes; existing `platejka_section_heading()`, `platejka_section_image()`, and `platejka_cf7_form()`.
- Produces: five data-driven templates preserving current CSS/JS/accessibility contracts.

- [ ] **Step 1: Write failing template contracts.**

Assert structured decorative headings retain semantic H2s, unsafe/fake links are suppressed, rich text is filtered, CF7 is rendered only for a valid published form, repeaters preserve order, timeline year controls derive from timeline rows, two instances have unique IDs, and representative classes/hooks from every section remain attached.

- [ ] **Step 2: Run the contract and verify RED.**

- [ ] **Step 3: Convert `financial.php` and `employees.php`.**

Use structured headings, explicit repeater shapes, escaped scalar output, `wp_kses_post()` for approved rich text, safe links, responsive images, and unchanged decorative SVG markup.

- [ ] **Step 4: Convert `exhibitions.php` and `developing.php`.**

Keep horizontal-slider hooks and controls. Generate developing cards and year-navigation buttons from the same repeater index.

- [ ] **Step 5: Convert `call-about.php`.**

Preserve the call root/form hooks, render the selected CF7 form through `platejka_cf7_form()`, keep trust and extended About cards local, and reject empty/unsafe social links rather than emitting `href="#"`.

- [ ] **Step 6: Run the contract and verify GREEN.**

- [ ] **Step 7: Commit.**

```powershell
git add themes/platejka_rework/sections/financial/financial.php themes/platejka_rework/sections/employees/employees.php themes/platejka_rework/sections/exhibitions/exhibitions.php themes/platejka_rework/sections/developing/developing.php themes/platejka_rework/sections/call/call-about.php tests/theme/about-section-templates-secondary.php
git commit -m "feat: render remaining About sections from ACF"
```

### Task 5: Deterministic About-page migration command

**Files:**
- Create: `plugins/platejka-core/src/Cli/AboutPageMigrationCommand.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Create: `tests/integration/about-page-migration.php`
- Create: `tests/integration/about-page-migration-applied.php`

**Interfaces:**
- Consumes: `AboutPageSeed::get()`, stable Task 1 field keys, post ID 22, and the existing media source-marker/hash convention.
- Produces: WP-CLI command `platejka section-builder migrate-about-page [--apply]`; `preview(): array`; `apply(): array`.

- [ ] **Step 1: Write the failing dry-run contract.**

Assert exact post ID/order, builder and row write plan, current-to-target reporting, deterministic repeated previews, no database/media mutation, unique media sources, and rejection of an invalid/incomplete seed. If any meaningful About rows already exist, assert that apply refuses to replace the collection and reports a manual-resolution conflict; an empty ACF-shaped collection is still eligible for seeding.

- [ ] **Step 2: Write the failing verification-order contract.**

Use a controlled verification failure seam to assert that the builder remains disabled and `platejka_about_page_builder_version` remains absent when row/content verification fails.

- [ ] **Step 3: Run both contracts and verify RED.**

- [ ] **Step 4: Implement the command and register it only under WP-CLI.**

Reuse the established safe path validation, attachment source marker, content hash fallback, recursive media replacement, meaningful-value detection, ACF normalization/empty-structure compaction, verify-before-enable ordering, and marker-last behavior. Refuse a pre-existing meaningful flexible-row collection rather than attempting an ambiguous merge. Do not write to `o_kompanii`, generic builder fields, or shared defaults.

- [ ] **Step 5: Run dry-run and migration contracts and verify GREEN.**

Expected: preview and verification-order contracts report `0 failures` and preview explicitly reports `writes.performed: false`. The database-dependent post-apply contract remains pending until Task 6.

- [ ] **Step 6: Commit.**

```powershell
git add plugins/platejka-core/src/Cli/AboutPageMigrationCommand.php plugins/platejka-core/platejka-core.php tests/integration/about-page-migration.php tests/integration/about-page-migration-applied.php
git commit -m "feat: add About page builder migration"
```

### Task 6: Review and apply the local database migration

**Files:**
- No product-code changes expected.
- Update ignored execution ledger under `.superpowers/sdd/2026-10-07-about-page-acf-migration/`.

**Interfaces:**
- Consumes: Task 5 CLI command and explicit user confirmation after reviewing the actual dry-run.
- Produces: verified local ACF values for ID 22 and version marker `platejka_about_page_builder_version = 1`.

- [ ] **Step 1: Run the real dry-run.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-about-page
```

Verify the exact nine rows, target ID 22, no writes, reusable/importable media counts, no shared/default or legacy targets, and no unexpected populated-field overwrite.

- [ ] **Step 2: Present the dry-run diff and pause for explicit confirmation.**

Do not infer approval from plan approval.

- [ ] **Step 3: Apply after confirmation.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-about-page --apply
```

- [ ] **Step 4: Run the post-apply contract and a second apply.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\about-page-migration-applied.php
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-about-page --apply
```

Expected: exact values/order and marker; second apply reports no writes/imports.

### Task 7: Browser parity and documentation

**Files:**
- Create: `tests/e2e/about-section-builder.spec.ts`
- Modify: `README.md`
- Modify: `docs/content-editing-guide.md`

**Interfaces:**
- Consumes: migrated local page from Task 6 and stable markup hooks from Tasks 3–4.
- Produces: one medium browser regression and documented editorial/rollback workflow.

- [ ] **Step 1: Add one desktop/mobile Playwright smoke.**

Assert HTTP 200, exact nine-section order, one `about-hero`, representative legacy classes/hooks, review tab/dialog behavior, office/exhibitions/developing sliders, `data-location-map`, valid call form hooks, responsive image attributes, no horizontal overflow at 390px, and no application runtime errors.

- [ ] **Step 2: Run the focused final profile.**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\acf-about-page-builder-schema.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\about-section-builder.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\about-section-templates-primary.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\about-section-templates-secondary.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\about-page-migration-applied.php
npx playwright test tests/e2e/about-section-builder.spec.ts
npm run lint
git diff --check
```

Expected: every PHP contract reports `0 failures`, Playwright reports `1 passed`, lint succeeds, and diff check is clean.

- [ ] **Step 3: Document editing, inheritance, migration, and rollback.**

State that the group exists only on ID 22, eight layouts are page-local, `review-main` inherits shared data, new rows default disabled, migration is preview-first/idempotent, legacy fields remain intact, and disabling the About builder restores the fallback.

- [ ] **Step 4: Commit.**

```powershell
git add tests/e2e/about-section-builder.spec.ts README.md docs/content-editing-guide.md
git commit -m "test: verify About page ACF migration"
```

- [ ] **Step 5: Request one final whole-branch review, address findings, rerun affected checks, and push `main` only after the worktree is clean and local/remote ancestry is safe.**
