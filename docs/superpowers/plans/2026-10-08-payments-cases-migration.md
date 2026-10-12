# Payments and Cases ACF Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the agreed ACF fields and rendering behavior, then provide a preview-first migration that moves legacy services and cases into the section builder without overwriting editor content.

**Architecture:** Keep field definitions in canonical Local JSON, resolve the local description policy in the theme content boundary, and isolate legacy-to-builder conversion in a new WP-CLI command with pure public transformation helpers. Hide legacy editor fields only after the migration marker is verified, while retaining their registered definitions and raw metadata for rollback.

**Tech Stack:** PHP 8.1+, WordPress 7.0, ACF Pro 6.2.6.1, WP-CLI through WordPress Studio, PHPUnit 10, Local JSON.

**Spec:** `docs/superpowers/specs/2026-10-08-payments-cases-migration-design.md`

## Global Constraints

- Do not mutate the Studio database before the user reviews the dry-run and explicitly confirms `--apply`.
- Do not modify the page-2054 SEO branch.
- Preserve non-empty destination overrides and report conflicts.
- Preserve legacy postmeta; hide fields only after the verified migration marker exists.
- Use WordPress escaping in templates and stable ACF field keys in writes.

## Review Focus

- Raw ACF field-key arrays must normalize to names without formatting WYSIWYG content or image IDs.
- A partially populated legacy card must migrate without inventing fields other than the required five stars.
- Existing non-empty destination rows must survive byte-for-byte except for canonical ordering.
- Pages with only a cases title must inherit global cards while suppressing only the global description.
- A second dry-run after equivalent target data exists must plan zero writes.

---

### Task 1: ACF schema and section rendering

**Files:**
- Modify: `plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json`
- Modify: `plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json`
- Modify: `themes/platejka_rework/inc/section-content.php`
- Modify: `themes/platejka_rework/sections/shipments/shipments.php`
- Modify: `themes/platejka_rework/sections/shipments/shipments.css`
- Modify: `tests/integration/acf-section-builder-schema.php`
- Modify: `tests/theme/section-content.php`
- Modify: `tests/theme/section-template-content.php`

**Interfaces:**
- Produces: `price` in `shipments.main.items`; row-level `description_mode`; resolved empty `description` only when mode is `hide` and no local description exists.
- Consumes: existing section merge and clone-wrapper conventions.

- [ ] **Step 1: Write failing integration assertions** for the price field, both `description_mode` controls, hide/inherit resolution, and price markup inside `.shipment-card__title`.
- [ ] **Step 2: Run the focused Studio checks** and confirm they fail for the missing fields/behavior.
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file <worktree>\tests\integration\acf-section-builder-schema.php`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file <worktree>\tests\theme\section-content.php`
  - Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file <worktree>\tests\theme\section-template-content.php`
  - Expected: each new assertion fails for the named missing contract.
- [ ] **Step 3: Implement the schema, resolver, markup, and compact price styling** using stable keys and escaped output.
- [ ] **Step 4: Re-run the focused Studio checks**.
  - Expected: all three report zero failures and explicit success.
- [ ] **Step 5: Commit** the schema/rendering slice.

### Task 2: Pure migration transformations

**Files:**
- Create: `plugins/platejka-core/src/Cli/LegacySectionsMigrationCommand.php`
- Create: `plugins/platejka-core/tests/LegacySectionsMigrationCommandTest.php`

**Interfaces:**
- Produces: public pure helpers for normalizing raw ACF values, mapping home shipments, mapping internal shipments, mapping cases, inserting/reordering rows, detecting meaningful overrides, and returning conflict metadata.
- Consumes: Task 1 field names and clone wrapper shapes.

- [ ] **Step 1: Write failing PHPUnit tests** for every mapping, five-star injection, preserved image/tags, blank internal tags/links, title-only cases, canonical order, conflict preservation, and idempotent target generation.
- [ ] **Step 2: Run the focused PHPUnit test**.
  - Run: `vendor\bin\phpunit --configuration phpunit.xml.dist plugins\platejka-core\tests\LegacySectionsMigrationCommandTest.php`
  - Expected: failure because the command/helpers do not exist.
- [ ] **Step 3: Implement the minimal pure helpers** in `LegacySectionsMigrationCommand`.
- [ ] **Step 4: Re-run the focused PHPUnit test**.
  - Expected: all migration transformation tests pass.
- [ ] **Step 5: Commit** the transformation slice.

### Task 3: Preview/apply orchestration and legacy field visibility

**Files:**
- Modify: `plugins/platejka-core/src/Cli/LegacySectionsMigrationCommand.php`
- Create: `plugins/platejka-core/src/Acf/LegacyFieldVisibility.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Modify: `plugins/platejka-core/tests/LegacySectionsMigrationCommandTest.php`
- Modify: `README.md`

**Interfaces:**
- Produces: `wp platejka section-builder migrate-legacy-sections [--apply]`; verified version marker; post-marker ACF UI hiding.
- Consumes: Task 2 transformations and Task 1 schema keys.

- [ ] **Step 1: Write failing tests** for candidate eligibility, default-template filtering, conflict reporting, planned writes, verification-before-marker behavior, and the exact legacy field-key denylist.
- [ ] **Step 2: Run the focused PHPUnit test**.
  - Expected: failure for missing orchestration and visibility behavior.
- [ ] **Step 3: Implement preview/apply orchestration**, command registration, verification, version marker, and `acf/prepare_field` visibility filters.
- [ ] **Step 4: Document the command, mappings, safety rules, and rollback behavior** in README.
- [ ] **Step 5: Run focused and full automated suites**.
  - Run: `vendor\bin\phpunit --configuration phpunit.xml.dist`
  - Run: `composer lint`
  - Expected: zero failures and zero lint errors.
- [ ] **Step 6: Commit** the orchestration slice.

### Task 4: Live read-only verification and dry-run

**Files:**
- No product files expected.

**Interfaces:**
- Produces: a saved dry-run report with totals, per-page actions, conflicts, and exact planned writes for user review.
- Consumes: Task 3 registered command running against the local Studio site.

- [ ] **Step 1: Make the worktree plugin code available to the Studio runtime without changing database content**, using an explicit reviewed path strategy.
- [ ] **Step 2: Run the ACF environment and schema checks** against Studio.
  - Expected: ACF Pro 6.2.6.1 remains active and all schema contracts pass.
- [ ] **Step 3: Run `migrate-legacy-sections` without `--apply`** and save its complete JSON report in the plan workspace.
  - Expected: `mode = dry-run`, `writes.performed = false`, no database mutation.
- [ ] **Step 4: Review report invariants**: page 24 global updates, default-template-only services, all eligible cases, canonical order, conflicts preserved, no source deletion.
- [ ] **Step 5: Present totals and conflicts to the user and stop for explicit database-write confirmation.**
