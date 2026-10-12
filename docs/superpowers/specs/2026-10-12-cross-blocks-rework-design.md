# Cross-blocks integration for `platejka_rework`

## Goal

Make the selected legacy ACF flexible-content rows from `skvoznye_bloki` render in the active `platejka_rework` theme, preserving their content structure while adapting presentation and lifecycle to the current section system.

## Source and target

- Source implementation: `themes/platejka-pagespeed/template-parts/cross-blocks` and its cross-block helpers.
- Target implementation: active theme `themes/platejka_rework`.
- Source data remains in the existing ACF field `skvoznye_bloki` (`field_67924d166072f`) inside group `group_67923f17e9f73`.
- No production postmeta migration or rewriting is permitted. The target theme reads existing values at runtime.

## Included layouts

The ACF editor labels of included layouts use the prefix `Кросс-блок — ` while technical layout names remain unchanged.

| Legacy layout | Target behavior |
| --- | --- |
| `destinations` | Reuse the current `destinations` section. Do not render a duplicate when it already exists in `platejka_sections`. |
| `wtt` | Add an adapted exchange-calculator section. Render HTML and CSS only; do not attach calculator JavaScript. |
| `company_metrics` | Add an adapted cross-block section. |
| `problems_solved` | Reuse the current `problems` section and map legacy row data to its content schema. |
| `client_reviews` | Reuse the current reviews section without duplicating an independently enabled builder section. |
| `external_ratings` | Add an adapted cross-block section. |
| `documents` | Reuse the current `documents` section and map legacy row data to its content schema. |
| `key_facts` | Add an adapted cross-block section. |
| `transfer_goals` | Add an adapted card-grid cross-block section. |
| `swift_problems` | Add an adapted card-grid cross-block section. |
| `guarantees` | Reuse the current `guarantees` section without duplication. |
| `audience` | Add an adapted card-grid cross-block section. |
| `data_table` | Reuse the current `table` section without duplication. |

The following layouts are excluded from rendering and from new ACF choices supplied by the target integration:

- `transfer_calculator`
- `work`
- legacy `reviews`
- `tariff`

Existing database definitions and stored rows for excluded layouts are not deleted.

## ACF behavior

- Extend the existing flexible-content field in code through ACF load filters. Do not persist field definitions to the database.
- Preserve existing field keys, layout names, stored values, and the field group's current location rules.
- Prefix included layout labels with `Кросс-блок — `, including pre-existing included layouts.
- Do not expose the group on additional page templates.
- An included row is active only when its legacy visibility field (`vklyuchit_blok` or `show_block`) is not explicitly false.
- An inactive cross-block neither injects a section nor supplies data to a builder section.

## Composition and duplicate prevention

The current `platejka_sections` builder remains the primary page-composition system.

1. Resolve active rows from `skvoznye_bloki` for the queried page.
2. Map each included legacy layout to either an existing section slug or a dedicated new cross-block section slug.
3. When an equivalent section is already enabled in `platejka_sections`, keep that section in its existing position and feed it mapped cross-block data.
4. When no equivalent section exists, inject the mapped cross-block immediately before the first enabled `faq` or `call` section.
5. When neither `faq` nor `call` exists, append the mapped cross-block after the regular page sections.
6. Preserve the relative order of injected rows from `skvoznye_bloki`.
7. Never render two instances representing the same cross-block/section mapping.

An independently enabled builder section remains visible when its matching cross-block is absent or explicitly disabled.

## Data resolution

- A non-empty legacy field overrides the corresponding current-theme field.
- Empty, false, null, or empty-array legacy values inherit the current theme's section defaults using the existing section merge semantics.
- Legacy repeater ordering is preserved.
- Data mapping changes array shape only where the current section schema requires it; it must not alter stored ACF data.
- Rendering filters incomplete rows according to the semantic requirements already present in the source templates (for example, card rows need a title or body; company metrics require valid metric entries).
- Output uses WordPress escaping appropriate to each value type.

## Presentation

- Preserve the semantic content structure, editable capabilities, item order, headings, cards, lists, CTA links, ratings, documents, facts, and table content of included blocks.
- HTML wrappers and CSS class names may change to follow `platejka_rework` conventions.
- Reused target sections retain their current CSS and JavaScript.
- New sections receive scoped responsive CSS based on the current theme's container, typography, spacing, color, button, and card patterns. The legacy `cross-blocks.css` is not imported wholesale.
- The exchange calculator is intentionally non-functional at this stage: no legacy or new calculator behavior script is enqueued. Its server-rendered rates and controls remain visible as static markup.
- Respect existing reduced-motion and accessibility behavior; interactive elements without attached behavior must not falsely advertise a working calculation.

## Asset loading

- Register new cross-block section directories in the current section registry so their scoped styles load only on pages that use them.
- Reused sections continue to use their existing assets.
- Do not enqueue `platejka-pagespeed/js/cross-blocks.js`.
- Do not enqueue or copy the transfer-calculator assets.

## Existing content scope

- `test-cross-blocks` is a QA fixture only and is not treated as production content for migration decisions.
- Production cross-block associations are discovered from `skvoznye_bloki` at runtime rather than hard-coded by page ID.
- Current audit shows the production page `platezhi-za-graniczu-dlya-yuridicheskih-licz` contains `destinations`, `wtt`, and `problems_solved` among included rows. Other audited country pages contain `destinations`, while their `work` and `tariff` rows are excluded.

## Failure and compatibility behavior

- If ACF is unavailable, page rendering falls back to the existing `platejka_sections` behavior without fatal errors.
- Unknown or excluded layouts are ignored.
- Missing template files are skipped using the current debug logging convention.
- Malformed row values resolve to empty arrays and must not generate warnings.
- The integration must not modify the old inactive theme.

## Verification

- Unit-style PHP tests cover layout allowlisting, visibility, mapping, duplicate prevention, insertion before `faq`/`call`, fallback placement, and legacy-to-current data mapping.
- ACF schema tests confirm included labels and excluded layouts.
- Asset tests confirm new CSS is conditional and no exchange-calculator or transfer-calculator JavaScript is loaded.
- Runtime checks confirm the target production page renders one destinations section, one static exchange section, and one problems section in the agreed position.
- Existing theme tests and PHP syntax checks remain green.
