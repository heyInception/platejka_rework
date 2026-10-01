# Third-party inventory

Inventory inspected on 2026-10-01 in the supplied design export
`C:\project\platejka-new\wordpress`. This baseline adds documentation only;
it does not copy the export's fonts, images, videos or JavaScript bundles.
Full notices and the evidence for each file's origin must accompany any later
distribution that includes those files.

## Fonts

| Supplied file under `common/fonts/` | Evidence and release action |
| --- | --- |
| `GoogleSans-Regular.ttf` | Redistribution permission was confirmed by the project owner in the agreed specification. Actual license evidence must be attached before a public release. No license text was supplied in the inspected export; this inventory does not invent one or substitute another font's license. |
| `Manrope-Medium.ttf`, `Manrope-SemiBold.ttf`, `Manrope-Bold.ttf` | Upstream Manrope is SIL Open Font License 1.1; see [Google Fonts' Manrope notice](https://github.com/google/fonts/blob/main/ofl/manrope/OFL.txt). The export contains no adjacent OFL file. Confirm the supplied binaries' provenance and include the matching copyright/license notice when copying them. |

## Bundled JavaScript

The supplied `common/common.js.LICENSE.txt` identifies the following libraries:

| Library | Version in supplied notice | License recorded by the source |
| --- | --- | --- |
| GSAP, Observer, ScrollTrigger | 3.15.0 | GreenSock standard license, copyright 2008–2026; supplied notice points to [GSAP standard license](https://gsap.com/standard-license). Preserve the supplied notice and attach the applicable terms before redistribution. |
| jQuery | 3.7.1 | MIT, OpenJS Foundation and contributors; supplied notice points to [jQuery license](https://jquery.org/license). |
| Select2 | 4.1.0 | [Upstream MIT license](https://github.com/select2/select2/blob/master/LICENSE.md); supplied notice references this file. |

`sections/call/call.js.LICENSE.txt` identifies Inputmask 5.0.8, copyright
2010–2023 Robin Herbots, MIT license. Keep that notice with any copied bundle;
its source is [Inputmask](https://github.com/RobinHerbots/Inputmask).

## Images, icons, flags and video

The export contains 238 PNGs, 214 SVGs, one JPG and two MP4 files. These counts
describe supplied files, not unique media or independently cleared rights.
The JPG is `sections/call/img/call-bg.jpg`; the MP4 filename is
`70960-536644237_medium.mp4`, under both `sections/review/img/review/` and
`sections/review-main/img/review/`. Image and icon files are distributed across
section directories. No separate image/video license evidence was found in the
export's two license-notice files. Record owner/source permission for the
specific decorative assets selected for transfer before public release.
Editable media continues to come from WordPress/ACF, with existing provenance.

## Existing theme scaffold and site dependencies

The existing `themes/platejka_rework` scaffold declares GNU GPL v2 or later
in `style.css` and `readme.txt` and includes `LICENSE`. Its stylesheet also
identifies normalize.css 8.0.1 as MIT. Preserve matching notices when retaining
that code. The scaffold is not staged in the Task 1 baseline commit.

WordPress, the legacy themes, ACF Pro, Contact Form 7, Yoast SEO, WP Rocket and
other installed third-party plugins remain external site dependencies. They
are excluded from this repository; this document grants no new rights to them.
