# Local test report

Date: 2026-10-03 (Europe/Moscow). Scope: the local `platejka_rework`
theme, `platejka-core`, and the existing WordPress content for the home,
China payments, and About pages. Production deployment was not tested or
performed.

## Environment

- WordPress Studio 1.22.0, local URL `http://localhost:8883/`
- WordPress 7.0.2, PHP 8.4
- Node.js 22.16.0, npm 10.9.2
- Playwright 1.63.0, Microsoft Edge 154.0.4258.53
- Active theme: `platejka_rework` 0.1.0
- Active companion plugin: `platejka-core` 0.1.0
- Easy FancyBox 2.3.17 and WebP Express 0.25.14 are inactive; WP Meteor is
  not installed. No theme compatibility workaround was added for them.

## Results

The targeted PHP verification passed with zero failures:

| Suite | Checks |
| --- | ---: |
| Global navigation/header/footer | 81 |
| Forms and calculator | 38 |
| Content map | 21 |
| Home page | 48 |
| China payments page | 40 |
| About page | 38 |
| `platejka-core` bootstrap | 5 |

The single agreed Edge desktop regression passed: **1 test, 0 failures,
9.8 seconds**. At 1440 px it visited `/`, `/china/`, and `/o-kompanii/`,
verified their approved section order, header, footer, call component,
calculator presence where required, absence of placeholder links, ordinary
browser errors, server errors, and missing direct theme/core resources.

The three existing public pages remain published as IDs 24 (`glavnaya`, the
front page), 1873 (`china`), and 22 (`o-kompanii`). Their legacy
`_wp_page_template` values were preserved; the active block theme renders the
new page compositions verified above.

Before and after verification, the database hashes were identical:

- posts and postmeta for IDs 22, 24, 1873:
  `87eddda56cc6cce056edf1ce0739f3ebddb167a6bb2e3a1a1e7e641638bce2d8`
- relevant options:
  `fcc084ab11647e66d832caff3974d109a00ae37823e1d43afe77748b4ae3720e`

The local CF7 schema interceptor initially pointed to three missing generated
files. Its existing `platejka_cf7_schema_cache_warm_all()` function regenerated
only `schema-305.json`, `schema-3542.json`, and `schema-4966.json`; all three
then returned HTTP 200. This changed cache files only, not WordPress content,
forms, plugin settings, or tracked source.

Studio's PHP debug log setting is disabled and no current PHP/error log file
was present to inspect. The browser regression found no PHP fatal response or
uncaught JavaScript error after the CF7 cache regeneration.

## Deliberately skipped

Per the owner's fast-mode decision, automated responsive/mobile/tablet QA,
visual comparison screenshots, Lighthouse, performance/SEO audits,
cross-browser testing, exhaustive ACF/calculator suites, and repeated historic
regressions were not run. Responsive visual QA remains owner-managed.
