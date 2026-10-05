# Section-based page assembly

## Goal

Assemble the home page, the About page, and the default WordPress page template from the existing PHP sections under `themes/platejka_rework/sections`. Keep the existing section markup and copy unchanged for now. Load a section's CSS and JavaScript only when that section is present in the active page template.

## Page compositions

### Home (`page-home.php`)

1. `hero-main`
2. `about`
3. `shipments` (`main` mode)
4. `guarantees` (`main` mode)
5. `documents` (`main` mode)
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

### About (`page-about.php`)

1. `about-hero`
2. `location`
3. `review-main`
4. `infrastructure`
5. `financial`
6. `employees`
7. `exhibitions`
8. `developing`
9. `call-about`

### Default page (`page.php`)

This is intentionally the common template for all ordinary pages, initially using the supplied China-payment copy unchanged.

1. `hero` (the internal-page hero variant)
2. `about`
3. `shipments` (default mode)
4. `guarantees` (default mode)
5. `documents` (default mode)
6. `protection`
7. `review`
8. `work`
9. `problems`
10. `calculator`
11. `seo`
12. `faq`
13. `call`

The templates do not render a separate content block. `the_content()` remains inside `sections/seo/seo.php`. These three templates do not render the sidebar.

## Architecture

`inc/assets.php` owns a small section registry and the public page-assembly helpers. Each page template declares one ordered section configuration before calling `get_header()`. The declaration both queues assets early enough for `wp_head()` and becomes the single source used to render the page body in the same order.

A section configuration can specify:

- the section directory/asset slug;
- a PHP template variant, such as `hero-main.php`, `hero.php`, or `call-about.php`;
- a render mode, used by exported templates that contain `main` and default branches.

The renderer resolves files only below the theme's `sections` directory. Invalid or missing section files are skipped without breaking the public page. When WordPress debugging is enabled, the failure may be written to the PHP error log.

## Asset loading

`common.css`, `common.js`, and the theme stylesheet remain globally enabled because they are the required shared foundation. Header, preloader, and footer assets are also global because those components appear on every page.

For declared page sections, the registry checks for `<section>/<section>.css` and `<section>/<section>.js`. Existing files are enqueued with stable unique handles; missing optional files are ignored. Shared files and identical handles are registered only once. Removing a section entry from a page configuration removes its markup and its section-specific assets together.

Scripts are loaded in the footer where compatible with the existing files. File modification times are used as development-friendly asset versions when available, with the theme version as fallback.

## Rendering exported sections

The renderer supports the supplied exported `@if (mode === 'main')` and `@if (mode !== 'main')` branches so that home and internal variants do not render together. This compatibility is limited to the known export pattern and is not a general template language.

Section output is buffered so relative asset references such as `src="img/..."` and CSS-style `url('img/...')` values in inline markup can be rewritten to absolute URLs under the active section directory. Normal absolute URLs, root-relative URLs, data URLs, anchors, and unrelated links are unchanged.

## Global components

The existing custom header markup remains in `header.php`. The preloader section is rendered globally near the beginning of the body. `footer.php` renders the supplied footer section instead of the Underscores placeholder while retaining the required closing wrappers and `wp_footer()` call.

## Verification

Keep verification deliberately small:

1. Run PHP syntax checks on changed PHP files.
2. Add or run one focused smoke check covering section order, `main` versus default selection, and the absence of assets for an undeclared section.
3. If the local WordPress site is available, visually smoke-test the home, About, and one ordinary page.

Do not expand the legacy integration suite or add large fixtures for this wiring change.

