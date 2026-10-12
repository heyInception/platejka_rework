# Cross-blocks Rework Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Render the approved legacy `skvoznye_bloki` layouts through the active `platejka_rework` section system without migrating postmeta, duplicating existing sections, or attaching calculator JavaScript.

**Architecture:** Add a theme-owned cross-block integration layer that extends the existing ACF field at load time, resolves visible legacy rows, maps them to current or dedicated section slugs, and composes them into the existing builder array before `faq`/`call`. Existing sections receive adapted legacy data through their normal resolver; missing visual types use one shared, scoped cross-block renderer and stylesheet.

**Tech Stack:** WordPress 7.0.2, PHP 8.4, ACF Pro 6.2.6.1, theme PHP templates, scoped CSS, Studio CLI/WP-CLI tests.

**Spec:** `docs/superpowers/specs/2026-10-12-cross-blocks-rework-design.md`

## Global Constraints

- Modify only `themes/platejka_rework` and its tests; do not modify the inactive `platejka-pagespeed` theme or production postmeta.
- Preserve `skvoznye_bloki`, field key `field_67924d166072f`, group key `group_67923f17e9f73`, stored layout names, row order, and current ACF location rules.
- Exclude `transfer_calculator`, `work`, legacy `reviews`, and `tariff` from the target integration.
- Do not enqueue any exchange-calculator or transfer-calculator JavaScript.
- Reused builder sections keep their existing position and assets; injected sections appear before the first `faq` or `call`, otherwise at the end.
- Prefix included ACF layout labels with `Кросс-блок — `.
- All output must use appropriate WordPress escaping and remain safe when ACF or row data is missing.

## Review Focus

- A page with `destinations` in both builders must render exactly one destinations section and use non-empty cross-block fields as overrides.
- An explicitly disabled cross-block must not inject or override anything, while an independently enabled builder section remains visible.
- Multiple injected cross-blocks must retain their ACF order and be inserted as one ordered group before `faq`/`call`.
- Empty/malformed legacy repeaters must not warn or replace usable current-theme defaults.
- Exchange markup must remain static and accessible without loading or advertising unavailable calculator behavior.

---

### Task 1: Cross-block row resolution and page composition

**Files:**
- Create: `themes/platejka_rework/inc/cross-blocks.php`
- Create: `themes/platejka_rework/tests/cross-block-composition.php`
- Modify: `themes/platejka_rework/functions.php`
- Modify: `themes/platejka_rework/page.php`
- Modify: `themes/platejka_rework/page-home.php`
- Modify: `themes/platejka_rework/page-services.php`
- Modify: `themes/platejka_rework/page-about.php`

**Interfaces:**
- Produces: `platejka_cross_block_map(): array<string,array{slug:string,adapter:string}>`.
- Produces: `platejka_cross_block_is_enabled(array $row): bool`.
- Produces: `platejka_get_cross_block_rows(int $post_id): array<int,array<string,mixed>>`.
- Produces: `platejka_compose_cross_blocks(array $sections, array $rows, int $post_id): array<int,mixed>`.
- Consumes: `platejka_get_page_sections()`, `platejka_use_sections()`, and existing normalized section configuration.

- [ ] **Step 1: Write the failing composition test**

Create a WP-CLI eval-file test asserting:

- the allowlist contains the 13 included legacy layouts and none of the four excluded layouts;
- missing/unknown rows are ignored;
- `show_block=0` and `vklyuchit_blok=0` rows are ignored;
- a mapped builder slug already present receives cross-block metadata instead of a duplicate section;
- new rows are inserted in ACF order immediately before `faq` or `call`;
- when neither boundary exists, rows append;
- two distinct card-grid layouts remain distinct sections.

- [ ] **Step 2: Run the test and verify RED**

Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/themes/platejka_rework/tests/cross-block-composition.php`

Expected: failure because the `platejka_cross_block_*` functions do not exist.

- [ ] **Step 3: Implement minimal pure composition helpers**

Implement the interfaces above in `inc/cross-blocks.php`. Store the original row and `legacy_layout` in section config, create deterministic instances `cross-<post>-<row>-<layout>`, and mark existing builder configs with cross-block data rather than adding duplicates.

- [ ] **Step 4: Load and apply composition in every page template**

Require `inc/cross-blocks.php` after `section-content.php`. In each page template, compose cross-blocks before calling `platejka_use_sections()`. Preserve each template's current fallback and special composition behavior.

- [ ] **Step 5: Run the focused test and verify GREEN**

Run the Step 2 command.

Expected: `Success: Cross-block composition checks passed.`

- [ ] **Step 6: Commit**

```bash
git add themes/platejka_rework/inc/cross-blocks.php themes/platejka_rework/tests/cross-block-composition.php themes/platejka_rework/functions.php themes/platejka_rework/page.php themes/platejka_rework/page-home.php themes/platejka_rework/page-services.php themes/platejka_rework/page-about.php
git commit -m "feat: compose legacy cross blocks"
```

### Task 2: ACF schema extension and editor labels

**Files:**
- Create: `themes/platejka_rework/inc/cross-block-schema.php`
- Create: `themes/platejka_rework/tests/cross-block-schema.php`
- Modify: `themes/platejka_rework/functions.php`

**Interfaces:**
- Produces: `platejka_cross_block_layout_definitions(): array<int,array<string,mixed>>` for included layouts not fully defined by the database JSON.
- Produces: `platejka_extend_cross_blocks_field(array $field): array` registered on `acf/load_field/key=field_67924d166072f`.
- Consumes: the included layout allowlist from Task 1.

- [ ] **Step 1: Write the failing schema test**

Create a WP-CLI eval-file test that loads `acf_get_field('field_67924d166072f')` and asserts:

- included existing layouts `destinations` and `wtt` are relabelled with `Кросс-блок — `;
- required new layouts are present with their original technical names and field structures;
- `work`, `reviews`, `tariff`, and `transfer_calculator` are not offered as target choices;
- the group location equals its pre-change two rule groups;
- no options page/default seeding hook is registered.

- [ ] **Step 2: Run the test and verify RED**

Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/themes/platejka_rework/tests/cross-block-schema.php`

Expected: failure because labels/layout definitions are unchanged.

- [ ] **Step 3: Implement code-only schema extension**

Port only the field/layout builders required by the approved layouts from the source schema. Preserve source field keys and options where stored postmeta depends on them. Filter out excluded layout choices in the loaded editor field without deleting stored data, and do not alter the field group location.

- [ ] **Step 4: Load the schema before runtime integration**

Require `inc/cross-block-schema.php` from `functions.php` before `inc/cross-blocks.php` so ACF resolves legacy rows against the complete schema.

- [ ] **Step 5: Run the focused test and verify GREEN**

Run the Step 2 command.

Expected: `Success: Cross-block schema checks passed.`

- [ ] **Step 6: Commit**

```bash
git add themes/platejka_rework/inc/cross-block-schema.php themes/platejka_rework/tests/cross-block-schema.php themes/platejka_rework/functions.php
git commit -m "feat: expose approved cross block fields"
```

### Task 3: Legacy data adapters for current sections

**Files:**
- Modify: `themes/platejka_rework/inc/cross-blocks.php`
- Modify: `themes/platejka_rework/inc/section-content.php`
- Create: `themes/platejka_rework/tests/cross-block-adapters.php`

**Interfaces:**
- Produces: `platejka_adapt_cross_block_data(string $layout, array $row): array<string,mixed>`.
- Produces mapping adapters for `destinations`, `problems_solved`, `client_reviews`, `documents`, `guarantees`, and `data_table`.
- Consumes: `platejka_merge_section_data()` for non-empty legacy override/current-default fallback behavior.

- [ ] **Step 1: Write failing adapter tests**

Assert real mapped output for each reused section, including:

- destinations title/subtitle/items map to target title/description/items;
- problem card `icon/title/text` maps to the current problems item shape without destroying default labels;
- document file arrays/IDs map to the target file field and description/title fields;
- reviews, guarantees, and table preserve item order and content;
- empty scalar/collection values do not replace current defaults;
- malformed rows return arrays without warnings.

- [ ] **Step 2: Run the test and verify RED**

Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/themes/platejka_rework/tests/cross-block-adapters.php`

Expected: failure because adapters are missing.

- [ ] **Step 3: Implement minimal adapters and resolver integration**

Resolve the normal builder/default section data first, then merge the adapted legacy row over it so every non-empty cross-block value has final priority. Never write the adapted array back to ACF.

- [ ] **Step 4: Run the focused test and verify GREEN**

Run the Step 2 command.

Expected: `Success: Cross-block adapter checks passed.`

- [ ] **Step 5: Commit**

```bash
git add themes/platejka_rework/inc/cross-blocks.php themes/platejka_rework/inc/section-content.php themes/platejka_rework/tests/cross-block-adapters.php
git commit -m "feat: adapt cross block content"
```

### Task 4: New cross-block renderer and scoped design

**Files:**
- Create: `themes/platejka_rework/sections/cross-blocks/cross-blocks.php`
- Create: `themes/platejka_rework/sections/cross-blocks/cross-blocks.css`
- Create: `themes/platejka_rework/tests/cross-block-rendering.php`
- Modify: `themes/platejka_rework/inc/assets.php`
- Modify: `themes/platejka_rework/inc/section-builder.php`
- Modify: `themes/platejka_rework/inc/cross-blocks.php`

**Interfaces:**
- Consumes dedicated section slugs for `wtt`, `company_metrics`, `external_ratings`, `key_facts`, `transfer_goals`, `swift_problems`, and `audience`.
- Produces one alias-resolved renderer directory `cross-blocks` with no JavaScript file.
- Produces semantic static markup selected by `$config['legacy_layout']` and data from `$section_data`.

- [ ] **Step 1: Write the failing rendering and asset test**

Create a WP-CLI eval-file test that renders representative rows and asserts:

- each new layout emits its expected heading, items, CTA, and unique section ID;
- incomplete source rows are safely omitted according to source semantics;
- exchange renders server values and a Telegram CTA but has no `data-cb-exchange-calculator`, range input, buy/sell buttons, or enqueued calculator script;
- all dedicated slugs normalize to the shared `cross-blocks` directory;
- the scoped stylesheet is enqueued once only when a new cross-block is present;
- `platejka-pagespeed/js/cross-blocks.js` and transfer-calculator assets are never enqueued.

- [ ] **Step 2: Run the test and verify RED**

Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/themes/platejka_rework/tests/cross-block-rendering.php`

Expected: failure because the renderer and aliases do not exist.

- [ ] **Step 3: Implement the shared PHP renderer**

Render the seven approved missing visual types through a layout switch with shared helpers for CTA, media, stars, and cards. Preserve semantic source content and escape all dynamic values.

- [ ] **Step 4: Implement the adapted responsive CSS**

Use current `platejka_rework` container widths, typography, border radii, green palette, buttons, spacing, mobile breakpoints, focus-visible treatment, and reduced-motion rules. Do not copy the legacy stylesheet wholesale.

- [ ] **Step 5: Register aliases and conditional assets**

Add dedicated slugs to the builder registry and normalize them to `sections/cross-blocks/cross-blocks.php`; asset de-duplication must enqueue `cross-blocks.css` once and find no JavaScript file.

- [ ] **Step 6: Run the focused test and verify GREEN**

Run the Step 2 command.

Expected: `Success: Cross-block rendering checks passed.`

- [ ] **Step 7: Commit**

```bash
git add themes/platejka_rework/sections/cross-blocks themes/platejka_rework/tests/cross-block-rendering.php themes/platejka_rework/inc/assets.php themes/platejka_rework/inc/section-builder.php themes/platejka_rework/inc/cross-blocks.php
git commit -m "feat: render adapted cross block sections"
```

### Task 5: Production-page regression and full verification

**Files:**
- Create: `themes/platejka_rework/tests/cross-block-runtime.php`
- Modify only if a failing regression test identifies an integration defect in files from Tasks 1–4.

**Interfaces:**
- Consumes all prior task interfaces against live page ID `2082` discovered by slug rather than hard-coding it in production code.
- Produces no production API.

- [ ] **Step 1: Write the failing runtime regression test**

Resolve `platezhi-za-graniczu-dlya-yuridicheskih-licz`, render its composed sections, and assert:

- one `.destinations` section;
- one static exchange cross-block;
- one `.problems` section using the stored `problems_solved` content;
- exchange and problems occur before FAQ/call;
- no transfer calculator, legacy work, legacy reviews, or tariff output;
- no PHP warnings/notices are produced.

- [ ] **Step 2: Run the runtime test and verify RED or regression sensitivity**

Run: `studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/themes/platejka_rework/tests/cross-block-runtime.php`

Expected before final fixes: at least one integration assertion fails; if it passes immediately, deliberately disable composition locally, confirm the test fails, then restore and continue.

- [ ] **Step 3: Make the minimal integration fixes**

Change only the owning implementation from Tasks 1–4. Do not add page-ID conditionals or database writes.

- [ ] **Step 4: Run all cross-block tests**

Run each new eval-file test from Tasks 1–5.

Expected: five success messages with no warnings.

- [ ] **Step 5: Run existing theme verification**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/themes/platejka_rework/tests/calculator-flag-urls.php
Get-ChildItem themes\platejka_rework -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
composer --working-dir=themes/platejka_rework lint:php
composer --working-dir=themes/platejka_rework lint:wpcs
```

Expected: calculator regression passes, all PHP files report no syntax errors, and available Composer lint commands pass. If bundled dependencies are absent, report that limitation and retain the direct PHP/Studio checks.

- [ ] **Step 6: Inspect the live page response**

Fetch `http://localhost:8883/platezhi-za-graniczu-dlya-yuridicheskih-licz/` and verify HTTP 200, unique section counts, ordering, stylesheet presence, and absence of cross-block calculator JavaScript.

- [ ] **Step 7: Review the final diff**

Run: `git diff --check` and `git status --short`.

Expected: no whitespace errors and only planned files changed.

- [ ] **Step 8: Commit**

```bash
git add themes/platejka_rework/tests/cross-block-runtime.php themes/platejka_rework
git commit -m "test: verify cross blocks on migrated pages"
```
