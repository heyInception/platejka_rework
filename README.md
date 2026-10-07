# Platejka rework

Local WordPress rework: the classic PHP theme `themes/platejka_rework`, companion
plugin `plugins/platejka-core`, documentation, tests and root tool configuration.
The allowlist in `.gitignore` excludes the site's database, uploads, caches,
third-party plugins, mu-plugins, old themes and WordPress drop-ins.

Local site: `C:\Users\Inception\Studio\platejka`.
Repository: `C:\Users\Inception\Studio\platejka\wp-content`.
Read-only behavior reference: `themes/platejka-pagespeed`.
Read-only design export: `C:\project\platejka-new\wordpress`.

Use WordPress Studio CLI for every WP-CLI operation, always targeting the site:

```powershell
studio --version
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\smoke\site-baseline.php
```

Studio resolves relative `eval-file` paths at the site root. The plan's
`tests/smoke/site-baseline.php` argument therefore cannot find this test;
use the absolute path above. Studio 1.22.0 returned shell exit 0 even when
WP-CLI reported a failed assertion: require explicit PASS lines and
`Summary: 17 checks, 0 failures.`, not only the wrapper exit code.

The baseline characterizes the original site before switching themes. See
[baseline](docs/baseline.md),
[section assembly design](docs/superpowers/specs/2026-10-05-section-page-assembly-design.md),
[implementation plan](docs/superpowers/plans/2026-10-05-section-page-assembly.md) and
[asset licensing](THIRD_PARTY_LICENSES.md).
Preserve page IDs, slugs, ACF keys/values and existing integrations.
Production deployment and publishing are outside this project's current scope.

## Implemented page scope

- `/` — home page (existing page ID 24)
- `/china/` — China payments (existing page ID 1873)
- `/o-kompanii/` — About (existing page ID 22)

Pages are assembled from PHP components in
`themes/platejka_rework/sections`. Each page template declares one ordered
fallback section list before `get_header()`. When the ACF page builder is
enabled, its ordered rows replace that fallback. The resolved list controls
both rendering and section-specific CSS/JavaScript: removing a section also
removes its dedicated assets. `common.css`, `common.js`, header, preloader and
footer remain global foundations.

Page templates:

- `page-home.php` — the home-page sequence and `main` section variants;
- `page-about.php` — the About-page sequence;
- `page.php` — the shared sequence for ordinary pages, currently using the
  supplied China-payment copy.

`the_content()` is rendered by `sections/seo/seo.php`. Relative image paths in
section markup are resolved by the section renderer.

## ACF section builder

Reusable section content is maintained on the ACF options page
`Сквозные секции` → `Стандартный контент секций`. Every page has a separate
`Сборщик секций` switch and an ordered flexible-content field. New rows are
disabled by default. Non-hero layouts can be reordered and repeated; the hero
layout is limited to one row.

Page-row overrides follow these rules:

- an empty scalar field inherits the global section value;
- a non-empty scalar field replaces the global value;
- an empty repeater inherits the global collection;
- a non-empty repeater replaces the complete global collection.

The migrated home page (ID 24) contains 18 enabled rows in the approved legacy
order. The China page (ID 1873) contains its approved 13-row composition:
`hero`, `about`, `shipments`, `guarantees`, `documents`, `protection`, `review`,
`work`, `problems`, `calculator`, `seo`, `faq`, `call`. Only ID 1873 is changed
by the default-page migration; other pages using `page.php` retain the PHP
fallback. The China `review` layout keeps its own markup but inherits the
shared `review-main` content. Its `shipments`, `guarantees`, and `documents`
rows explicitly use the `default` variant.

New rows added in the editor are disabled by default. The migrated rows are
enabled deliberately to preserve the existing public page. Its original DOM
classes and JavaScript hooks remain part of the tested layout contract.
Editorial images are stored as WordPress attachment IDs and rendered with
intrinsic dimensions, `srcset` and `sizes`; decorative SVGs remain immutable
theme assets.

Preview the deterministic migration without writing anything, then apply it:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate --apply
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-default-page
studio wp --path C:\Users\Inception\Studio\platejka platejka section-builder migrate-default-page --apply
```

The migration is additive and idempotent: later runs add newly introduced
fields without replacing populated editor values or importing duplicate media.
To roll back the China page rendering without deleting its saved rows, disable
its `Сборщик секций` switch. `page.php` will immediately use the original
13-section fallback in the same order.

## Fast local verification

Run the focused section/page smoke suite with Studio WP-CLI, using its absolute
path. It verifies conditional assets, section variants, page order, preloader
and footer:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\integration\section-builder-applied.php
npx playwright test
```
