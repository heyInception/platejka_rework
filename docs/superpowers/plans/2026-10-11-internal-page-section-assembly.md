# Internal Page Section Assembly Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give eligible compact `page.php` pages the approved shared-section composition and add a reusable, page-local Process section with preview-first legacy migration.

**Architecture:** Extend the existing Local JSON section schema and section loader with one `process` component, while reusing the six shared components already in `platejka_rework`. Isolate ordering, mapping, and conflict detection in pure helpers, then wrap them in an idempotent WP-CLI command that defaults to dry-run and requires explicit approval before database writes.

**Tech Stack:** PHP 8.1+, WordPress 7.0.2, ACF Pro 6.2.6.1, WP-CLI through WordPress Studio, Local JSON, CSS Grid, PHPUnit 10, Playwright 1.63.

**Spec:** `docs/superpowers/specs/2026-10-11-internal-page-section-assembly-design.md`

## Global Constraints

- Do not apply the migration to the Studio database before the user reviews the real dry-run and explicitly confirms `--apply`.
- Target only default-template pages with the builder enabled and a recognized compact sequence ending in FAQ; exclude Home, About, the expanded China page, sitemap-only pages, and other special compositions.
- Preserve existing Hero, Shipments, Cases, and FAQ rows byte-for-byte except for their position in the resulting row list.
- Do not add absent Shipments or Cases rows.
- Do not read into, copy, modify, render, or remove legacy `skvoznye_bloki` rows.
- Add Process only when legacy `process` contains meaningful data; ignore `vybor_elementa`.
- Shared Calculator, Guarantees, Reviews, Destinations, SEO, and Call rows must contain no local content override.
- Do not create or enqueue `process.js`; enqueue `process.css` only when Process is resolved on the page.
- Preserve all unrelated dirty-worktree changes and stage only task-owned files in each commit.

## Review Focus

- A page with the right slugs in the wrong order, duplicates, or an extra section must be a conflict, not an automatic rewrite.
- Empty and partially corrupt legacy Process rows must not generate empty cards or a blank section.
- Existing local data nested under ACF clone wrappers must survive normalization and reordering without display formatting or type drift.
- A page containing legacy cross-block data must migrate identically to one without it, while the cross-block postmeta remains unchanged.
- A repeated preview after equivalent post-migration rows or a verified marker must plan zero writes.

---

### Task 1: Register the Process data contract

**Files:**
- Modify: `plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json`
- Modify: `plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json`
- Modify: `themes/platejka_rework/inc/section-builder.php`
- Modify: `tests/integration/acf-section-builder-schema.php`
- Modify: `tests/theme/section-builder.php`
- Modify: `tests/theme/section-loader.php`

**Interfaces:**
- Produces: canonical `process` group with `title:string`, `items:list<{title:string,text:string}>`, and `description:string`; page-builder layout `process`; registered section slug `process`; builder-row validation that requires a title and at least one complete step only when an enabled Process row is present.
- Produces stable keys: `field_platejka_defaults_process_v1`, `field_platejka_process_title_v1`, `field_platejka_process_items_v1`, `field_platejka_process_item_title_v1`, `field_platejka_process_item_text_v1`, `field_platejka_process_description_v1`, and `layout_platejka_process_v1`.
- Consumes: existing `enabled`, `anchor`, and clone-wrapper conventions from adjacent page-builder layouts.

- [ ] **Step 1: Add failing schema assertions** that require the Process group, exact child names and stable keys, optional canonical default values, and a Process builder layout whose override clone points to `field_platejka_defaults_process_v1`.
- [ ] **Step 2: Add failing theme assertions** that `platejka_section_builder_registry()` accepts `process`, normalizes it to `sections/process/process.php`, rejects an enabled Process row without a title or complete step, accepts a complete Process row, and retains the existing single-Hero and anchor validation behavior.
- [ ] **Step 3: Run the focused checks and verify failure.**
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\acf-section-builder-schema.php`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-builder.php`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php`
  - Expected: only the new Process assertions fail because its schema, layout, registry entry, and template do not exist.
- [ ] **Step 4: Add the minimal Local JSON definitions, registry entry, and row-level validator.** Keep the canonical Process defaults optional and empty so saving the global options page never requires demonstration copy; expose the three data fields through the layout's local clone override, then enforce title plus one complete step in `platejka_validate_builder_rows()` only for an enabled Process row.
- [ ] **Step 5: Re-run the focused checks.**
  - Expected: all three scripts report zero failures.
- [ ] **Step 6: Commit the Process contract.**
  - Stage only the six files listed in this task.
  - Commit: `feat: register process section contract`

### Task 2: Build the Process section template and responsive styles

**Files:**
- Create: `themes/platejka_rework/sections/process/process.php`
- Create: `themes/platejka_rework/sections/process/process.css`
- Modify: `tests/theme/section-template-content.php`
- Modify: `tests/theme/section-loader.php`

**Interfaces:**
- Consumes: `$section_data:array`, `$section_instance:string`, and `$section_anchor:string` supplied by `platejka_render_section()`; Task 1 field names.
- Produces: a semantic Process section with an ordered list of complete steps, escaped titles, `wp_kses_post()` body content, automatic visual numbering, and no output for an empty title or zero complete steps.

- [ ] **Step 1: Add failing template assertions** using complete, empty, and partially corrupt fixtures. Assert the complete fixture renders the supplied anchor, title, ordered six-step markup, descriptions, and no seed fallback; assert incomplete items are filtered; assert empty title or no complete items emits an empty string.
- [ ] **Step 2: Add failing asset assertions** that a resolved `process` section registers/enqueues `platejka-rework-section-process`, a page without Process does not enqueue it, and no `platejka-rework-section-process` script exists.
- [ ] **Step 3: Run the theme checks and verify failure.**
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-template-content.php`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php`
  - Expected: failure for missing Process markup and stylesheet.
- [ ] **Step 4: Implement `process.php`.** Filter `items` to rows with non-empty stripped title and text before the fail-closed check; use an `<ol>`/`<li>` structure so document order carries the step semantics; mark connector decoration as non-semantic.
- [ ] **Step 5: Implement `process.css`.** Use the current theme container and custom properties, a responsive grid with automatic columns, CSS counters or native list numbering, desktop connectors, and a single-column mobile layout with connectors removed. Do not port fixed `335px`, `450px`, or `683px` legacy widths.
- [ ] **Step 6: Re-run the focused checks.**
  - Expected: both scripts report zero failures; source assertions confirm there is no `process.js`.
- [ ] **Step 7: Commit the rendered section.**
  - Stage only the four files listed in this task.
  - Commit: `feat: add responsive process section`

### Task 3: Implement pure ordering and legacy mapping

**Files:**
- Create: `plugins/platejka-core/src/Cli/InternalPageAssemblyMigrationCommand.php`
- Create: `tests/unit/internal-page-assembly-migration.php`

**Interfaces:**
- Produces: `InternalPageAssemblyMigrationCommand::isEligibleStatus(string $status): bool`.
- Produces: `InternalPageAssemblyMigrationCommand::isCompactSequence(array $rows): bool`; accepted pre-migration slug sequences are exactly `hero > faq`, `hero > shipments > faq`, `hero > cases > faq`, and `hero > shipments > cases > faq`.
- Produces: `InternalPageAssemblyMigrationCommand::normalizeRawValue($value, ?callable $field_resolver = null)` without formatting scalar content or media IDs.
- Produces: `InternalPageAssemblyMigrationCommand::mapLegacyProcess($value): ?array` returning `title`, complete `items[{title,text}]`, and optional `description`, with no `vybor_elementa`.
- Produces: `InternalPageAssemblyMigrationCommand::reconcileRows(array $rows, ?array $process): array{rows:array,conflicts:array}`.
- The canonical post-migration order is existing `hero`, optional existing `shipments`, optional existing `cases`, inheritance-only `calculator`, optional local `process`, inheritance-only `guarantees`, `review`, `destinations`, `seo`, existing `faq`, and inheritance-only `call`.
- Every new shared row has exactly `acf_fc_layout`, `enabled => 1`, `anchor => ''`, and an empty `overrides` value; the Process row instead stores the mapped payload under `overrides.process`.

- [ ] **Step 1: Write the failing procedural unit test** for all five Review Focus cases plus status eligibility, the four accepted compact sequences, rejection of Hero-only and Hero-missing sequences, mapping of six/seven-step fixtures, incomplete-row filtering, exact canonical order, byte-for-byte preservation of existing rows, empty overrides on new shared rows, duplicate/extra-row conflicts, and idempotent reconciliation of an equivalent post-migration sequence.
- [ ] **Step 2: Run the unit test and verify failure.**
  - Run: `php tests\unit\internal-page-assembly-migration.php`
  - Expected: exit `1` with `InternalPageAssemblyMigrationCommand is missing.`
- [ ] **Step 3: Implement the pure public helpers** in `InternalPageAssemblyMigrationCommand`. Keep WordPress/database calls out of these helpers; return conflict metadata without mutating the input on conflict.
- [ ] **Step 4: Re-run the unit test.**
  - Expected: `Internal page assembly migration contracts passed.`
- [ ] **Step 5: Run PHP lint on the new files.**
  - Run: `vendor\bin\parallel-lint plugins\platejka-core\src\Cli\InternalPageAssemblyMigrationCommand.php tests\unit\internal-page-assembly-migration.php`
  - Expected: no syntax errors.
- [ ] **Step 6: Commit the pure migration slice.**
  - Commit: `feat: map internal page assembly`

### Task 4: Add preview/apply orchestration and integration contracts

**Files:**
- Modify: `plugins/platejka-core/src/Cli/InternalPageAssemblyMigrationCommand.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Create: `tests/integration/internal-page-assembly-migration.php`
- Create: `tests/integration/internal-page-assembly-migration-applied.php`
- Modify: `README.md`

**Interfaces:**
- Produces CLI command: `wp platejka section-builder migrate-internal-pages [--apply]`.
- Produces option marker: `platejka_internal_page_assembly_version = 1`, written only after target row writes are read back and verified.
- Consumes legacy Process field key `field_6790b8aa79094`, builder-enabled key `field_platejka_page_builder_v1`, and builder rows key `field_platejka_sections_v1`.
- Produces report keys: `mode`, `already_migrated`, `totals`, `pages`, `excluded`, `conflicts`, and `writes.{performed,planned}`.

- [ ] **Step 1: Add failing orchestration assertions** for default-template filtering, builder-enabled filtering, accepted statuses, recognized sequence filtering, full-page conflict skips, no cross-block access in the command source, verification-before-marker ordering, and read-only preview behavior.
- [ ] **Step 2: Add a read-only integration snapshot** hashing the marker, builder flag, raw builder rows, page template, raw legacy Process value, and raw `skvoznye_bloki` value for every editorial page. Assert preview leaves the hash unchanged, reports exactly 135 currently eligible compact pages, maps Process only for pages `2098` and `2109`, excludes page `1873`, and plans no writes for pages `1469`, `2279`, or `3075`.
- [ ] **Step 3: Run the unit and integration checks and verify failure.**
  - Run: `php tests\unit\internal-page-assembly-migration.php`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\internal-page-assembly-migration.php`
  - Expected: new orchestration/report assertions fail; the database snapshot remains unchanged.
- [ ] **Step 4: Implement `preview(): array`, `apply(): array`, command invocation, candidate discovery, planned-write reporting, write verification, and marker persistence.** A default-template builder page containing only compact slugs plus both Hero and FAQ but with duplicates or invalid order is a conflict; Hero-only, Hero-missing, and special expanded compositions are exclusions. Never call `get_field('skvoznye_bloki', ...)` or its field key from product code.
- [ ] **Step 5: Register the command and document usage.** Add the `require_once` and `WP_CLI::add_command()` entry in `platejka-core.php`; document dry-run, `--apply`, mappings, exclusions, conflicts, and rollback expectations in `README.md`.
- [ ] **Step 6: Implement the isolated applied-state integration contract.** It must verify canonical rows, page-local Process values, inherited shared rows, unchanged cross-block postmeta, marker-after-verification, and a second preview with zero planned writes. Do not run this script against the real local database before explicit approval.
- [ ] **Step 7: Re-run all non-destructive focused checks.**
  - Run: `php tests\unit\internal-page-assembly-migration.php`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\internal-page-assembly-migration.php`
  - Expected: contracts pass; preview reports `writes.performed = false`; before/after hashes match.
- [ ] **Step 8: Run the broader automated suites.**
  - Run: `vendor\bin\phpunit --configuration phpunit.xml.dist`
  - Run: `composer lint`
  - Run the existing Studio theme/integration scripts affected by section schema and loading.
  - Expected: zero failures and zero lint errors.
- [ ] **Step 9: Commit orchestration and documentation.**
  - Stage only the five files listed in this task.
  - Commit: `feat: add internal page assembly migration`

### Task 5: Produce and review the real dry-run

**Files:**
- No product files expected.

**Interfaces:**
- Consumes: Task 4 CLI command against `C:\Users\Inception\Studio\platejka`.
- Produces: the exact read-only migration report for user review.

- [ ] **Step 1: Verify the live environment.**
  - Run: `studio status --path C:\Users\Inception\Studio\platejka --format json`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka plugin get advanced-custom-fields-pro --format=json`
  - Expected: site online; ACF Pro active at `6.2.6.1` unless a changed version is explicitly reviewed first.
- [ ] **Step 2: Run the command without `--apply`.**
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-internal-pages`
  - Expected: `mode = dry-run`, `writes.performed = false`, 135 eligible pages in the current audited database, Process mappings only for `2098` and `2109`, no special-page writes, and no unexplained conflicts.
- [ ] **Step 3: Compare the report against the approved invariants.** Check every conflict, exact current/target sequences, empty local data on shared rows, preserved optional Shipments/Cases absence, and unchanged cross-block state via the integration snapshot.
- [ ] **Step 4: Present totals, exclusions, conflicts, and planned writes to the user and stop.** Request explicit approval before any real `--apply`.

### Task 6: Apply and visually verify after explicit approval

**Files:**
- Create or modify only if needed after observed defects: `tests/e2e/internal-page-assembly.spec.ts`

**Interfaces:**
- Consumes: explicit user approval of Task 5's exact report.
- Produces: verified migrated database state and desktop/mobile evidence for pages `2098`, `2109`, and one eligible page without Process.

- [ ] **Step 1: Run the approved write command.**
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-internal-pages --apply`
  - Expected: all approved writes verify before marker `1` is saved; any runtime conflict aborts rather than overwriting data.
- [ ] **Step 2: Run the applied-state and idempotence checks.**
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\internal-page-assembly-migration-applied.php`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-internal-pages`
  - Expected: applied contract passes; repeat preview plans zero writes.
- [ ] **Step 3: Add or update Playwright assertions** for section order, Process visibility on `2098`/`2109`, Process absence on the control page, and absence of a `process.js` request.
- [ ] **Step 4: Run desktop and mobile Playwright checks.**
  - Run: `$env:PLAYWRIGHT_BASE_URL='http://localhost:8883'; npx playwright test tests/e2e/internal-page-assembly.spec.ts`
  - Expected: order and visibility assertions pass at desktop and mobile viewports.
- [ ] **Step 5: Visually inspect Process at both viewports.** Confirm no overflow, readable step order, sensible final odd-card sizing, removed mobile connectors, and consistent spacing adjacent to Calculator and Guarantees.
- [ ] **Step 6: Fix only verified defects through a failing regression assertion first, rerun focused and full checks, and commit the verification slice.**
  - Commit: `test: verify internal page assembly`
