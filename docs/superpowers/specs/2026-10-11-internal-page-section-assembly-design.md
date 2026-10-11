# Internal Page Section Assembly Design

## Goal

Bring the compact internal pages rendered by the default `page.php` template onto a consistent section composition while preserving their existing local content. Reuse the current shared sections from `platejka_rework`, introduce a reusable `Process` section type, and migrate legacy Process data only where it exists.

The target pages currently use compact combinations of the internal Hero, payment directions (`shipments`), Cases, and FAQ sections. Special compositions such as Home, About, and the expanded China page are outside this change.

## Approved composition

The canonical order for eligible pages is:

1. Internal Hero
2. Payment directions, when already present
3. Cases, when already present
4. Calculator
5. Process, only when legacy Process content exists
6. Guarantees
7. Client reviews
8. Available destinations
9. SEO
10. FAQ
11. Call

An absent payment-directions or Cases section must remain absent. The migration must not invent page-specific content.

## Scope and eligibility

The migration targets editorial pages with statuses `publish`, `future`, `draft`, `pending`, or `private` that:

- are rendered by the default page template;
- have the ACF page builder enabled;
- use one of the recognized compact internal-page compositions;
- are not Home, About, the expanded China page, or another special composition.

Eligibility is determined from template and builder structure rather than a permanent hard-coded list of page IDs. Trash, revisions, and autosaves are excluded.

Legacy `skvoznye_bloki` (`cross-block`) data is out of scope. Its presence neither disqualifies a page nor causes those rows to be copied. The migration does not read, modify, render, or remove that field.

## Existing shared sections

The following `platejka_rework` sections already provide templates, assets, ACF schemas, global defaults, and the shared-content resolver:

- `calculator`
- `guarantees`
- `review`
- `destinations`
- `seo`
- `call`

They will be reused, not duplicated or recreated from the old theme. Their new page-builder rows contain no local content overrides and therefore inherit canonical values from `platejka_section_defaults`.

Existing Hero, payment-directions, Cases, and FAQ rows must retain their enabled state, variant, anchor, description mode, and local overrides. Their rows are moved into the approved order without rebuilding their data.

## Process section

### Component structure

Add Process as a normal section in the existing section system:

- `themes/platejka_rework/sections/process/process.php`
- `themes/platejka_rework/sections/process/process.css`
- a `process` entry in the section registry;
- a `process` flexible-content layout in the shared page builder;
- a central ACF field schema that can be cloned into page-local overrides.

No JavaScript file is required. The section is presentational, and an empty script would add maintenance and request overhead without behavior.

The current section asset loader discovers the enabled Process row and enqueues `process.css` only for pages that render the section. Pages without Process receive no Process asset.

### Data model

Process uses the following normalized data:

```text
title: string, required
items: repeater, at least one row
  title: string, required
  text: textarea or rich text, required
description: textarea or rich text, optional
```

The legacy presentation selector `vybor_elementa` (normal, half, or quarter width) is deliberately omitted. Editors control content and order, while layout remains automatic and consistent.

Process is a reusable shared section type, but its content is page-local. Editors can add it to any supported internal page and fill its local override fields. No demonstration or fallback copy is inherited when those fields are empty.

The template fails closed: if the resolved data lacks a non-empty title or at least one complete step, it emits no section markup.

### Presentation

The old `platejka-pagespeed` Process section is a visual reference, not source code to copy verbatim. The new section retains:

- ordered steps;
- visible automatic numbering;
- a clear progression between cards on larger screens;
- optional supporting text below the steps.

The implementation uses `platejka_rework` containers, spacing, typography, color tokens, and responsive conventions. Card widths are determined by a responsive CSS grid. Mobile collapses to one column and removes decorative connectors that do not aid comprehension.

Markup must remain semantic and accessible. Step order is represented in the DOM; decorative numbers and connectors must not create redundant screen-reader output.

## Legacy Process migration

Only pages with meaningful data in `Блоки → Процесс` receive a Process row. The mapping is:

| Legacy field | New local field |
| --- | --- |
| `process.zagolovok` | `process.title` |
| `process.elementy[].zagolovok` | `process.items[].title` |
| `process.elementy[].tekst` | `process.items[].text` |
| `process.tekst` | `process.description` |
| `process.elementy[].vybor_elementa` | ignored |

The audit found relevant legacy data on two pages within the approved compact-page scope:

- `2098`, “Международные денежные переводы”, with six steps;
- `2109`, “Оплата инвойса в Китай”, with seven steps and supporting text.

Home and the expanded China page also contain legacy Process values, but they are outside this migration because their compositions are explicitly excluded.

## Migration behavior

Create a dedicated WP-CLI migration command following the repository's preview-first pattern.

The default invocation is read-only and reports:

- eligible and excluded pages;
- current and target section sequences;
- Process source and mapped target data;
- planned writes;
- conflicts.

The write path requires an explicit `--apply`. It updates only the page-builder enabled flag or rows that differ from the approved target. It records a version marker only after all writes have been verified.

The command must be deterministic and idempotent. After a successful apply, another preview reports no pending writes.

## Conflict policy

Migration safety takes precedence over forcing a uniform result. A page is reported as conflicting and skipped in full if it contains any of the following:

- duplicate target section rows;
- an unexpected section relative to its recognized pre-migration or post-migration composition;
- an existing Process row whose local content differs from the mapped legacy source;
- meaningful local overrides on a shared row the migration would otherwise add or normalize;
- another state that would require deleting or overwriting editor-authored data.

The command must not silently delete rows, replace overrides, or resolve conflicts by choosing one value.

## ACF and editorial behavior

The Process layout appears alongside the other page-builder layouts. Its title and at least one step are required in the editor; the supporting description is optional. Editors can reorder, add, or remove steps.

Shared Calculator, Guarantees, Reviews, Available destinations, SEO, and Call rows remain individually enableable through the existing `enabled` field. Their content continues to inherit from the section-defaults options page unless an editor intentionally adds a supported local override later.

Legacy fields remain untouched by the data migration. Their visibility can be managed separately by the existing legacy-field policy once migration completion has been verified.

## Validation

### Unit coverage

- compact-page eligibility and exclusions;
- canonical ordering with optional payment directions and Cases;
- preservation of existing row metadata and overrides;
- insertion of inheritance-only shared rows;
- Process field mapping and omission of `vybor_elementa`;
- omission of Process when legacy data is empty;
- conflict detection;
- deterministic and idempotent reconciliation.

### Integration coverage

- a dry-run database snapshot is identical before and after preview;
- the report contains the expected target pages and planned writes;
- cross-block values are unchanged;
- an apply-mode test verifies stored rows and the completion marker in an isolated test state;
- a second preview after apply produces no writes.

Actual local database application is not part of ordinary test setup. After implementation, run the real dry-run and present its exact report for review. Execute the real `--apply` only after separate explicit confirmation.

### Theme coverage

- Process renders escaped, semantic content from resolved section data;
- incomplete Process data emits no wrapper;
- step numbering and ordering are stable;
- Process CSS is enqueued only when Process is in the resolved section list;
- no Process JavaScript is registered or requested;
- the six reused shared sections resolve global data without copied local content.

### Visual coverage

Check desktop and mobile rendering for:

- page 2098 with six steps;
- page 2109 with seven steps and supporting text;
- a migrated page without Process;
- the complete approved section order and spacing between adjacent sections.

## Non-goals

- rebuilding existing shared sections;
- migrating, rendering, or deleting legacy cross-block rows;
- changing Home, About, China, or other special page compositions;
- inventing missing payment-directions, Cases, or Process content;
- carrying legacy card-width controls into the new editor;
- applying database writes without reviewed preview output and explicit approval.
