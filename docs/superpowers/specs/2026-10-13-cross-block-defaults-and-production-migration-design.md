# Cross-block defaults and production migration design

## Goal

Move the approved cross-blocks onto the same data model as the existing page-section builder:

- global editable defaults live on `admin.php?page=platejka-section-defaults`;
- page-builder rows control placement and visibility;
- each page can edit local overrides inside its builder row;
- empty local scalar values inherit global defaults;
- non-empty local repeater values replace the global repeater wholesale;
- the supplied production SQL dump is the source for the one-time default seed, page associations, and page-specific content differences.

The legacy `skvoznye_bloki` data remains untouched for rollback but stops being a runtime or editor data source after migration.

## Inputs and constraints

- Production dump: `C:\Users\Inception\Studio\platejka\wp-content\a0856023_app_wordpress_20.sql`.
- The dump must be parsed read-only. It must not be imported over the local WordPress database.
- Only published production pages that also exist locally are eligible.
- Production and local pages are matched by slug, not by numeric post ID.
- Existing local page-builder edits have priority and must not be overwritten automatically.
- Cross-block sections must not be placed after FAQ.
- The exact order before FAQ is not important.
- The exchange calculator retains its source HTML structure, but its JavaScript remains disconnected.
- `test-cross-blocks` remains outside the migration scope.

## Included section types

### New dedicated builder sections

| Legacy layout | Builder slug | Admin label |
|---|---|---|
| `wtt` | `cross-exchange` | Кросс-блок — Обмен валют |
| `company_metrics` | `cross-company-metrics` | Кросс-блок — Показатели компании |
| `external_ratings` | `cross-external-ratings` | Кросс-блок — Рейтинги на внешних площадках |
| `key_facts` | `cross-key-facts` | Кросс-блок — Конкретные цифры и условия |
| `transfer_goals` | `cross-transfer-goals` | Кросс-блок — Частые цели переводов |
| `swift_problems` | `cross-swift-problems` | Кросс-блок — Почему прямой SWIFT не работает |
| `audience` | `cross-audience` | Кросс-блок — Кому подходит |

### Existing builder sections included in content migration

| Legacy layout | Existing builder slug | Rule |
|---|---|---|
| `problems_solved` | `problems` | Enable and migrate values that differ from current global defaults. |
| `guarantees` | `guarantees` | Enable and migrate values that differ from current global defaults. |
| `data_table` | `table` | Enable and migrate values that differ from current global defaults. |

### Explicit exclusions

The migration must neither add, enable, nor change these layouts/sections:

- `destinations` / Доступные направления;
- `client_reviews` and legacy `reviews` / Отзывы клиентов;
- `documents` / Документы;
- `transfer_calculator`;
- `work`;
- `tariff`;
- any unapproved legacy layout.

## ACF architecture

### Global defaults

Add seven groups to the existing `group_platejka_section_defaults_v1` field group. Each group uses the canonical section name derived from the builder slug, for example:

- `cross_exchange`;
- `cross_company_metrics`;
- `cross_external_ratings`;
- `cross_key_facts`;
- `cross_transfer_goals`;
- `cross_swift_problems`;
- `cross_audience`.

Each group contains the editable content fields required by its renderer. The fields must match the source block structure, including repeaters, links, media, numeric values, and calculator labels or static content where applicable.

The defaults appear as dedicated tabs on `platejka-section-defaults`. Initial values come from the corresponding rows of `options_skvoznye_bloki` in the supplied SQL dump. Seeding is one-time and non-destructive: an already populated global group is never overwritten by a deployment or repeated migration.

### Page-builder overrides

Each of the seven dedicated layouts in `field_platejka_sections_v1` contains:

1. `enabled` — section visibility;
2. `anchor` — optional DOM anchor;
3. `overrides` — an ACF Clone field referencing that section's global-default group.

This matches the established builder pattern used by sections such as `problems`.

### Resolution rules

The runtime resolves data in this order:

1. non-empty local builder override;
2. global value in `platejka_section_defaults`;
3. safe renderer fallback where applicable.

Scalar values use the existing empty-value inheritance semantics. A non-empty local repeater replaces the global repeater as one collection; rows are never merged by index. An empty local repeater inherits the complete global collection.

The runtime must not read `skvoznye_bloki` after the migration. The legacy postmeta and options are preserved unchanged for rollback.

## SQL parsing and manifest

The migration tool parses only the required `wp_posts`, `wp_postmeta`, and `wp_options` rows from the SQL dump. It must not execute SQL from the dump.

The dry-run manifest contains:

- global default groups that would be seeded;
- production slug and matching local post ID;
- production layouts found on each page;
- source visibility state (`show_block` or `vklyuchit_blok`);
- builder rows to add, enable, move before FAQ, or deduplicate;
- global-versus-page content comparisons;
- local overrides proposed for import;
- protected local edits that conflict with production data;
- missing local pages;
- invalid, disabled, unsupported, or incomplete source rows;
- pages whose builder cannot safely be enabled.

Only source rows enabled in production are eligible. A layout's presence in the flexible-content parent value is not sufficient when its visibility field is false.

## Page migration rules

1. Match the published production page to a local published page by slug.
2. Ignore excluded layouts before planning any builder or content change.
3. Find or create the target builder row.
4. If duplicates exist, preserve the first suitable row and its local values; remove later duplicates.
5. If the retained row is after FAQ, move it immediately before FAQ. Existing target rows already before FAQ retain their position.
6. Enable the retained target row.
7. Compare production page data with the appropriate global defaults:
   - new dedicated sections compare against the newly seeded cross-block default group;
   - `problems`, `guarantees`, and `table` compare against their current section-default groups.
8. Leave equal scalar values empty locally so they inherit.
9. Copy differing scalar values as local overrides.
10. Leave an equal repeater empty locally; copy a differing repeater wholesale.
11. Never overwrite a non-empty local override with a different production value. Report the conflict instead.
12. Enable the overall page builder only when the page already has a valid non-empty builder composition. Otherwise report a conflict and do not change the page.

## Example: `perevody-v-evropu-dlya-yurlicz`

The production SQL contains five legacy layouts on post ID 2182:

- `swift_problems`;
- `destinations`;
- `reviews`;
- `guarantees`;
- `problems_solved`.

The local page is selected by the slug `perevody-v-evropu-dlya-yurlicz`; production ID 2182 is not relied upon.

`destinations` and `reviews` are excluded and remain unchanged locally.

### Cross-block — SWIFT problems

The builder contains one enabled `cross-swift-problems` row before FAQ.

Global defaults seeded from `options_skvoznye_bloki` include:

- title: `Почему прямой SWIFT может не работать`;
- intro: `Распространённые причины задержки или возврата международного платежа.`;
- three demonstration cards: `Ограничения банка`, `Банки-корреспонденты`, and `Комплаенс и документы`.

Page overrides imported from production include:

- title: `Почему прямой SWIFT не работает`;
- no local intro, so the global intro is inherited;
- a complete local three-card collection:
  1. `Банк отправителя под ограничениями`;
  2. `Комплаенс иностранного банка`;
  3. `Проблемный маршрут платежа`.

### Guarantees

The existing `guarantees` builder row is enabled. Production content differs from current defaults, so local overrides include the title `Гарантии и безопасность перевода`, its production intro, and the complete four-card production collection.

### Problems

The existing `problems` builder row is enabled. Local overrides include the production title `Что можно оплатить` and its complete six-card production collection.

All three sections remain editable in their respective builder rows after migration.

## Safety and application flow

The operation has two explicit phases.

### Dry-run

The dry-run performs no writes and emits a complete JSON manifest plus a human-readable summary. It hashes or otherwise fingerprints the relevant current ACF state so that later edits invalidate the manifest.

### Apply

Apply accepts only an approved dry-run manifest. Before writing, it verifies page identity and all recorded current values. If any relevant value changed, application stops before mutating that page.

Before the first write, the tool stores a recoverable backup of:

- affected global default option values and ACF references;
- page-builder enable flags;
- raw page-builder flexible-content rows and associated subfield meta.

Writes use ACF field keys and raw-safe handling so null and empty values are not normalized unexpectedly. The apply result identifies every successful change and every skipped conflict.

## Verification

Automated coverage must include:

- seven global-default groups are registered on the existing defaults page;
- all seven builder rows expose editable clone overrides;
- scalar inheritance and local override behavior;
- whole-repeater replacement behavior;
- disabled production rows are ignored;
- excluded layouts never modify builder rows or content;
- slug-based matching;
- duplicate removal while preserving the retained row's local edits;
- placement before FAQ;
- local edit conflict protection;
- safe handling of missing pages and invalid builders;
- one-time non-destructive default seeding;
- idempotence: a second dry-run after apply reports zero changes;
- exchange calculator markup remains intact and no calculator JavaScript is enqueued.

Integration verification must read values through the live ACF API and fetch the real HTML of every changed local page. For `perevody-v-evropu-dlya-yurlicz`, verification must assert the inherited intro and the three page-specific SWIFT cards, plus the migrated Guarantees and Problems content. It must also assert that no migrated target section occurs after FAQ.

## Success criteria

- Editors can populate and update global cross-block defaults on `platejka-section-defaults`.
- Editors can override every supported cross-block field inside a page-builder row.
- Production text differences and approved page associations from the supplied SQL are present locally.
- Excluded existing sections remain byte-for-byte and configuration-for-configuration untouched.
- No approved cross-block is silently removed solely because the legacy `skvoznye_bloki` field is absent locally.
- The legacy field is preserved for rollback but is no longer required to render the site.
- Re-running the migration is safe and produces no additional changes.
