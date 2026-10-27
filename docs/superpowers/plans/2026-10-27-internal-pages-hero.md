# Internal Pages Hero Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Figma-based internal-page hero while retaining existing main and China hero variants and page content.

**Architecture:** Keep `hero` and its ACF keys as China. Introduce `hero-pages` with a separate ACF layout/default group and template. Migrate non-China builder rows with a preview-first idempotent command, copying resolved text and trust data while clearing the new list.

**Tech Stack:** WordPress, PHP, ACF Pro, CSS, JavaScript.

**Spec:** `docs/superpowers/specs/2026-10-27-internal-pages-hero-design.md`

## Global Constraints

- Do not regress `hero-main` or `hero`; retain existing user edits.
- China page IDs: `1873`, `2109`, `2075`.
- New WYSIWYG list starts empty. Replace background and blur on migrated pages.
- Preview and verify ACF writes before applying.

## Review Focus

- Pages with custom local fields retain rendered text after migration.
- Pages inheriting China defaults retain text and trust values after migration.
- China IDs retain layout and appearance.
- Multiple hero rows or malformed ACF rows are reported as conflicts.
- Re-running migration performs no writes.

---

### Task 1: New ACF schema and defaults

**Files:** `plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json`, `plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json`, `plugins/platejka-core/data/default-page-defaults-v1.json`, schema tests.

- [ ] Add `hero-pages` option group and page layout with a WYSIWYG list field.
- [ ] Rename current `hero` labels to China while preserving keys.
- [ ] Add blank common copy, new image paths, and existing trust data defaults.
- [ ] Run schema tests.

### Task 2: Template and scoped styles

**Files:** `themes/platejka_rework/sections/hero/hero-pages.php`, `themes/platejka_rework/sections/hero/hero-pages.css`, `themes/platejka_rework/inc/assets.php`, `themes/platejka_rework/inc/section-builder.php`, `themes/platejka_rework/inc/section-content.php`.

- [ ] Register slug/template and route assets without altering existing hero markup or CSS.
- [ ] Implement Figma content, images, icons, WYSIWYG list, info, and existing trust/calculator behavior.
- [ ] Verify PHP syntax and rendering.

### Task 3: Safe content migration

**Files:** new command under `plugins/platejka-core/src/Cli/`, CLI registration, migration tests.

- [ ] Build dry-run report for all `hero` rows excluding China IDs; include old and new resolved values and backup.
- [ ] Apply only after preview, preserving page content, section order, and original source data in backup.
- [ ] Verify results and idempotency with tests.

### Task 4: Visual and functional verification

- [ ] Compare new hero with Figma at desktop and mobile widths.
- [ ] Check calculator, image files, ACF editor, and representative China and non-China pages.
- [ ] Review diff for changes to old hero variants.
