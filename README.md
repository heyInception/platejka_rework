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
