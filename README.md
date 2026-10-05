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
section list before `get_header()`. The declaration controls both rendering and
section-specific CSS/JavaScript: removing a section from the list also removes
its dedicated assets. `common.css`, `common.js`, header, preloader and footer
remain global foundations.

Page templates:

- `page-home.php` — the home-page sequence and `main` section variants;
- `page-about.php` — the About-page sequence;
- `page.php` — the shared sequence for ordinary pages, currently using the
  supplied China-payment copy.

`the_content()` is rendered by `sections/seo/seo.php`. Relative image paths in
section markup are resolved by the section renderer.

## Fast local verification

Run the focused section/page smoke suite with Studio WP-CLI, using its absolute
path. It verifies conditional assets, section variants, page order, preloader
and footer:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\section-loader.php
```
