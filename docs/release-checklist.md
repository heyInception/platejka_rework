# Local release checklist

This checklist covers the local test site only. It does not authorize or
describe a production deployment.

- [x] `platejka_rework` 0.1.0 is the active local theme.
- [x] `platejka-core` 0.1.0 is active and its bootstrap smoke passes.
- [x] Existing page IDs, slugs, publication state, content, postmeta, ACF data,
  and relevant options remain unchanged.
- [x] Home, China payments, and About render the approved exported section
  order with the shared header, footer, forms, and calculator.
- [x] Targeted PHP verification passes with 271 checks and zero failures.
- [x] One Microsoft Edge 1440 px regression passes with no uncaught browser
  error, fatal/server response, placeholder link, or missing direct owned
  resource.
- [x] Generated CF7 schema cache files for forms 305, 3542, and 4966 exist and
  return HTTP 200.
- [x] Easy FancyBox and WebP Express are inactive; WP Meteor is absent. No
  unused compatibility workaround is present in the theme.
- [x] Relevant PHP was executed by the targeted Studio WP-CLI suites; changed
  JavaScript/JSON and the Playwright spec pass syntax/configuration checks.
- [x] `git diff --check` passes.
- [ ] Owner completes responsive and visual QA on the required devices.
- [ ] Owner reviews real CF7 delivery and amoCRM receipt with safe test leads
  before any production release.
- [ ] Production backup, deployment window, cache strategy, monitoring, tag,
  push, and rollback plan are approved separately.
