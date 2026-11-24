# Task 31 — Financial Goal Calculator: Remove Large Banner

**Date:** 2026-11-24  
**Scope:** Financial Goal Calculator form + results headers only  
**Out of scope:** Tasks 32–37, global PFM banner removal, formula/input changes  
**Environment:** Local only

---

## 1. Existing banner architecture

Financial Goal does **not** use the shared `page-hero` PFM component.

| Piece | Role |
|---|---|
| `PfmToolController` | Routes FG form → `goal-form`, results → `goal-results` |
| `goal-form.blade.php` / `goal-results.blade.php` | Dedicated FG views |
| `header.yk-goal__hero` | Large dark gradient band |
| `svg.yk-goal__hero-art` | Decorative chart illustration |
| `yuktione-pfm-goal.css` | Hero padding (~96px bottom) + negative `margin-top` pull on `.yk-goal__layout` |

Other PFM tools (Money Planner, Emergency Fund, etc.) keep using `partials/page-hero.blade.php` — untouched.

---

## 2. Confirmed root cause

The large banner was the **tool-specific** `.yk-goal__hero` block (gradient + decorative SVG), not a shared site-wide banner.

Measured **before** (desktop Chromium):

| Metric | Value |
|---|---|
| `.yk-goal__hero` height | **380px** |
| Form card top | 393px |
| First calculator inputs | Required scrolling past the hero |

---

## 3. Removed banner components

From Financial Goal form + results:

- Large `.yk-goal__hero` gradient band
- `.yk-goal__hero-art` decorative SVG
- Oversized white-on-dark H1 styling for that band
- Card overlap pull (`margin-top: -60px`) under the compact header path

Preserved: breadcrumbs, calculator steps, form fields, aside summary, actions, history link, disclosures.

---

## 4. Compact header implementation

New partial: `resources/views/frontend/sections/pfm/partials/goal-compact-header.blade.php`

- Title: **Financial Goal Calculator**
- Description: **Estimate the monthly investment needed to achieve your financial goal.**
- Lucide `target` via `<x-yk-icon>`
- Soft plum icon badge (`#F8EAF1` / `#8A0F4D`)
- Breadcrumbs retained
- Results page uses the same compact header with goal name + fact chips (no large art)

Main classes: `yk-goal--compact-header`, `yk-goal__header`.

---

## 5. Before-and-after header height

| State | Header height | Form visible without scroll |
|---|---|---|
| Before | **380px** | No (hero dominated first viewport) |
| After | **179px** | **Yes** (`formTop` ≈ 253px) |

≈ **201px** vertical reduction on desktop.

---

## 6. Calculator accessibility improvements

- Goal type radios / Continue visible above the fold on desktop
- Compact heading still has clear H1 + description
- No empty hero container left behind
- Navigation and history link remain

---

## 7. Modified files

**New**

- `resources/views/frontend/sections/pfm/partials/goal-compact-header.blade.php`
- `tests/Feature/FinancialGoalBannerRemovalTest.php`
- `docs/TASK_31_FINANCIAL_GOAL_BANNER_REPORT.md`
- `docs/audits/task-31-financial-goal-banner/*`

**Updated**

- `resources/views/frontend/sections/pfm/goal-form.blade.php`
- `resources/views/frontend/sections/pfm/goal-results.blade.php`
- `public/frontend/assets/css/yuktione-pfm-goal.css`

---

## 8. Database changes

**None.**

---

## 9. Automated test results

| Suite | Result |
|---|---|
| `FinancialGoalBannerRemovalTest` | 2 passed |
| `GoalProjectionTest` | 5 passed |
| `PfmToolsTest` financial goal case | 1 passed |
| `EmergencyFundUiFormattingTest` (isolation) | 4 passed |

Banner test asserts: no `yk-goal__hero-art`, compact header markers, form fields present, Emergency Fund still has `yk-pfm__hero`.

---

## 10. Actual browser verification

`http://127.0.0.1:8000/tools/pfm/financial-goal`

- Compact header + target icon rendered
- Description matches Task 31 copy
- Goal type step visible without scrolling (desktop)
- No `.yk-goal__hero` / `.yk-goal__hero-art`
- Emergency Fund still returns shared PFM hero markup

Full calculate → save → history click path covered by existing `PfmToolsTest` financial-goal case.

---

## 11. Responsive verification

| Viewport | Method | Notes |
|---|---|---|
| Desktop | Chromium | Header 179px; form above fold |
| 390×844 | CDP emulation | Compact header; no large banner |

This is **viewport emulation**, not physical-device testing. Breakpoints 360/430/768/1024/1440 use the same compact CSS; spot-checked desktop + 390.

---

## 12. Before-and-after screenshots

| Shot | Path |
|---|---|
| Before large banner | `docs/audits/task-31-financial-goal-banner/before-large-banner.png` |
| After compact header | `docs/audits/task-31-financial-goal-banner/after-compact-header.png` |

---

## 13. Remaining limitations

- Shared PFM `page-hero` for FG **history/show** (if those shared templates are used) is unchanged — Task 37 covers global banner removal.
- FG still uses its own emerald/paper tool chrome; Task 32 will align full design consistency.
- Years slider / input / result restructures deferred to Tasks 33–36.

---

## 14. Confirmation — other financial tools unaffected

- Emergency Fund still serves `yk-pfm__hero` (verified via fetch + test).
- No edits to shared `page-hero` behaviour for non-goal tools beyond prior Task 30 optional icon prop.
- Money Planner / Net Worth / Debt / Retirement / Inflation / Savings templates not modified in this task.

---

**Status:** Task 31 complete. Stopped — waiting for further instructions.
