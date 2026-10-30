# Cross Exchange Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the cross exchange calculator functional, visually consistent, and fed by maintained CBR rates.

**Architecture:** Keep the existing section and core rate reader. Add a section script for calculation and actions, global ACF settings for commissions, and move the Python updater into the core plugin.

**Tech Stack:** WordPress, ACF, Contact Form 7, vanilla JavaScript, Python.

**Spec:** Approved in this chat on 2026-10-30.

## Global Constraints

- Buy commission 2%; sell commission 5% of RUB base.
- Buy total = amount × CBR rate × 1.02; sell payout = amount × CBR rate × 0.95.
- Keep the public UI free of stale-rate warnings.
- Telegram must share a prepared message through the chat chooser.

## Review Focus

- Invalid and empty amounts must not generate a request.
- Typed amounts beyond the slider maximum must still calculate.
- Missing or malformed rates must not produce NaN.
- ACF settings outside 0–100 must fall back to defaults.
- Multiple exchange instances must work independently.

---

### Task 1: Pricing and settings

**Files:** `themes/platejka_rework/inc/exchange-settings.php`, `themes/platejka_rework/functions.php`, `themes/platejka_rework/tests/exchange-settings.php`

- [x] Add read-only ACF registration and value check.
- [x] Implement shared ACF fields and status display.
- [x] Run PHP checks.

### Task 2: Exchange UI

**Files:** `themes/platejka_rework/sections/cross-blocks/cross-blocks.php`, `cross-blocks.css`, `cross-blocks.js`, `themes/platejka_rework/inc/assets.php`

- [x] Add JS tests for buy/sell and invalid input.
- [x] Implement linked input/range, results, form and Telegram actions.
- [x] Redesign responsive layout and run checks.

### Task 3: Rate updater

**Files:** `plugins/platejka-core/tools/update_cbr_rates.py`, `plugins/platejka-core/docs/production-cbr-rates.md`

- [x] Move updater and its tests to core ownership.
- [x] Document existing production cron transition and snapshot behavior.
- [x] Run Python tests and syntax checks.
