# Cross-blocks integration for `platejka_rework` — corrected design

## Goal

Expose the approved cross-blocks inside the existing ACF group `Конструктор секций страницы`, preserve the source PHP/HTML structure of newly transferred blocks, reuse current-theme sections where agreed, and keep cross-block content sourced from the legacy `skvoznye_bloki` rows.

## Source of truth

- Source templates: `themes/platejka-pagespeed/template-parts/cross-blocks`.
- Source content: ACF flexible field `skvoznye_bloki` (`field_67924d166072f`).
- Page composition/editor: ACF flexible field `platejka_sections` (`field_platejka_sections_v1`) in `Конструктор секций страницы`.
- The public production HTML is the authority for page-to-block associations because the local database snapshot predates the populated production rows.

The separate ACF group `Сквозные блоки` must no longer be extended or made visible on every page by the target theme. Existing production values remain intact and are read at runtime.

## Included and excluded blocks

| Legacy layout | Builder behavior |
| --- | --- |
| `destinations` | Use existing `destinations`; label it as a cross-block choice where applicable. |
| `wtt` | Add `cross-exchange`; preserve `exchange-calculator.php` DOM/classes/data attributes. Do not enqueue its JavaScript. |
| `company_metrics` | Add `cross-company-metrics`; preserve source DOM/classes. |
| `problems_solved` | Use existing `problems`; source its values from the matching legacy row. |
| `client_reviews` | Use existing `review`; do not add a separate renderer. |
| `external_ratings` | Add `cross-external-ratings`; preserve source DOM/classes. |
| `documents` | Use existing `documents`. |
| `key_facts` | Add `cross-key-facts`; preserve source DOM/classes. |
| `transfer_goals` | Add `cross-transfer-goals`; preserve the source card-grid DOM/classes. |
| `swift_problems` | Add `cross-swift-problems`; preserve the source card-grid DOM/classes. |
| `guarantees` | Use existing `guarantees`. |
| `audience` | Add `cross-audience`; preserve the source card-grid DOM/classes. |
| `data_table` | Use existing `table`. |

Exclude `transfer_calculator`, `work`, legacy `reviews`, and `tariff`.

## Builder schema

- Add the seven new layouts to `field_platejka_sections_v1`, not to `field_67924d166072f`.
- Their labels start with `Кросс-блок — `.
- Each new layout contains `enabled` and `anchor`; its editable content remains in the matching legacy row.
- Existing reusable layouts retain their current field definitions and storage.
- Remove target-theme filters that add layouts or locations to the separate `Сквозные блоки` group.
- Builder order controls output order. A new cross layout resolves its matching legacy row by layout name and renders nothing if that source row is missing or disabled.
- For existing reusable sections, matching legacy data overrides only non-empty values; normal current-theme defaults remain fallback values.

## Rendering contract

- Newly transferred blocks keep the source section wrappers, class names, child hierarchy, form controls, and `data-*` hooks.
- `wtt` retains Buy/Sell buttons, currency buttons, range input, prompt, total and CTA exactly as the source template provides.
- No exchange JavaScript is registered or enqueued at this stage. The controls are present for structural parity and future activation.
- CSS may change only presentation. It is scoped to the preserved source classes and adapted to the current theme's tokens, container, spacing, typography, radii and breakpoints.
- Existing reused sections keep their current markup and assets.
- Output is escaped using WordPress APIs and malformed/missing row data must not generate warnings.

## Page associations and local synchronization

The production crawl on 2026-10-12 found 129 public pages, 88 with any cross-block, and 78 with at least one included block after excluding `transfer_calculator`. Relevant counts are: `destinations` 76, `wtt` 1, `company_metrics` 1, `problems_solved` 2, `key_facts` 1, `transfer_goals` 1, `swift_problems` 2, `guarantees` 2, and `audience` 2. No rendered production instances were found for `external_ratings`, `documents`, `client_reviews`, or `data_table`.

Rare page associations are:

- `platezhny-agent`: `wtt`.
- `china`: `problems_solved`.
- `perevody-v-turcziyu`: `destinations`, `key_facts`.
- `kriptovalyutnye-perevody`: `destinations`, `company_metrics`.
- `perevody-v-evropu-dlya-yurlicz`: `swift_problems`, `destinations`, `guarantees`, `problems_solved`.
- `perevody-v-indiyu`: `destinations`, `transfer_goals`.
- `perevody-v-ssha`: `destinations`, `swift_problems`, `audience`.
- `perevody-v-koreyu`: `destinations`, `audience`, `guarantees`.

The local page `perevody-v-evropu-dlya-yurlicz` was last modified on 2026-05-12, while production was modified on 2026-08-31. Therefore local synchronization is a separate database operation:

1. Generate a read-only manifest from production HTML and local postmeta.
2. Show an exact dry-run of pages, builder rows and source content that would change.
3. Require explicit confirmation before writing local ACF values.
4. Back up affected postmeta and make the operation idempotent.

Public HTML cannot reveal disabled rows and does not expose raw ACF media IDs. Where exact source values cannot be reconstructed safely, the dry-run must mark the row unresolved rather than invent data.

## Verification

- Tests prove the new layouts exist only in `platejka_sections` and the separate group is no longer expanded.
- Template tests prove preserved source classes and exchange controls are present while calculator JS is absent.
- Composition tests prove builder order, source-row lookup, reuse without duplication, and missing-source behavior.
- Runtime checks cover `test-cross-blocks` plus the eight rare production associations.
- Database synchronization is verified separately through dry-run, backup, idempotence and post-write page rendering.
