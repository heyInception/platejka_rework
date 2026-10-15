# Blog Redesign Design

## Purpose

Bring the blog landing page, category and tag archives, search results, and individual posts into the existing Platejka visual system. Preserve published content and URLs while improving readability, navigation, responsive behavior, and loading behavior.

## Scope and constraints

- Reuse the theme's existing Manrope typography, design tokens, containers, spacing, radii, buttons, header, footer, and contact flow.
- Preserve the three landing-page sections: Internet Accounting, Accounting Services, and News.
- Preserve existing post and taxonomy URLs and do not rewrite post content in the database.
- Keep `.wpcf7-spinner` unchanged.
- Preserve the user's current uncommitted blog, breadcrumb, AJAX, and stylesheet work; refactor it in place rather than discarding it.
- Support desktop, tablet, and mobile layouts.

## Architecture

Blog presentation lives in a conditionally loaded theme module under `sections/blog/`. The module owns blog CSS and JavaScript; PHP helpers own querying, card data, reading-time calculation, SEO directives, and secure AJAX responses. Shared template parts render the same card markup on initial and AJAX-loaded views.

The existing `index.php`, `archive.php`, and `single.php` templates become thin compositions of these helpers and template parts. Blog-specific rules move out of `style.css`; unrelated global styles and `.wpcf7-spinner` stay in place.

## Blog landing page

- Show breadcrumbs, the `Blog` heading, a compact search field, and category navigation.
- Keep three category sections in the existing order.
- Render three cards per section on desktop and two on mobile.
- All cards have equal visual weight; there is no featured or oversized first card.
- Each section has an AJAX `Show more` control when additional posts exist.

## Archives and search

- Category and tag archives use the same card component in a uniform grid.
- Initial archive batch sizes are nine on desktop, six on tablet, and four on mobile.
- Search updates results without a full reload and is scoped to the current category when used on a category archive.
- The search term is represented by `?blog_search=...` so the state can be shared and restored.
- Search-result parameter pages output `noindex, follow`; their canonical URL excludes the search parameter.
- Ordinary landing, archive, tag, and single-post pages remain indexable.

## Article cards

Every card contains:

- featured image or a consistent branded Platejka placeholder;
- one primary category;
- title;
- excerpt clamped to two or three lines;
- publication date;
- calculated reading time;
- view count when the views plugin is available.

The whole card is clickable, keyboard accessible, and has a consistent height within its grid row. Post tags are not displayed on cards.

## AJAX behavior

- `Show more` loads the next batch for the exact section, taxonomy, or search context.
- Requests include a WordPress nonce and sanitized query inputs.
- Responses return rendered card markup plus `has_more`; they do not concatenate template output incorrectly.
- Controls expose loading, disabled, empty, and recoverable error states.
- The button disappears after the final batch.
- Dynamic endpoints are not indexable content surfaces.

## Single article

- Show breadcrumbs, category, publication date, reading time, views, title, featured image, and post content in a readable-width column.
- Generate an in-page table of contents from `h2` and `h3` headings without requiring editor input.
- On desktop the table of contents is sticky beside the article; on mobile it is a collapsible block before the content.
- Do not render the standard WordPress sidebar or comments.
- Preserve authored headings, paragraphs, lists, images, tables, quotes, and embeds while applying blog typography and responsive overflow rules.

## Sharing and conversion

- Sharing controls use the visual treatment and source icons from the existing `call` section.
- The asset currently named/labeled as WhatsApp is presented accessibly as MAX.
- MAX, Telegram, and VK controls share the current article rather than opening company profiles.
- Copy-link feedback is retained as an accessible status message if copy is offered as a fallback.
- The end of the article contains a native CTA that opens the site's existing contact form, with Telegram as the secondary contact route.
- A `Read also` block shows three posts from the same category, excluding the current post.

## Accessibility and interaction

- Use semantic headings, lists, buttons, links, status messages, and `aria-expanded` for the mobile table of contents.
- Preserve visible keyboard focus and reduced-motion preferences.
- Search is debounced, cancellable, and announces result/loading states.
- Card links, load controls, sharing controls, and the table of contents work without pointer-only assumptions.

## Error and edge cases

- Missing featured images use the branded placeholder.
- Missing categories, excerpts, or view-plugin functions do not produce warnings or broken markup.
- Empty searches and empty archives show a designed message rather than a blank region.
- AJAX failures restore the control and show a retryable message.
- Duplicate posts are prevented across pagination batches.
- Very long titles, taxonomy names, and breadcrumb labels wrap without breaking the layout.

## Verification

- Add PHP contract tests for helper behavior, secure AJAX configuration, templates, SEO directives, card metadata, and the preservation of `.wpcf7-spinner`.
- Add browser coverage for landing sections, search, load-more behavior, single-post table of contents, CTA, sharing controls, empty/error states, and responsive layouts.
- Run PHP syntax checks, the repository unit/theme suites, relevant Playwright tests, and visual inspection at desktop, tablet, and mobile widths.

