# About Page ACF Migration Design

**Date:** 2026-10-07  
**Target:** WordPress page ID 22 (`/o-kompanii/`)  
**Status:** Approved in conversation; awaiting written-spec review

## Goal

Give the About page its own ACF section builder without adding its page-specific
content or layouts to the shared section-defaults group or to the generic page
builder. Preserve the current public layout, content, responsive behavior, and
JavaScript hooks while making the approved content editable on page ID 22.

## Scope

The builder contains exactly these layouts in the migrated order:

1. `about-hero`
2. `location`
3. `review-main`
4. `infrastructure`
5. `financial`
6. `employees`
7. `exhibitions`
8. `developing`
9. `call-about`

Eight layouts are page-local. `review-main` retains its existing shared
canonical content and allows page-local overrides.

The migration changes only page ID 22. Existing `o_kompanii` values, the
generic `platejka_sections` field, and the shared `platejka_section_defaults`
options are not deleted or overwritten.

## ACF Architecture

Create one local JSON field group titled `О компании — сборщик секций` with a
location rule for post ID 22 only. It owns two new stable fields:

- `platejka_about_page_builder`: a true/false switch;
- `platejka_about_sections`: a flexible-content field.

Every layout row contains:

- `enabled`, defaulting to false for newly added rows;
- an optional validated anchor;
- page-local content fields or a local override clone.

`about-hero` has `max: 1`. The other eight layouts can be reordered and
repeated. Migrated rows are explicitly enabled to preserve the existing page.
Instance-derived DOM IDs prevent duplicate IDs when repeatable layouts are used.

`page-about.php` reads only the About builder. When the switch is disabled, it
renders the existing nine-section PHP fallback. It does not use the generic
page-builder switch or rows.

## Data Resolution

The eight new layouts resolve directly from their own row data. Empty fields do
not read from the shared options page.

`review-main` resolves in this order:

1. shared `review-main` content from `platejka_section_defaults`;
2. non-empty local override fields on page ID 22;
3. the existing `review-main` template.

An empty local scalar inherits the shared scalar. An empty local repeater
inherits the shared collection. A non-empty local repeater replaces the entire
shared collection. This matches the established section-content merge rules.

## Page-Local Field Model

### `about-hero`

- eyebrow, H1, and lead;
- proof cards with image, title, text, and optional link;
- company metrics;
- rating sources and media links where represented by the current design;
- editable raster images while decorative signature SVG remains in the theme.

### `location`

- H2, description, address, phone, email;
- map accessible label and existing map hook;
- office gallery with image and alt text;
- slider labels required by the existing controls.

### `review-main`

- clone of the canonical shared `review-main` group as local overrides;
- no duplicated page-local default content at migration time.

### `infrastructure`

- H2 and description;
- specializations;
- numeric facts;
- infrastructure illustration;
- countries/entities with image, name, caption, and active/style state required
  by the existing markup.

### `financial`

- eyebrow, structured heading, description, and CTA;
- benefits;
- case labels, copy, image, and bank logo;
- bank section copy and bank list;
- test-payment copy, CTA, and image.

### `employees`

- structured heading;
- quote text;
- author name, role, and photo;
- team image;
- decorative quote/signature SVG assets remain in the theme.

### `exhibitions`

- H2, secondary heading, and description;
- trademark image, name, and caption;
- exhibition gallery with alt text and slider labels.

### `developing`

- H2;
- timeline repeater containing year, heading, rich text, and image;
- navigation labels derived from the same repeater so cards and year controls
  cannot drift apart.

### `call-about`

- structured H2 and CF7 form selection;
- trust cards;
- contact and social cards;
- messenger and media links with labels and icons;
- consent copy and interface labels required by the existing form markup.

## Heading Semantics

Headings with line breaks or highlighted fragments use structured `text`,
`accent`, and `decorative` fields instead of arbitrary editor HTML. The
semantic H1/H2 remains in the DOM. When a separate decorative representation is
needed, the semantic heading is screen-reader accessible and the visible
decorative element is `aria-hidden`, following the existing
`platejka_section_heading()` pattern.

## Markup and Media Preservation

Templates become data-driven without replacing or simplifying the approved
markup. Existing root classes, nested classes, `data-*` hooks, slider structure,
dialogs, map hooks, and form hooks remain stable.

Editable raster images are attachment IDs and render through
`wp_get_attachment_image()` with intrinsic dimensions and applicable `srcset`,
`sizes`, `loading`, and `decoding` attributes. Existing attachments are reused
where possible. Missing editable raster assets are imported once. Decorative
SVGs, complex signatures, and non-editorial icons remain theme assets.

## Migration Source and Conflict Rules

The current public output of the reworked About page is the authoritative
content baseline. The legacy `o_kompanii` field is used when its values match
the current section or provide real links, forms, or media needed by that
section. If current output and legacy content conflict, the current output wins.

Legacy values remain untouched. Existing values already entered into the new
About fields also remain untouched; the migration seeds only empty new fields.

## Migration Command

Provide a preview-first WP-CLI command:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-about-page
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-about-page --apply
```

The command:

1. validates the immutable seed and the target page ID;
2. reports current and target values without writes by default;
3. reuses media by source marker or content hash;
4. imports only missing editable raster media;
5. seeds page-local content only where the new fields are empty and writes the
   exact nine rows;
6. verifies normalized ACF values and row order;
7. enables the About builder only after verification;
8. writes a dedicated version marker last.

An interrupted run leaves the builder disabled until verification succeeds.
Imported media and successfully saved values are reusable on the next run. A
repeated successful apply performs no writes and imports no media.

## Rollback

Disable `О компании — сборщик секций` on page ID 22. The page immediately
returns to its existing nine-section PHP fallback in the same order. Saved new
rows, imported media, and legacy `o_kompanii` data remain available and are not
deleted.

## Validation

Use a medium test profile:

- ACF schema checks for stable keys, supported field types, default-disabled
  rows, `about-hero` maximum one, and location restricted to ID 22;
- resolver checks for page-local content and `review-main` inheritance;
- template checks for escaping, unique IDs, responsive images, existing classes,
  and JavaScript hooks;
- deterministic read-only migration preview;
- post-apply exact order, enabled rows, marker ordering, and idempotency;
- one Playwright smoke at desktop and mobile widths covering the nine-section
  order, one About hero, review interactions, sliders, map hook, call form,
  representative layout hooks, responsive image attributes, and runtime errors.

The migration requires an explicit reviewed dry-run before `--apply` because it
writes ACF values and media to the local WordPress database.
