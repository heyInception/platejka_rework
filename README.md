# Platejka rework

Local WordPress rework: the new block theme `themes/platejka_rework`, companion
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
[baseline](docs/baseline.md), [design](docs/superpowers/specs/2026-10-01-platejka-rework-design.md),
[plan](docs/superpowers/plans/2026-10-01-platejka-rework.md) and
[asset licensing](THIRD_PARTY_LICENSES.md).
Preserve page IDs, slugs, ACF keys/values and existing integrations.
Production deployment and publishing are outside this project's current scope.

## Implemented page scope

- `/` — home page (existing page ID 24)
- `/china/` — China payments (existing page ID 1873)
- `/o-kompanii/` — About (existing page ID 22)

The supplied HTML/CSS/JavaScript from `C:\project\platejka-new\wordpress` is
ported into WordPress blocks; content is connected to existing ACF/WordPress
sources according to `docs/section-map.md`.

## Fast local verification

Run the focused PHP suites with Studio WP-CLI, using absolute paths. For
example:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\home-page.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\china-page.php
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\theme\about-page.php
```

Run the single agreed desktop regression in Microsoft Edge:

```powershell
$env:PLAYWRIGHT_BASE_URL='http://localhost:8883'
$env:PLAYWRIGHT_CHANNEL='msedge'
npx playwright test tests/e2e/regression.spec.ts --workers=1
```

See `docs/test-report.md` for evidence and skipped checks, and
`docs/release-checklist.md` for the remaining owner/manual and production
gates.
