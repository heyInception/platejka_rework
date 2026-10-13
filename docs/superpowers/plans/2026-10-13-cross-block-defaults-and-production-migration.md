# Cross-block Defaults and Production Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the approved cross-blocks editable through shared section defaults and per-page builder overrides, then migrate their production defaults, page content, and page associations from the supplied SQL dump.

**Architecture:** The canonical ACF defaults group owns shared content and each builder layout clones its corresponding defaults group for local overrides. Runtime rendering resolves builder overrides over shared defaults without reading `skvoznye_bloki`; a new WP-CLI migration parses the SQL dump read-only, produces an approved manifest, and applies only fingerprinted, conflict-free changes.

**Tech Stack:** WordPress 7, PHP 8.1+, ACF Pro, ACF Local JSON, WP-CLI, PHPUnit 10, Studio CLI.

**Spec:** `docs/superpowers/specs/2026-10-13-cross-block-defaults-and-production-migration-design.md`

## Global Constraints

- Parse `C:\Users\Inception\Studio\platejka\wp-content\a0856023_app_wordpress_20.sql` as text; never import or execute the dump.
- Match production and local published pages by slug, never by production post ID.
- Do not modify, add, or enable Destinations, Client Reviews/Reviews, Documents, `transfer_calculator`, `work`, or `tariff`.
- Preserve legacy `skvoznye_bloki` options and postmeta for rollback, but remove them from runtime rendering and editing.
- Preserve non-empty local overrides; report conflicts instead of overwriting them.
- Keep every migrated target section before FAQ and deduplicate target layouts by retaining the first suitable row.
- A non-empty local repeater replaces its shared repeater wholesale; an empty local repeater inherits the shared collection.
- Seed shared defaults only when their canonical group is empty.
- Do not connect exchange-calculator JavaScript.
- Do not modify `test-cross-blocks`.
- Any database apply requires review of the exact dry-run manifest and fresh explicit user confirmation.

## Review Focus

- MySQL dump values containing escaped quotes, backslashes, serialized arrays, UTF-8 text, or embedded newlines must parse without truncation; Task 3 pins these cases.
- Existing non-empty local values that differ from production must become reported conflicts and remain byte-for-byte unchanged; Task 4 pins this behavior.
- Disabled, unsupported, excluded, or incomplete legacy rows must not create or enable builder sections; Tasks 3 and 4 pin all four classes.
- Production attachment IDs that do not identify the same local attachment must not be copied blindly; Task 4 reports and omits unresolved media.
- Duplicate target rows, malformed builders, and targets after FAQ must be handled without losing unrelated rows or enabling a broken page; Task 4 pins these cases.
- The exchange calculator must preserve the source block's complete static DOM contract, including buy/sell controls, range input, labels, amount fields, and result containers; Tasks 2 and 7 pin this structure while keeping its JavaScript disconnected.
- Builder labels and defaults-page tabs must use the approved `Кросс-блок — …` wording so editors can distinguish migrated cross-blocks; Task 1 pins the exact labels.

---

### Task 1: Register shared cross-block defaults and editable builder overrides

**Files:**
- Modify: `themes/platejka_rework/inc/cross-block-schema.php`
- Modify: `plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json`
- Modify: `plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json`
- Modify: `themes/platejka_rework/tests/cross-block-schema.php`
- Modify: `tests/integration/acf-section-default-tabs.php`
- Modify: `tests/integration/acf-section-builder-schema.php`

**Interfaces:**
- Produces: `platejka_cross_block_default_groups(): array<string,array<string,mixed>>`, keyed by builder slug.
- Produces: `platejka_cross_block_default_field_key(string $slug): string` returning keys such as `field_platejka_defaults_cross_swift_problems_v1`.
- Produces: builder layouts whose `overrides` clone points at the matching canonical default group.
- Consumes: the existing field helpers in `inc/cross-block-schema.php` and the established `enabled`/`anchor`/`overrides` layout contract.

- [ ] **Step 1: Extend the schema tests with failing assertions**

Assert that all seven canonical default groups exist on `group_platejka_section_defaults_v1`, their names are `cross_exchange`, `cross_company_metrics`, `cross_external_ratings`, `cross_key_facts`, `cross_transfer_goals`, `cross_swift_problems`, and `cross_audience`, and every matching builder layout has exactly one clone named `overrides` pointing at its default field key. Assert the exact approved `Кросс-блок — …` builder labels and defaults-page tab labels. Assert that the default groups contain content fields but no `show_block` visibility field.

- [ ] **Step 2: Run schema tests and confirm they fail**

Run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/themes/platejka_rework/tests/cross-block-schema.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/tests/integration/acf-section-default-tabs.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/tests/integration/acf-section-builder-schema.php
```

Expected: FAIL because the seven defaults groups and builder clone fields are absent.

- [ ] **Step 3: Define the seven canonical content groups**

Implement `platejka_cross_block_default_groups()` and `platejka_cross_block_default_field_key()` in `inc/cross-block-schema.php`. Reuse the approved source field shapes but omit legacy visibility controls. Preserve the exchange structure fields, metric constraints, four-item facts constraint, ratings fields, card-grid fields, links, and media return formats required by the existing renderer.

- [ ] **Step 4: Add clone overrides to the builder definitions**

Change `platejka_cross_block_builder_layout(string $slug, string $label, string $default_field_key): array` so each layout includes an `overrides` clone with `display=group`, `prefix_name=0`, and the matching default field key. Update the Local JSON definitions to match the runtime definitions exactly.

- [ ] **Step 5: Add the defaults tabs to canonical Local JSON**

Append seven left-side tabs and their groups to `group_platejka_section_defaults_v1.json`. Keep the existing group key, options-page location, and existing sections unchanged.

- [ ] **Step 6: Run schema tests and confirm they pass**

Expected: all three commands report success and ACF returns the clone/default contracts through the live API.

- [ ] **Step 7: Commit the schema unit**

```powershell
git add themes/platejka_rework/inc/cross-block-schema.php plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json themes/platejka_rework/tests/cross-block-schema.php tests/integration/acf-section-default-tabs.php tests/integration/acf-section-builder-schema.php
git commit -m "feat: add editable cross-block defaults"
```

### Task 2: Make runtime rendering independent of legacy cross-block fields

**Files:**
- Modify: `themes/platejka_rework/inc/cross-blocks.php`
- Modify: `themes/platejka_rework/inc/section-content.php`
- Modify: `themes/platejka_rework/inc/assets.php`
- Modify: `themes/platejka_rework/page.php`
- Modify: `themes/platejka_rework/page-home.php`
- Modify: `themes/platejka_rework/page-services.php`
- Modify: `themes/platejka_rework/page-about.php`
- Modify: `themes/platejka_rework/sections/cross-blocks/cross-blocks.php`
- Modify: `themes/platejka_rework/tests/cross-block-composition.php`
- Modify: `themes/platejka_rework/tests/cross-block-runtime.php`
- Modify: `themes/platejka_rework/tests/cross-block-rendering.php`

**Interfaces:**
- Consumes: canonical global groups and builder clone values from Task 1 through `platejka_resolve_section_data(array $config, int $post_id): array`.
- Produces: `platejka_cross_block_layout_for_section(string $slug): string` as the sole builder-slug-to-renderer-layout mapping.
- Removes runtime dependence on: `platejka_get_cross_block_rows()`, `platejka_compose_cross_blocks()`, `cross_block_row`, and legacy enrichment configuration.

- [ ] **Step 1: Rewrite runtime tests to express the new contract and fail**

Assert that an enabled dedicated builder row survives without `skvoznye_bloki`, resolves empty local fields from `platejka_section_defaults`, resolves non-empty scalar overrides locally, replaces repeaters wholesale, and renders the expected marker. Snapshot the source exchange-calculator DOM contract and assert the migrated renderer retains its buy/sell controls, range input, labels, amount inputs, and result containers without enqueuing calculator JavaScript. Assert that reusable sections are not enriched from legacy rows and that no page template calls the legacy composer.

- [ ] **Step 2: Run the three cross-block runtime tests and confirm failure**

Run the `cross-block-composition.php`, `cross-block-runtime.php`, and `cross-block-rendering.php` eval-file tests. Expected: FAIL on legacy source dependence.

- [ ] **Step 3: Remove legacy composition from page templates**

Make all four page templates pass the builder result directly to `platejka_use_sections()`. Do not add fallback injection.

- [ ] **Step 4: Resolve dedicated content through the standard section resolver**

Remove the legacy merge branch from `platejka_resolve_section_data()`. Keep the existing scalar and collection inheritance behavior. Ensure a dedicated slug resolves field names such as `cross_swift_problems` from the shared options post and the builder clone.

- [ ] **Step 5: Resolve renderer layout from the section slug**

In `platejka_render_section()`, derive `$legacy_layout` with `platejka_cross_block_layout_for_section($config['slug'])` for the shared cross-block template. Stop propagating `cross_block_row` and `cross_block_adapter` through normalized configs.

- [ ] **Step 6: Remove obsolete runtime lookup/composition functions**

Retain only rendering helpers, the approved slug mapping, and data-shape helpers still needed by the migration. Ensure the external-ratings title uses `($row['title'] ?? '')` so a missing title never raises a notice.

- [ ] **Step 7: Run cross-block and current-theme regression tests**

Run all theme cross-block tests plus `section-builder.php`, `section-content.php`, `section-loader.php`, and `services-page.php`. Expected: zero failures and no `cross-blocks.js` enqueue.

- [ ] **Step 8: Commit the runtime unit**

```powershell
git add themes/platejka_rework
git commit -m "refactor: resolve cross blocks from section defaults"
```

### Task 3: Parse the production SQL dump without executing it

**Files:**
- Create: `plugins/platejka-core/src/Migration/CrossBlockSqlDumpReader.php`
- Create: `plugins/platejka-core/tests/CrossBlockSqlDumpReaderTest.php`
- Create: `tests/fixtures/cross-block-production.sql`

**Interfaces:**
- Produces: `Platejka\Core\Migration\CrossBlockSqlDumpReader::__construct(string $path)`.
- Produces: `read(): array{options:array<string,string>,posts:array<int,array{ID:int,post_name:string,post_status:string,post_type:string}>,postmeta:array<int,array<string,string>>,warnings:array<int,array<string,mixed>>}`.
- The returned snapshot contains only `options_skvoznye_bloki*`, `_options_skvoznye_bloki*`, `skvoznye_bloki*`, `_skvoznye_bloki*`, and the page rows required to map IDs to slugs/status.

- [ ] **Step 1: Add a minimal representative SQL fixture and failing parser tests**

Cover multiline INSERT statements, UTF-8 text, escaped quotes and backslashes, serialized flexible-content parents, duplicate option names, unrelated tables, malformed tuples, and a page whose slug exists but status is not `publish`.

- [ ] **Step 2: Run the focused PHPUnit test and confirm failure**

Run:

```powershell
vendor\bin\phpunit --configuration phpunit.xml.dist plugins/platejka-core/tests/CrossBlockSqlDumpReaderTest.php
```

Expected: FAIL because the reader class does not exist.

- [ ] **Step 3: Implement the streaming dump reader**

Read the dump incrementally, accept only INSERT data for the relevant posts/postmeta/options tables, decode MySQL string escaping without `eval`, and reject an unreadable file with `RuntimeException`. Resolve duplicate option or meta keys deterministically with last tuple wins while recording a warning, matching the effective final value of an ordered dump. Do not load the 56 MB dump into memory as one string.

- [ ] **Step 4: Run parser tests and static syntax checks**

Expected: PHPUnit PASS; `php -l` passes for the reader and test fixture consumer.

- [ ] **Step 5: Commit the parser unit**

```powershell
git add plugins/platejka-core/src/Migration/CrossBlockSqlDumpReader.php plugins/platejka-core/tests/CrossBlockSqlDumpReaderTest.php tests/fixtures/cross-block-production.sql
git commit -m "feat: parse production cross-block SQL"
```

### Task 4: Build a deterministic, conflict-aware migration plan

**Files:**
- Create: `plugins/platejka-core/src/Migration/CrossBlockMigrationPlanner.php`
- Create: `plugins/platejka-core/tests/CrossBlockMigrationPlannerTest.php`

**Interfaces:**
- Consumes: the snapshot returned by `CrossBlockSqlDumpReader::read()`.
- Produces: `extractProduction(array $snapshot): array{defaults:array<string,array<string,mixed>>,pages:array<string,array<string,mixed>>,warnings:array<int,array<string,mixed>>}`.
- Produces: `plan(array $production, array $local_state): array{defaults:array<int,array<string,mixed>>,pages:array<int,array<string,mixed>>,conflicts:array<int,array<string,mixed>>,totals:array<string,int>}`.
- Produces: `mergeBuilderRows(array $rows, array $targets): array{rows:array<int,array<string,mixed>>,added:array<int,string>,enabled:array<int,string>,removed_duplicates:array<int,string>,moved_before_faq:array<int,string>}`.
- Produces: stable manifest fingerprints from the current default group, builder flag, and raw builder rows.

- [ ] **Step 1: Add failing production-extraction tests**

Assert the exact seven-layout map, the three reusable-layout map, and all exclusions. Assert disabled source rows are omitted. Assert SQL option rows become defaults without `show_block`. Assert page 2182-like data produces a SWIFT page row whose empty intro is omitted while its differing title and full three-card list are retained.

- [ ] **Step 2: Add failing builder and conflict tests**

Cover insertion immediately before FAQ, moving an existing target from after FAQ, preserving target rows already before FAQ, removing later duplicates, retaining the first row's anchor and overrides, rejecting an empty/malformed builder, and never enabling excluded layouts.

- [ ] **Step 3: Add failing inheritance and local-edit tests**

Assert equal scalar/repeater values are omitted locally, differing repeaters replace wholesale, non-empty conflicting local overrides remain unchanged and are reported, and missing local attachment identities produce warnings with empty imported media rather than copied production IDs.

- [ ] **Step 4: Run the planner test and confirm failure**

Run the focused PHPUnit class. Expected: FAIL because the planner does not exist.

- [ ] **Step 5: Implement production extraction and value comparison**

Implement the exact maps and comparison rules from the spec. Normalize semantically equivalent scalar types without rewriting HTML strings. Use current canonical defaults for `problems`, `guarantees`, and `table`; use the proposed one-time defaults for the seven dedicated layouts.

- [ ] **Step 6: Implement safe builder planning and fingerprints**

Make row planning idempotent and preserve every unrelated row and subfield exactly. Fingerprints must change when any relevant current value changes.

- [ ] **Step 7: Run planner, parser, and full PHPUnit suites**

Run both focused tests, then `vendor\bin\phpunit --configuration phpunit.xml.dist`. Expected: all tests pass.

- [ ] **Step 8: Commit the planner unit**

```powershell
git add plugins/platejka-core/src/Migration/CrossBlockMigrationPlanner.php plugins/platejka-core/tests/CrossBlockMigrationPlannerTest.php
git commit -m "feat: plan cross-block content migration"
```

### Task 5: Add manifest-gated WP-CLI preview and apply commands

**Files:**
- Create: `plugins/platejka-core/src/Cli/CrossBlockMigrationCommand.php`
- Modify: `plugins/platejka-core/platejka-core.php`
- Create: `plugins/platejka-core/tests/CrossBlockMigrationCommandTest.php`
- Create: `tests/integration/cross-block-migration-dry-run.php`
- Create: `tests/integration/cross-block-migration-applied.php`

**Interfaces:**
- Produces CLI command: `wp platejka section-builder migrate-cross-blocks`.
- Dry-run syntax: `--sql=<absolute.sql> --output=<absolute-manifest.json>`.
- Apply syntax: `--apply --manifest=<approved-manifest.json> --backup=<absolute-backup.json>`.
- Produces: `CrossBlockMigrationCommand::preview(string $sql_path): array`.
- Produces: `CrossBlockMigrationCommand::applyManifest(array $manifest, string $backup_path): array`.
- Consumes: Task 3 reader, Task 4 planner, live ACF values, and raw builder values via field keys.

- [ ] **Step 1: Add failing command tests**

Assert dry-run makes no database writes, rejects a missing/unreadable SQL path, outputs stable JSON, and includes conflicts/missing pages. Assert apply rejects absent backup path, modified fingerprints, slug/ID mismatch, a tampered target, and a manifest containing excluded layouts.

- [ ] **Step 2: Add failing backup/apply tests**

Assert the backup includes affected canonical option values/references, page builder flags, and raw builder postmeta. Assert defaults seed only empty groups, page writes use ACF field keys, null raw leaves are restored, and verification rereads every written target.

- [ ] **Step 3: Run the focused command tests and confirm failure**

Expected: FAIL because the command class and registration do not exist.

- [ ] **Step 4: Implement CLI orchestration and registration**

Require both Migration classes and the command class only under WP-CLI, then register `platejka section-builder migrate-cross-blocks`. Dry-run may write only the requested manifest file. Apply must validate the complete manifest before its first database mutation.

- [ ] **Step 5: Implement recoverable backup and verified writes**

Back up exact option and postmeta records, not only formatted ACF values. Abort before writes on any stale fingerprint. After each write, reread raw ACF values and fail with the page/default identity in the error when verification differs.

- [ ] **Step 6: Run unit and read-only integration tests**

Run the full PHPUnit suite and:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/tests/integration/cross-block-migration-dry-run.php
```

Expected: PASS; the integration test proves the live database hash is unchanged.

- [ ] **Step 7: Commit the CLI unit**

```powershell
git add plugins/platejka-core/src/Cli/CrossBlockMigrationCommand.php plugins/platejka-core/platejka-core.php plugins/platejka-core/tests/CrossBlockMigrationCommandTest.php tests/integration/cross-block-migration-dry-run.php tests/integration/cross-block-migration-applied.php
git commit -m "feat: add cross-block migration command"
```

### Task 6: Generate and review the exact local dry-run

**Files:**
- Create outside Git tracking: `database/backups/cross-block-defaults-migration-manifest-20261013.json`
- Create after approval: `database/backups/cross-block-defaults-before-apply-20261013.json`

**Interfaces:**
- Consumes: the CLI command from Task 5 and the supplied SQL dump.
- Produces: an immutable manifest for user review and, only after approval, a recoverable database backup.

- [ ] **Step 1: Record the clean pre-run state**

Capture `git status`, the current relevant options/postmeta hash, current global cross-block defaults, and current builder layouts for every locally matched production slug.

- [ ] **Step 2: Run the exact dry-run**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-cross-blocks --sql=C:\Users\Inception\Studio\platejka\wp-content\a0856023_app_wordpress_20.sql --output=C:\Users\Inception\Studio\platejka\wp-content\database\backups\cross-block-defaults-migration-manifest-20261013.json
```

Expected: success, no database hash change, and a manifest listing defaults, matched pages, changes, skips, and conflicts.

- [ ] **Step 3: Verify the Europe-page preview explicitly**

Assert the manifest excludes Destinations and Reviews, includes one enabled SWIFT row before FAQ, inherits the shared SWIFT intro, imports the three exact production cards, and plans local Guarantees and Problems differences without overwriting current local edits.

- [ ] **Step 4: Present the complete write summary and stop for confirmation**

Report exact counts and every conflict/missing page. Do not run apply until the user explicitly approves this manifest in the active session.

- [ ] **Step 5: Apply only after explicit confirmation**

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-cross-blocks --apply --manifest=C:\Users\Inception\Studio\platejka\wp-content\database\backups\cross-block-defaults-migration-manifest-20261013.json --backup=C:\Users\Inception\Studio\platejka\wp-content\database\backups\cross-block-defaults-before-apply-20261013.json
```

Expected: verified success and a non-empty recoverable backup created before the first write.

### Task 7: Verify editor behavior, real page HTML, and idempotence

**Files:**
- Modify if necessary: `tests/integration/cross-block-migration-applied.php`
- Modify if necessary: `themes/platejka_rework/tests/cross-block-runtime.php`

**Interfaces:**
- Consumes: applied database state from Task 6.
- Produces: final evidence that ACF editing, inheritance, associations, placement, and frontend output all match the spec.

- [ ] **Step 1: Verify live ACF defaults and page overrides**

Run the applied integration test. Assert all seven global groups are populated, each affected page has one enabled target row before FAQ, exclusions are unchanged, and legacy `skvoznye_bloki` hashes match the pre-run snapshot.

- [ ] **Step 2: Verify the actual Europe-page HTML**

Fetch `http://localhost:8883/perevody-v-evropu-dlya-yurlicz/` and assert the SWIFT section contains the inherited intro and the three production card titles, Guarantees and Problems contain their production overrides, and no migrated section begins after the FAQ marker.

- [ ] **Step 3: Verify all changed pages and asset behavior**

Fetch every changed local page from the manifest. Assert its expected section markers and page-specific text, HTTP 200, no PHP warnings in output/logs, and cross-block CSS present where needed. On pages containing exchange, compare the required DOM controls against the source-contract fixture and assert buy/sell buttons, range input, labels, amount fields, and result containers are present while no exchange-calculator JavaScript asset is enqueued.

- [ ] **Step 4: Prove idempotence**

Run a second dry-run against the same SQL. Expected: zero default writes, zero page writes, zero duplicate removals, and only previously reported unresolved items/conflicts.

- [ ] **Step 5: Run the complete relevant verification suite**

Run full PHPUnit, PHP syntax/lint, all cross-block tests, the current section-builder/content/loader/services tests, `git diff --check`, and `git status --short --branch`. If WPCS remains unavailable, report that limitation explicitly rather than claiming it passed.

- [ ] **Step 6: Request final code review and address findings**

Review schema fidelity, runtime data flow, SQL parser safety, conflict protection, raw ACF preservation, and real-page evidence. Fix Important findings with a failing test before implementation; record any deliberately deferred Minor finding.

- [ ] **Step 7: Commit final verification adjustments**

```powershell
git add plugins/platejka-core themes/platejka_rework tests
git commit -m "test: verify cross-block defaults migration"
```

