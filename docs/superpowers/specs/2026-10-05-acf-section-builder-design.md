# ACF section builder and shared defaults

## Purpose

Create a reusable ACF-based section system for WordPress pages. Editors must be able to arrange, repeat, enable, and disable sections per page while inheriting canonical site-wide content. The home page is the first migrated page; other pages retain their current PHP composition until an editor explicitly activates the builder.

The system must preserve the current public appearance of the home page, keep legacy ACF groups and data untouched, and provide a safe path for adding sections from other pages later.

## Selected approach

Use one new ACF options page for canonical section defaults and one new page field group for the page builder.

Alternatives considered:

1. Store a complete independent content copy on every page. This makes editing simple but creates drift and defeats shared defaults.
2. Let pages select and order global sections without local overrides. This is compact but cannot support page-specific headings, links, or cards.
3. Use global defaults with field-level page overrides. This is the selected approach because it keeps one canonical baseline while allowing controlled local variation.

## ACF administration

Register a new options page in `platejka-core`:

- Page title and menu title: `Сквозные секции`.
- Capability: `edit_pages`.
- Stable post ID: `platejka_section_defaults`.
- The page is registered in code and is not created through the ACF UI.

Add two completely new field groups as ACF Local JSON in `plugins/platejka-core/acf-json`:

1. `Стандартный контент секций`, located only on the new options page.
2. `Конструктор секций страницы`, located on every `page` post.

The JSON schemas are the only field-definition source. Do not persist duplicate field-group posts in the database. Field values continue to use normal WordPress/ACF storage.

The existing group `Сквозные блоки`, all other legacy groups, and their stored values remain unchanged for rollback and later migration work.

## Page-builder model

The page group contains:

- `platejka_page_builder`: a `true_false` field labelled `Использовать конструктор секций`, defaulting to false.
- `platejka_sections`: a `flexible_content` field labelled `Секции`.

Every layout row contains these common controls:

- `enabled`: `true_false`, default false, labelled `Показывать секцию`.
- `anchor`: optional sanitized anchor without the leading `#`.
- Section-specific override fields.

The flexible-content row order is the public render order. Most layouts may repeat without limit. All hero layouts form one exclusive family: a page may contain no more than one hero row of any hero variant. Validation must reject a save that contains multiple hero-family rows.

When the page builder is disabled, the current PHP section declaration remains authoritative. When enabled, only ACF rows are considered. An enabled builder with zero rows, or with every row disabled, intentionally renders no body sections. An enabled row renders even when all of its resolved content is empty; visibility is never inferred from content completeness.

The editor UI places the builder immediately below the page title. The normal WordPress content editor remains available below it because the `seo` section continues to consume `the_content()`.

Flexible-content rows should be collapsible and labelled with the section name, enabled/disabled state, and custom anchor when present.

## Initial layouts and home composition

The first release includes all current home-page layouts:

1. `hero-main`
2. `about`
3. `shipments` in `main` mode
4. `guarantees` in `main` mode
5. `documents` in `main` mode
6. `compliance`
7. `review-main`
8. `work`
9. `calculator`
10. `with-us`
11. `destinations`
12. `seo`
13. `problems`
14. `serves`
15. `cases`
16. `table`
17. `faq`
18. `call`

Migration activates the builder on the existing home page ID 24 and creates these 18 enabled rows in this exact order. Newly added rows on any page remain disabled by default.

For `shipments`, `guarantees`, and `documents`, the global group stores separate content for `main` and `default` variants. Their page rows expose a tri-state variant control: inherit, main, or default. The migrated home rows explicitly select `main`. Override fields for only the effective variant are shown through conditional logic; saved values for the inactive variant are retained.

## Content inheritance

Resolve scalar, link, image, file, and rich-text values in this order:

1. A non-empty page override.
2. The corresponding global default.
3. An empty value.

There is no fallback to text or media embedded in PHP after migration. PHP section templates own structure and behavior only.

For repeater and gallery fields, an empty page value inherits the complete global collection. Any non-empty page collection replaces the global collection in full. Rows are never merged by position or key.

Page-level boolean overrides use a tri-state select (`inherit`, `on`, `off`) so an explicit false value is distinguishable from inheritance. The row-level `enabled` control remains a normal boolean.

When an inherited element may need to disappear, add a section-specific tri-state visibility control such as `Скрыть заголовок`. Do not add a generic visibility switch to every scalar field.

## Editorial field rules

Move all editorial content into the new system:

- headings and short labels use `text` or `textarea`;
- long formatted copy uses `wysiwyg` with WordPress-allowed markup;
- cards, FAQ entries, stages, table rows, tags, and similar collections use repeaters;
- calls to action use ACF `link` fields;
- documents use ACF `file` fields;
- content images use ACF `image` fields returning attachment IDs;
- technical CSS classes, data attributes, decorative SVGs, and JavaScript hooks remain in PHP templates.

Editors do not enter arbitrary HTML or CSS classes. Heading line breaks are entered as lines in a textarea and rendered as escaped text separated by `<br>` elements.

Ordinary headings remain visible semantic `<h2>` elements. For headings that require complex decorative markup, render a plain-text `<h2 class="screen-reader-text">` and a visible decorative `<div aria-hidden="true">` assembled from the same structured ACF data. Do not use `display: none` or `visibility: hidden` for the semantic heading.

## Images and files

Content images are stored as WordPress attachment IDs and rendered through `wp_get_attachment_image()` so WordPress supplies intrinsic dimensions, responsive `srcset`, and `sizes`. Templates define the correct image size and `sizes` value for their layout.

Hero and first-viewport content images use eager loading and high fetch priority. Other content images use lazy loading and no high-priority hint. Decorative assets remain files in the theme and are not imported solely to make them editable.

Migration first reuses an existing media attachment that matches the source URL or file. It imports a content image from the theme only when no matching attachment exists. Re-running migration must not create duplicate attachments.

Semantic images use the Media Library alt text. Decorative images use an empty alt attribute.

## Rendering and identifiers

Extend the existing section loader rather than replacing its conditional asset system. The builder normalizes enabled ACF rows into the same section configuration consumed by `platejka_use_sections()` and `platejka_render_sections()`.

Each rendered row receives a unique per-instance identifier. Fixed IDs and matching `aria-labelledby`/`aria-controls` values inside a section receive the same instance suffix so repeated sections produce valid HTML. The optional editor anchor is sanitized and used as the public section ID when it is unique. Duplicate or invalid custom anchors must produce an ACF validation error rather than silently changing the requested anchor.

CSS and JavaScript for a section directory load once even when that section renders multiple times. Removing or disabling every instance removes its section-specific assets.

## SEO-section exceptions

The main long-form SEO copy remains in the standard page editor and continues to render through `the_content()`.

The current heading logic remains unchanged:

```php
<?php if ( is_page( 2054 ) ) : ?>
	<h2><?php the_field( 'zagolovok_services' ); ?></h2>
<?php else : ?>
	<h2><?php the_title(); ?></h2>
<?php endif; ?>
```

Other editorial elements in the `seo` section, including its cards and toggle labels, move to the new global/default and page-override model.

## Contact Form 7 integration

The `call` and `calculator` layouts each contain an independent Contact Form 7 selector using a `post_object` field restricted to `wpcf7_contact_form` and returning an ID.

Global migration assigns:

- `call`: form ID 305, `Заявка с сайта`;
- `calculator`: form ID 4966, `Заявка на перевод`.

Page rows may independently override either selection. Form fields, submission, validation, and downstream integrations remain owned by Contact Form 7. A missing or deleted selected form must fail closed with no shortcode execution and, when `WP_DEBUG` is enabled, a diagnostic log entry.

## Migration and rollout

Migration has two phases:

1. A read-only preview produces a deterministic report of option values, page 24 builder rows, selected CF7 forms, media matches/import candidates, and every database write that would occur.
2. After explicit approval of that report, execute the idempotent write pass.

Populate global defaults from the content currently rendered by the home section templates and existing approved content adapters. Preserve the current home output after page 24 starts using the builder. Do not modify production or publish a preview site as part of this task.

The migration must be safe to rerun. It may update the known new option/page fields to the same intended values, but it must not duplicate flexible rows, media attachments, or forms.

## Validation and security

- Sanitize anchors with WordPress primitives and reject invalid or duplicate anchors.
- Reject multiple hero-family rows on one page.
- Escape text and attributes at output; allow rich content only through `wp_kses_post()`.
- Validate attachment, file, and CF7 post IDs before rendering.
- Never execute an arbitrary shortcode stored in ACF.
- Resolve only known section-layout slugs from a fixed registry.
- Preserve the existing traversal protection in the section loader.

## Verification

Automated coverage must prove:

- both new Local JSON groups load and contain only supported ACF 6.2.6.1 field types;
- the options page uses `edit_pages` and the stable post ID;
- builder-disabled pages keep their current PHP composition;
- builder-enabled pages use only enabled ACF rows in stored order;
- new rows default disabled;
- duplicate non-hero rows render, while multiple hero-family rows fail validation;
- scalar and tri-state inheritance works;
- repeaters replace rather than merge;
- variant-specific content resolves correctly;
- assets enqueue once for repeated sections and not at all for disabled sections;
- repeated markup has unique IDs and matching ARIA references;
- responsive images use attachment IDs with the required loading priority;
- CF7 selections render only validated form IDs;
- the SEO title branch and `the_content()` behavior remain unchanged;
- migration preview is read-only and the write pass is idempotent;
- the migrated home page has the exact 18-section order and preserves its public section structure.

Run PHP syntax checks, the focused Studio WP-CLI integration tests, the existing PHPUnit suite, and browser smoke tests for the home page plus one non-migrated page.

## Out of scope

- Deleting or rewriting legacy ACF groups.
- Migrating About, China, or other pages to builder rows.
- Production deployment or preview-site publication.
- Rewriting section CSS or JavaScript beyond changes required for repeat-safe identifiers and real CF7 mounting.
- Moving the main SEO article body out of the standard page editor.
