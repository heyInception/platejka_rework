# Contact forms, trust links, and location gallery

## Intent
Make the FAQ and work CTAs functional, expose the guarantees evidence links, and let visitors inspect location photos at full size.

## Approach
Reuse the theme's CF7 modal system and popup link script. Add a dedicated CF7 work form with required PDF/DOCX attachment, amount, currency, recipient details, and payment purpose; deliver to the site's ordinary form recipient. Use a native dialog for the photo viewer with themed CSS animation and pointer/keyboard navigation. No new frontend dependency.

## Constraints
Preserve existing uncommitted manual edits. Use the existing FAQ ACF settings where available. Guarantee links match hero trust links. Work attachment must be required and restricted to PDF/DOCX. Photo viewer needs click, arrows, swipe, Escape, and animated opening. Commit and push the combined changes to main after verification.

## Alternatives considered
Using the generic consultation form for work would impose work-specific fields on unrelated requests. A separate gallery library would add a dependency for a small viewer. The chosen approach fits the site's existing components.
