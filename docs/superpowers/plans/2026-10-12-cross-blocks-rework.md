# Cross-blocks Rework Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move cross-block controls into the existing page-section builder, restore the source DOM contract for newly transferred blocks, and safely synchronize missing local associations/content from production.

**Architecture:** `platejka_sections` owns placement and visibility, while `skvoznye_bloki` remains the content source. Dedicated builder rows resolve one legacy layout at runtime; agreed reusable rows enrich current sections. The code change and database synchronization are separate deliverables, with the latter gated by an exact dry-run and explicit approval.

**Tech Stack:** WordPress 7.0.2, PHP 8.4, ACF Pro 6.2.6.1, Studio WP-CLI, theme PHP/CSS.

**Spec:** `docs/superpowers/specs/2026-10-12-cross-blocks-rework-design.md`

## Global Constraints

- Add cross-block choices only to `field_platejka_sections_v1`.
- Do not alter or expose `group_67923f17e9f73` through target-theme filters.
- Preserve source DOM/classes/data attributes for all seven dedicated templates.
- Do not register or enqueue exchange/transfer calculator JavaScript.
- Keep cross-block content in `skvoznye_bloki`; builder rows control placement/visibility.
- Exclude `transfer_calculator`, `work`, legacy `reviews`, and `tariff`.
- Perform no database write before an exact dry-run, backup plan, and explicit confirmation.

## Review Focus

- A builder row whose legacy source row is missing renders nothing and produces no warning.
- Reused blocks appear once even when both builder and legacy sources exist.
- Exchange controls retain their exact structural hooks without loading behavior JavaScript.
- Existing builder rows and unrelated page metadata survive synchronization byte-for-byte.
- Re-running synchronization makes no further changes.

---

### Task 1: Correct ACF ownership

**Files:**
- Modify: `themes/platejka_rework/inc/cross-block-schema.php`
- Modify: `themes/platejka_rework/tests/cross-block-schema.php`
- Modify: `plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json`

**Interfaces:**
- Produces builder layouts `cross-exchange`, `cross-company-metrics`, `cross-external-ratings`, `cross-key-facts`, `cross-transfer-goals`, `cross-swift-problems`, and `cross-audience`.
- Each produces `enabled: bool` and `anchor: string` only.

- [ ] Write tests asserting the seven layouts are absent from `field_67924d166072f` extensions and present in `field_platejka_sections_v1` with `Кросс-блок — ` labels.
- [ ] Run the schema test and observe the expected failure.
- [ ] Replace the legacy-group extension with page-builder definitions and remove the page-location filter.
- [ ] Run the schema test and observe success.

### Task 2: Builder-driven source resolution and composition

**Files:**
- Modify: `themes/platejka_rework/inc/cross-blocks.php`
- Modify: `themes/platejka_rework/inc/section-builder.php`
- Modify: `themes/platejka_rework/inc/section-content.php`
- Modify: `themes/platejka_rework/tests/cross-block-composition.php`
- Modify: `themes/platejka_rework/tests/cross-block-adapters.php`

**Interfaces:**
- Produces `platejka_get_cross_block_row(int $post_id, string $layout): ?array`.
- Produces mapping from builder slug to legacy layout.
- Existing reusable sections consume the same lookup without automatic injection of rows absent from the builder.

- [ ] Write failing tests for builder order, missing sources, disabled sources, reusable-section enrichment and duplicate prevention.
- [ ] Run focused tests and observe the expected failures.
- [ ] Make the builder the sole composition authority and resolve content from matching legacy rows.
- [ ] Run focused tests and observe success.

### Task 3: Restore source template contracts

**Files:**
- Replace: `themes/platejka_rework/sections/cross-blocks/cross-blocks.php`
- Modify: `themes/platejka_rework/sections/cross-blocks/cross-blocks.css`
- Modify: `themes/platejka_rework/tests/cross-block-rendering.php`
- Modify: `themes/platejka_rework/tests/cross-block-runtime.php`

**Interfaces:**
- Consumes `$legacy_layout`, `$section_data`, `$section_instance`, `$section_anchor`.
- Emits source classes `cb-exchange`, `cb-metrics`, `cb-ratings`, `cb-facts`, and `cb-card-section--<layout>`.

- [ ] Write failing structural assertions for every dedicated template, including Buy/Sell buttons, currencies, range, prompt, total and CTA in `wtt`.
- [ ] Assert no cross-block calculator JavaScript is registered or enqueued, then observe the expected structural failures.
- [ ] Port the source PHP structures with current-theme escaping/helpers and no behavior script.
- [ ] Adapt scoped CSS without changing the DOM contract.
- [ ] Run rendering/runtime tests and observe success.

### Task 4: Production/local synchronization manifest

**Files:**
- Create: `themes/platejka_rework/tools/cross-block-sync.php`
- Create: `themes/platejka_rework/tests/cross-block-sync.php`

**Interfaces:**
- `--dry-run` outputs page slug, current builder layouts, proposed builder layouts, resolvable source rows and unresolved values.
- `--apply` requires a backup path and writes only the approved post IDs/fields.

- [ ] Write failing tests for manifest parsing, mapping, preservation of unrelated rows and idempotence.
- [ ] Implement a read-only production HTML manifest and local diff mode first.
- [ ] Run `--dry-run`, save its output, and present the exact proposed database changes to the user.
- [ ] Stop for explicit approval before any `--apply` execution.

### Task 5: Approved local synchronization and verification

**Files:**
- Modify only the local WordPress database after approval; no additional schema changes unless a failing test proves they are needed.

- [ ] Back up affected postmeta before applying the approved manifest.
- [ ] Apply the manifest once and verify the second dry-run reports zero changes.
- [ ] Run all cross-block tests, existing theme tests, PHP syntax checks, WPCS where available, and `git diff --check`.
- [ ] Fetch `test-cross-blocks` and the eight rare pages locally; assert expected unique sections, ordering, CSS presence and absence of calculator JS.
- [ ] Review the final code diff and report database backup/recovery details.
