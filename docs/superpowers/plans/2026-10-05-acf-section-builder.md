# ACF Section Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add shared ACF section defaults, a reusable page builder, editable content for all 18 home sections, and an idempotent migration that preserves the current home page.

**Architecture:** `platejka-core` owns the ACF options page, Local JSON schemas, admin validation, and a WP-CLI migration command. The theme owns builder resolution and rendering: page rows normalize into the existing section loader, section templates consume resolved data, and the loader passes repeat-safe instance context. A versioned seed JSON contains migration input only and is never a runtime content fallback.

**Tech Stack:** WordPress 7.0.2, PHP 8.4 runtime/PHP 8.1 minimum, ACF Pro 6.2.6.1, Contact Form 7, ACF Local JSON, WordPress Studio CLI, PHPUnit 10, Playwright.

**Spec:** `docs/superpowers/specs/2026-10-05-acf-section-builder-design.md`

## Global Constraints

- Add exactly two new ACF field groups; leave every legacy group and value untouched.
- Store field schemas only in `plugins/platejka-core/acf-json`; do not create DB-backed field-group posts.
- The builder applies to all `page` posts but is disabled by default.
- Page 24 is the only page migrated in this plan and must retain the exact 18-section order from the spec.
- New section rows default disabled; enabled empty rows still render.
- Resolve content as page override → global default → empty; never fall back to template copy.
- Empty page repeaters/galleries inherit; non-empty page collections replace globally.
- Permit duplicate non-hero sections and reject more than one hero-family row.
- Preserve `the_content()` and the exact existing SEO title branch.
- Use attachment IDs plus `wp_get_attachment_image()` for editorial images.
- Use CF7 form 305 for `call` and form 4966 for `calculator` after migration.
- Database writes and media imports require a dry-run report and explicit user confirmation.
- Production deployment and preview-site publication are out of scope.

## Review Focus

- A builder saved with zero rows must render zero page sections rather than falling back to PHP; cover in Task 2.
- A duplicate or invalid custom anchor must fail validation rather than be silently renamed; cover in Task 2.
- A false local tri-state override must win over a true global value; cover in Task 3.
- A deleted CF7 form or invalid attachment ID must not execute/render unsafe output; cover in Task 3.
- A second migration run must report no new media or row creation and preserve the same values; cover in Task 6.

---

### Task 1: Register the options page and Local JSON contracts

**Files:**
- Create: `plugins/platejka-core/src/Acf/SectionDefaults.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Create: `plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json`
- Create: `plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json`
- Modify: `tests/integration/acf-local-json.php`

**Interfaces:**
- Consumes: ACF Pro `acf_add_options_page()`, Local JSON loading already registered by `Platejka\Core\Acf\LocalJson`.
- Produces: `Platejka\Core\Acf\SectionDefaults::register(): void`, options post ID `platejka_section_defaults`, field keys `field_platejka_page_builder_v1` and `field_platejka_sections_v1`, and the canonical layout/field keys consumed by later tasks.

- [ ] **Step 1: Extend the failing Local JSON integration test**

Add assertions that:

- nine schema files exist;
- both new groups load from JSON and have no DB-backed field-group post;
- the defaults group location targets `options_page == platejka-section-defaults`;
- the builder group location targets `post_type == page` and uses `acf_after_title`;
- all 18 layout names exist in the spec order;
- every layout has `enabled` (default `0`) and `anchor` controls;
- `shipments`, `guarantees`, and `documents` expose `inherit/main/default` variants;
- image fields return IDs and CF7 selectors restrict `post_type` to `wpcf7_contact_form` and return IDs;
- only field types reported by the live ACF install are used;
- the options page has post ID `platejka_section_defaults` and capability `edit_pages`.

- [ ] **Step 2: Run the integration test and verify RED**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\acf-local-json.php
```

Expected: FAIL because the options page and two JSON schemas do not exist.

- [ ] **Step 3: Implement options-page registration**

Create `SectionDefaults::register()` and call it only when ACF is available. Register on `acf/init` with slug `platejka-section-defaults`, post ID `platejka_section_defaults`, capability `edit_pages`, no redirect, and translatable Russian labels.

- [ ] **Step 4: Add the two Local JSON schemas**

The defaults group contains one named group per section and separate `main`/`default` groups for the three variant sections. The page group contains the builder toggle plus one flexible-content layout per section. Duplicate the editorial field structure with unique field keys; page-only fields remain empty and never carry `default_value` content. Use conditional logic for variant-specific override groups.

- [ ] **Step 5: Run the integration test and PHP syntax checks**

Run:

```powershell
php -l plugins/platejka-core/src/Acf/SectionDefaults.php
php -l plugins/platejka-core/platejka-core.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\acf-local-json.php
```

Expected: both syntax checks pass and the integration test reports zero failures.

- [ ] **Step 6: Commit**

```powershell
git add plugins/platejka-core/src/Acf/SectionDefaults.php plugins/platejka-core/platejka-core.php plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json tests/integration/acf-local-json.php
git commit -m "feat: add ACF section builder schemas"
```

### Task 2: Resolve builder rows and validate page structure

**Files:**
- Create: `themes/platejka_rework/inc/section-builder.php`
- Modify: `themes/platejka_rework/functions.php`
- Modify: `themes/platejka_rework/inc/assets.php`
- Modify: `themes/platejka_rework/page-home.php`
- Modify: `themes/platejka_rework/page-about.php`
- Modify: `themes/platejka_rework/page.php`
- Create: `tests/theme/section-builder.php`
- Modify: `tests/theme/section-loader.php`

**Interfaces:**
- Consumes: `field_platejka_page_builder_v1`, `field_platejka_sections_v1`, layout keys from Task 1, and existing `platejka_use_sections()`/`platejka_render_sections()`.
- Produces: `platejka_get_page_sections( int $post_id, array $fallback ): array`, `platejka_validate_builder_rows( array $rows ): true|WP_Error`, and normalized configs containing `slug`, `mode`, `row`, `instance`, and `anchor`.

- [ ] **Step 1: Write the failing builder integration test**

Cover these behaviors with real ACF values restored in a `finally` block:

- disabled builder returns the supplied fallback;
- enabled builder with no rows returns an empty array;
- enabled rows retain stored order and filter only `enabled == 1`;
- two `faq` rows are accepted and receive different instance IDs;
- any two hero-family rows return validation errors;
- invalid or duplicate anchors return validation errors;
- `main/default/inherit` mode normalization is deterministic;
- disabled rows do not enqueue assets and repeated enabled rows enqueue each directory once.

- [ ] **Step 2: Run the new test and verify RED**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-builder.php
```

Expected: FAIL because the builder functions are undefined.

- [ ] **Step 3: Implement builder resolution and ACF validation**

Add a fixed layout registry. Hook `acf/validate_value/key=field_platejka_sections_v1` to reject multiple hero-family rows and invalid/duplicate anchors. Sanitize valid anchors with `sanitize_title()` but require the submitted value to already equal its sanitized form. Derive internal instance IDs from post ID plus row index; custom anchors remain the public section ID.

- [ ] **Step 4: Connect every page template to the builder fallback**

Keep each current PHP array as the fallback argument, resolve page sections before `get_header()`, then pass the result to the existing loader. Do not change fallback order or variants.

- [ ] **Step 5: Run builder and loader tests**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-builder.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php
```

Expected: both report zero failures and non-migrated pages preserve their existing order.

- [ ] **Step 6: Commit**

```powershell
git add themes/platejka_rework/inc/section-builder.php themes/platejka_rework/functions.php themes/platejka_rework/inc/assets.php themes/platejka_rework/page-home.php themes/platejka_rework/page-about.php themes/platejka_rework/page.php tests/theme/section-builder.php tests/theme/section-loader.php
git commit -m "feat: resolve ACF page section layouts"
```

### Task 3: Add inheritance, media, heading, and CF7 rendering helpers

**Files:**
- Create: `plugins/platejka-core/data/section-defaults-v1.json`
- Create: `plugins/platejka-core/src/Acf/SectionSeed.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Create: `themes/platejka_rework/inc/section-content.php`
- Modify: `themes/platejka_rework/functions.php`
- Modify: `themes/platejka_rework/inc/assets.php`
- Create: `tests/theme/section-content.php`

**Interfaces:**
- Consumes: normalized section configs from Task 2 and global fields from options post ID `platejka_section_defaults`.
- Produces: `Platejka\Core\Acf\SectionSeed::get(): array`, `platejka_resolve_section_data( array $config, int $post_id ): array`, `platejka_resolve_scalar( mixed $local, mixed $global ): mixed`, `platejka_resolve_collection( mixed $local, mixed $global ): array`, `platejka_resolve_tristate( string $local, bool $global ): bool`, `platejka_section_image( int $attachment_id, string $size, array $attributes = array() ): string`, `platejka_section_heading( array $heading, string $class ): string`, and `platejka_cf7_form( int $form_id ): string`.

- [ ] **Step 1: Write failing helper integration tests**

Assert:

- empty scalar inherits and non-empty scalar overrides;
- local string `0` is not treated as empty;
- empty repeater inherits while a non-empty repeater replaces completely;
- tri-state `off` beats a true global and `on` beats a false global;
- `main` and `default` choose separate global/override groups;
- two equal layouts resolve separate page-row overrides;
- semantic headings stay visible `<h2>` and decorative headings produce one screen-reader `<h2>` plus an `aria-hidden` div from the same escaped text;
- valid attachments render intrinsic dimensions, `srcset`, `sizes`, and requested loading/fetchpriority attributes;
- invalid attachment IDs return an empty string;
- valid CF7 IDs render only the selected form and invalid/deleted/non-CF7 IDs return an empty string;
- repeated section output has unique IDs with matching ARIA references.
- before migration marker `platejka_section_builder_version == 1` exists, missing global option values resolve from the versioned seed so the site does not go blank while code is staged;
- after the marker exists, an empty global value stays empty and never falls back to the seed.

- [ ] **Step 2: Run the helper test and verify RED**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-content.php
```

Expected: FAIL because the helper interface does not exist.

- [ ] **Step 3: Create the versioned seed and seed reader**

Transcribe the currently rendered home editorial content into deterministic JSON. Store source media paths/URLs, not runtime attachment IDs. Include both `main` and `default` content for the three variant sections, form IDs 305/4966, and all 18 home rows. Exclude `the_content()` and the SEO heading branch. `SectionSeed::get()` validates the schema version and returns an empty array on malformed data.

- [ ] **Step 4: Implement minimal helpers and pass render context**

Resolve defaults with `get_field( <section-name>, 'platejka_section_defaults' )`, keep page rows supplied in normalized configs, and pass `$section_data`, `$section_instance`, and `$section_anchor` into section templates. Until option `platejka_section_builder_version` equals `1`, use the seed only for missing global values; once migrated, never consult it as a content fallback. Use `wp_get_attachment_image()` and a fixed CF7 post-type check; never execute stored shortcode text.

- [ ] **Step 5: Run the helper and existing loader tests**

Run the Task 3 test followed by `tests/theme/section-loader.php`.

Expected: both report zero failures.

- [ ] **Step 6: Commit**

```powershell
git add plugins/platejka-core/data/section-defaults-v1.json plugins/platejka-core/src/Acf/SectionSeed.php plugins/platejka-core/platejka-core.php themes/platejka_rework/inc/section-content.php themes/platejka_rework/functions.php themes/platejka_rework/inc/assets.php tests/theme/section-content.php tests/theme/section-loader.php
git commit -m "feat: resolve inherited section content"
```

### Task 4: Convert foundational and repeated-content home sections

**Files:**
- Modify: `themes/platejka_rework/sections/hero/hero-main.php`
- Modify: `themes/platejka_rework/sections/about/about.php`
- Modify: `themes/platejka_rework/sections/compliance/compliance.php`
- Modify: `themes/platejka_rework/sections/review-main/review-main.php`
- Modify: `themes/platejka_rework/sections/work/work.php`
- Modify: `themes/platejka_rework/sections/with-us/with-us.php`
- Modify: `themes/platejka_rework/sections/destinations/destinations.php`
- Modify: `themes/platejka_rework/sections/problems/problems.php`
- Modify: `themes/platejka_rework/sections/serves/serves.php`
- Modify: `themes/platejka_rework/sections/cases/cases.php`
- Modify: `themes/platejka_rework/sections/table/table.php`
- Create: `tests/theme/section-template-content.php`

**Interfaces:**
- Consumes: `$section_data`, `$section_instance`, `$section_anchor`, and helpers from Task 3.
- Produces: eleven templates with no embedded editorial fallback and repeat-safe IDs.

- [ ] **Step 1: Write failing template-content assertions**

For each listed template, render a fixture whose local values override one scalar, one collection, and one image where applicable. Assert escaped output, global inheritance for an empty local value, full collection replacement, responsive image markup, stable data hooks, and absence of the replaced hard-coded copy.

- [ ] **Step 2: Run the template test and verify RED**

Run the new Studio eval-file test.

Expected: FAIL because the templates still contain static editorial content.

- [ ] **Step 3: Convert the eleven templates**

Keep the existing DOM hierarchy, CSS classes, decorative asset paths, and JavaScript data attributes. Replace editorial literals with escaped resolved data. Use repeaters for cards/items and `platejka_section_image()` for editorial media. Apply the decorative-heading pattern only to headings whose current markup needs styled fragments or controlled line breaks.

- [ ] **Step 4: Run template tests and PHP syntax checks**

Run the new test, `tests/theme/section-loader.php`, and `php -l` for every modified template.

Expected: all pass with no warnings.

- [ ] **Step 5: Commit**

```powershell
git add themes/platejka_rework/sections/hero/hero-main.php themes/platejka_rework/sections/about/about.php themes/platejka_rework/sections/compliance/compliance.php themes/platejka_rework/sections/review-main/review-main.php themes/platejka_rework/sections/work/work.php themes/platejka_rework/sections/with-us/with-us.php themes/platejka_rework/sections/destinations/destinations.php themes/platejka_rework/sections/problems/problems.php themes/platejka_rework/sections/serves/serves.php themes/platejka_rework/sections/cases/cases.php themes/platejka_rework/sections/table/table.php tests/theme/section-template-content.php
git commit -m "feat: make home section content editable"
```

### Task 5: Convert variant, dynamic, form, FAQ, and SEO sections

**Files:**
- Modify: `themes/platejka_rework/sections/shipments/shipments.php`
- Modify: `themes/platejka_rework/sections/guarantees/guarantees.php`
- Modify: `themes/platejka_rework/sections/documents/documents.php`
- Modify: `themes/platejka_rework/sections/calculator/calculator.php`
- Modify: `themes/platejka_rework/sections/seo/seo.php`
- Modify: `themes/platejka_rework/sections/faq/faq.php`
- Modify: `themes/platejka_rework/sections/call/call.php`
- Modify: `tests/theme/forms-calculator.php`
- Modify: `tests/theme/section-template-content.php`
- Modify: `tests/e2e/seo-toggle.spec.ts`

**Interfaces:**
- Consumes: variant selection, content/media helpers, and CF7 renderer from Task 3.
- Produces: seven remaining home templates backed by the new data model while preserving calculator logic and the SEO-title exception.

- [ ] **Step 1: Add failing tests for variant and integration behavior**

Assert that:

- `main` and `default` variants use only their own data;
- document downloads use validated attachment URLs;
- calculator rates and calculation hooks remain programmatic while labels and CF7 form become editable;
- `call` mounts form 305 and calculator mounts form 4966 from resolved data;
- FAQ IDs/ARIA references are unique across two instances;
- SEO still calls `the_content()` and retains the exact page-2054/title branch;
- SEO cards and toggle labels come from resolved data;
- no migrated template contains its former editorial fallback strings.

- [ ] **Step 2: Run focused tests and verify RED**

Run `tests/theme/section-template-content.php`, `tests/theme/forms-calculator.php`, and the focused SEO Playwright spec.

Expected: failures identify static content and missing CF7/resolved-data behavior.

- [ ] **Step 3: Convert the seven templates**

Retain the existing `@if (mode...)` compatibility until both branches consume resolved variant data. Preserve all calculator endpoint/rate behavior. Replace the static `call` form and calculator mount placeholder with `platejka_cf7_form()`. Keep the SEO heading branch textually and behaviorally unchanged.

- [ ] **Step 4: Run focused and regression tests**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-template-content.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\forms-calculator.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php
npm run test:e2e -- --grep "SEO"
```

Expected: all focused checks pass; home remains on fallback until migration.

- [ ] **Step 5: Commit**

```powershell
git add themes/platejka_rework/sections/shipments/shipments.php themes/platejka_rework/sections/guarantees/guarantees.php themes/platejka_rework/sections/documents/documents.php themes/platejka_rework/sections/calculator/calculator.php themes/platejka_rework/sections/seo/seo.php themes/platejka_rework/sections/faq/faq.php themes/platejka_rework/sections/call/call.php tests/theme/forms-calculator.php tests/theme/section-template-content.php tests/e2e/seo-toggle.spec.ts
git commit -m "feat: migrate dynamic sections to ACF content"
```

### Task 6: Build the deterministic migration preview and idempotent apply command

**Files:**
- Create: `plugins/platejka-core/src/Cli/SectionBuilderMigrationCommand.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Create: `plugins/platejka-core/tests/SectionBuilderMigrationTest.php`
- Create: `tests/integration/section-builder-migration.php`

**Interfaces:**
- Consumes: canonical ACF field keys from Task 1, current approved home content, page ID 24, CF7 IDs 305/4966, and WordPress media APIs.
- Produces: `wp platejka section-builder migrate` (dry-run by default) and `wp platejka section-builder migrate --apply`; both emit the same deterministic JSON report shape with `options`, `page`, `media.reused`, `media.to_import|imported`, and `writes`; apply sets `platejka_section_builder_version` to `1` only after every write succeeds.

- [ ] **Step 1: Write failing unit and integration tests**

Unit-test report ordering, media-source normalization, attachment reuse, 18-row construction, apply idempotency through in-memory WordPress/ACF adapters, and no duplicate planned imports. Integration-test only the real dry-run: default invocation leaves option/page/media hashes unchanged and reports the exact section order and CF7 IDs. Do not run a real apply, even with cleanup, before the Task 7 approval gate.

- [ ] **Step 2: Run tests and verify RED**

Run PHPUnit for `SectionBuilderMigrationTest` and the Studio integration eval-file.

Expected: FAIL because the command and seed do not exist.

- [ ] **Step 3: Implement dry-run and apply modes**

Default to dry-run. Resolve existing attachments before planning imports. On `--apply`, import only missing editorial images, update the new option fields, enable page 24's builder, replace its new flexible-content value with the exact 18-row target, and finally set migration version `1`. Never touch legacy field keys. Register the command only when `WP_CLI` is defined.

- [ ] **Step 4: Run unit and read-only integration tests**

Run:

```powershell
composer test -- --filter SectionBuilderMigrationTest
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\section-builder-migration.php
```

Expected: unit tests pass, including adapter-level apply idempotency; live integration reports preview read-only and performs no write or import.

- [ ] **Step 5: Commit**

```powershell
git add plugins/platejka-core/src/Cli/SectionBuilderMigrationCommand.php plugins/platejka-core/platejka-core.php plugins/platejka-core/tests/SectionBuilderMigrationTest.php tests/integration/section-builder-migration.php
git commit -m "feat: add section builder migration command"
```

### Task 7: Preview the real local migration and obtain approval

**Files:**
- No code changes expected.
- Save command output in the plan workspace for review; do not commit runtime output.

**Interfaces:**
- Consumes: dry-run command from Task 6.
- Produces: the exact local database/media diff required by the ACF safety protocol.

- [ ] **Step 1: Capture current hashes and run the dry-run command**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate
```

Expected: deterministic JSON describing current → new values, page 24, media reuse/import candidates, and no mutation.

- [ ] **Step 2: Prove preview did not mutate state**

Run the read-only migration integration snapshot and compare its before/after hashes.

Expected: hashes are identical.

- [ ] **Step 3: Present the exact diff and stop for explicit confirmation**

List option target, page target, field changes, row count/order, media reuse/import candidates, and CF7 selections. Do not run `--apply` until the user confirms this preview.

### Task 8: Apply the approved migration and verify the site

**Files:**
- Modify only local WordPress values/media through the approved command.
- Modify: `tests/fixtures/acf-page-baseline.json`
- Update tests only if the live result exposes a spec-level mismatch, using RED→GREEN.

**Interfaces:**
- Consumes: explicit approval from Task 7 and apply mode from Task 6.
- Produces: populated shared defaults, page 24's active 18-row builder, and verified public rendering.

- [ ] **Step 1: Apply once, then rerun preview**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate --apply
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate
```

Expected: apply succeeds; subsequent preview reports no pending writes or imports.

- [ ] **Step 2: Run complete automated verification**

Run:

```powershell
composer test
npm run test:unit
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\acf-local-json.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-builder.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-content.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-template-content.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php
npm run test:e2e
```

Expected: all suites pass with no PHP warnings or browser console errors.

- [ ] **Step 3: Visually smoke-test migrated and fallback pages**

Open `/`, `/o-kompanii/`, and `/china/`. Confirm the home section order/content, responsive images, working FAQ/calculator/forms, unique IDs, and that the two non-migrated pages still use their PHP fallbacks.

- [ ] **Step 4: Run an idempotency apply check**

Run the apply command a second time.

Expected: zero new attachments, zero duplicate rows, identical target hashes.

- [ ] **Step 5: Commit any verification-driven code fixes**

If no fixes were required, create no empty commit. If fixes were required, each must have its own observed failing test before implementation and pass the complete relevant suite.
