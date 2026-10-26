# Contact and Gallery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Connect FAQ and work forms, guarantee evidence links, and a full-size location gallery.

**Architecture:** Extend the existing CF7 modal and ACF options module. Keep the location viewer inside its section assets and reuse the window opener for guarantees.

**Tech Stack:** WordPress PHP, ACF, Contact Form 7, vanilla JavaScript, CSS.

**Spec:** `docs/superpowers/specs/2026-10-27-contact-gallery-design.md`

## Global Constraints

- Preserve existing uncommitted changes and push to `main` after verification.
- Work upload is required; only PDF/DOCX.
- Use the existing contact recipient.

## Review Focus

- Missing CF7 configuration must not produce a broken submit flow.
- Wrong file extension must be rejected by CF7 and browser.
- Gallery must handle one photo and multiple photos.
- Gallery must restore focus and support reduced motion.
- Popups must use the same URLs and behavior as hero trust links.

### Task 1: Forms

- [ ] Add behavior contract tests for FAQ and work modal.
- [ ] Add ACF form configuration and form rendering for each context.
- [ ] Connect FAQ and work buttons and style work attachment.
- [ ] Verify CF7 delivery configuration.

### Task 2: Trust links

- [ ] Test URL and window behavior against hero.
- [ ] Replace disabled guarantee controls with matching links.

### Task 3: Gallery

- [ ] Add behavior checks for navigation and close.
- [ ] Build dialog markup, navigation script, and animated styles.
- [ ] Verify keyboard, touch, and responsive behavior.

### Task 4: Release

- [ ] Run targeted tests, syntax checks, and browser checks.
- [ ] Review combined diff, commit all authorized changes, push main.
