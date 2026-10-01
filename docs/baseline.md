# Original local site baseline

Verified on 2026-10-01 from `C:\Users\Inception\Studio\platejka\wp-content`,
using Studio CLI 1.22.0. This is a read-only characterization before the new
theme or companion plugin is activated. No credentials, database contents or
admin auto-login links are recorded.

## Reproduce

```powershell
studio --version
studio wp --path C:\Users\Inception\Studio\platejka eval-file C:\Users\Inception\Studio\platejka\wp-content\tests\smoke\site-baseline.php
```

The canonical absolute-path command exited 0 and emitted:

```text
PASS: page 24 exists as page | actual=page
PASS: page 24 slug=glavnaya | actual=glavnaya
PASS: page 24 template=page-home.php | actual=page-home.php
PASS: page 1873 exists as page | actual=page
PASS: page 1873 slug=china | actual=china
PASS: page 1873 template=page-payments.php | actual=page-payments.php
PASS: page 22 exists as page | actual=page
PASS: page 22 slug=o-kompanii | actual=o-kompanii
PASS: page 22 template=page-company.php | actual=page-company.php
PASS: active theme=platejka-pagespeed | actual=platejka-pagespeed
PASS: active theme version=1.2.6 | actual=1.2.6
PASS: WordPress=7.0.2 | actual=7.0.2
PASS: PHP=8.4.x | actual=8.4.25
PASS: required plugin ACF Pro active | actual=active version=6.2.6.1
PASS: required plugin Contact Form 7 active | actual=active version=6.1.4
PASS: required plugin Yoast SEO active | actual=active version=26.8
PASS: required plugin WP Rocket active | actual=active version=3.16.2.1
Summary: 17 checks, 0 failures.
```

## Path and exit-status limitations

The plan's exact command was run:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file tests/smoke/site-baseline.php
```

Output: `Error: 'tests/smoke/site-baseline.php' does not exist.` Studio resolves
relative paths against the site root rather than this repository's `wp-content`.
The wrapper still exited 0. No file was created outside the authorized workspace.

A controlled negative run temporarily changed only the test's expected home
slug to `glavnaya-regression-probe`, then ran:

```powershell
studio wp --path C:\Users\Inception\Studio\platejka eval-file wp-content/tests/smoke/site-baseline.php
```

It emitted `FAIL: page 24 slug=glavnaya-regression-probe | actual=glavnaya`
and `Summary: 17 checks, 1 failures.` The assertion was restored to `glavnaya`
before the successful canonical run. The test requests `WP_CLI::halt(1)` on
failure, but Studio 1.22.0 returned shell exit 0 for this negative run as well.
Automated consumers must validate the final summary and reject FAIL/error
output rather than rely solely on Studio's process exit status.

Studio required access to its PHP runtime CA-bundle temporary file under
`C:\Users\Inception\.studio`; the initial sandbox-only inventory was denied.
The read-only WP-CLI commands succeeded with runtime access.

## Repository boundary

The root allowlist permits only project documentation/tests, the new theme,
the companion plugin, and named root tool configurations. Database, uploads,
caches, third-party plugins, mu-plugins, legacy themes and drop-ins are ignored.
The pre-existing `themes/platejka_rework` scaffold remains untracked for its
later implementation task. Task 1 stages its five deliverables and the
existing spec/plan only. `.superpowers` remains locally excluded.

After creating the allowlist, `git status --short` emitted:

```text
?? .gitignore
?? README.md
?? THIRD_PARTY_LICENSES.md
?? docs/
?? tests/
?? themes/
```

`git status --short --untracked-files=all` confirmed that `themes/` contains
only the pre-existing `platejka_rework` scaffold. `git check-ignore -v`
confirmed exclusion of `database/.ht.sqlite`, `uploads`, `cache`,
`wp-rocket-config`, `advanced-cache.php`, `db.php`, `index.php`, `mu-plugins`,
`plugins/advanced-custom-fields-pro`, `themes/platejka-pagespeed`,
`themes/platejka` and `.superpowers`. The new theme, companion plugin,
`docs/baseline.md` and `tests/smoke/site-baseline.php` match negated allow rules.
