# Default-page ACF section migration

## Purpose

Extend the existing ACF section builder with the section composition used by
`page.php`, then migrate only the current China page (`/china/`, post ID 1873)
to that builder. Preserve its public markup, styling, scripts, content, and
interaction behavior.

Other pages that use `page.php` remain on the existing PHP fallback until they
are migrated explicitly in a later task.

## Approved composition

The migrated page contains these enabled rows in this exact order:

1. `hero`
2. `about`
3. `shipments` with the `default` variant
4. `guarantees` with the `default` variant
5. `documents` with the `default` variant
6. `protection`
7. `review`
8. `work`
9. `problems`
10. `calculator`
11. `seo`
12. `faq`
13. `call`

The `hero` row is part of the exclusive hero family and may occur only once on
a page. The remaining rows follow the existing repeatability rules.

## Selected data model

Add two new canonical groups below the existing `Стандартный контент секций`
options-page field group:

- `hero`, separate from `hero-main`, because the internal-page hero has its own
  content structure and markup;
- `protection`, for the introductory copy, link, and protection cards.

Reuse the existing canonical groups for `about`, `shipments`, `guarantees`,
`documents`, `work`, `problems`, `calculator`, `seo`, `faq`, and `call`.

Add a `review` builder layout but do not create another canonical review group.
Its page overrides and inherited values use the same field structure and the
same `review-main` canonical data. Rendering still uses
`sections/review/review.php`, so the internal-page review keeps its distinct
DOM and behavior. In the section resolver, template slug and canonical content
slug are therefore separate concepts:

- template slug: `review`;
- canonical content slug: `review-main`.

The three variant sections explicitly select `default` after migration. Their
existing `main` data remains unchanged.

## Editorial fields

### Hero

The new `hero` group covers all editorial content currently embedded in the
internal hero template: badge text, primary and secondary title parts,
subtitle, feature items, informational text, trust items, CTA labels, links,
and content images. The embedded calculator continues to consume the existing
canonical calculator data rather than duplicating calculator fields inside the
hero group.

### Protection

The new `protection` group contains the eyebrow, title, description, primary
link, and a repeater of cards. Each card contains an attachment image, title,
body text, and optional detail/caption.

### Review

The `review` layout inherits labels, video reviews, and text reviews from
`review-main`. It renders only content supported by its existing internal-page
markup; the `review-main` rating summary is not added to the internal template.
An empty page-level review collection inherits the complete corresponding
global collection, following the existing replacement-not-merge rule.

All newly added builder rows continue to default to disabled for editors. The
migration explicitly enables the 13 rows for post 1873.

## Markup and layout contract

Dynamic templates must preserve the current static templates as the layout
contract. Do not rename, remove, reorder, or add wrappers around existing CSS
classes, `data-*` hooks, dialog hooks, slider hooks, or calculator hooks unless
a unique per-instance identifier is required by the existing builder rules.

In particular, preserve the structural contracts of:

- `sections/hero/hero.php`;
- `sections/protection/protection.php`;
- `sections/review/review.php`.

The work is a content-source migration, not a redesign. CSS changes are allowed
only when required to preserve the pre-migration rendering after dynamic data
is introduced.

Heading semantics follow the established builder rules: ordinary headings stay
as visible heading elements. When a heading contains decorative line breaks or
highlighted fragments, render a screen-reader-accessible semantic heading plus
an `aria-hidden` decorative element driven by the same structured ACF values.
The existing special SEO heading branch and `the_content()` behavior remain
unchanged.

## Images

Editable content images are stored as WordPress attachment IDs and rendered
through `wp_get_attachment_image()` (or the existing project helper built on
it). Output must retain intrinsic `width` and `height` and WordPress-responsive
`srcset` and `sizes` attributes.

Images in the first viewport use `loading="eager"` and
`fetchpriority="high"`; other content images use lazy loading. Meaningful
images use Media Library alt text, while decorative images use an empty alt.
Theme-only decorative assets may remain in the theme.

The migration reuses matching Media Library attachments before importing a
theme image and never creates duplicate attachments on reruns.

## Migration behavior

Migration targets only post ID 1873. It must:

1. seed the new global `hero` and `protection` defaults from the content that is
   currently rendered by their static templates;
2. leave the existing `review-main` global data as the canonical review source;
3. enable `platejka_page_builder` for post 1873;
4. write exactly the 13 enabled rows in the approved order;
5. select `default` for `shipments`, `guarantees`, and `documents`;
6. leave page override groups empty so the rows inherit canonical content;
7. leave all other pages and legacy fields untouched.

The migration is additive and idempotent. Before applying writes, its dry-run
must report the option values, page rows, media reuse/import decisions, and
every intended database change. The write pass is run only after the dry-run
has been reviewed. Rerunning it must not duplicate rows or media.

## Test-first implementation and verification

Add failing coverage before production changes for:

- presence and shape of the new `hero`, `protection`, and `review` layouts;
- one-hero validation across both hero layouts;
- `review` resolving global content from `review-main` while rendering the
  `review` template;
- `default` variant resolution for the three variant sections;
- disabled-by-default behavior for newly inserted rows;
- dry-run read-only behavior and migration idempotency;
- exact 13-row order for post 1873;
- responsive attachment markup and hero loading priority;
- preservation of the SEO heading branch and standard page content;
- preservation of required classes and JavaScript/data hooks in the three
  newly dynamic templates.

After implementation, run PHP syntax checks, the focused ACF/schema and
migration tests, the existing theme test suite, and browser checks for `/china/`.
Browser verification covers desktop and mobile layout plus hero calculator,
review tabs/slider/dialog, section order, console errors, and a comparison with
the current pre-migration baseline.

## Rollback

The migration does not delete static templates or legacy fields. Turning off
`platejka_page_builder` for post 1873 restores the existing `page.php` fallback.
The new option values may remain stored without affecting pages that do not use
the builder.

## Out of scope

- Migrating any page other than post 1873.
- Changing the visual design or rewriting section CSS/JavaScript.
- Creating another global review dataset.
- Changing the existing home-page builder rows or content.
- Production deployment or publication outside the local Studio site.
