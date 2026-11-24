# Task 35 — Financial Goal Calculator: All Inputs on One Screen

**Status:** Complete (local only; no push/deploy)  
**Scope:** Replace FG three-step wizard with one continuous form. Tasks 31–34 preserved. Task 36 not started.

---

## 1. Previous three-step architecture

| Step | Label | Fields |
| --- | --- | --- |
| 0 | Goal type | `goal_type`, `goal_name` |
| 1 | Cost & timeline | `current_cost`, `years`, `existing_savings` |
| 2 | Assumptions | `inflation_percent`, `expected_return_percent` |

Mechanics: `data-pfm-wizard="1"`, `[data-pfm-step]` + `hidden`, step progress nav, Continue / Back, Calculate revealed only on last step. Wizard driven by shared `yuktione-pfm.js` `initWizard()` (layout-global). FG-specific `yuktione-pfm-goal.js` owned live summary + years slider only.

Pre-impl audit: `docs/audits/task-35-financial-goal-single-page/AUDIT.md`.

---

## 2. Exact inventory of all seven input fields

| Name | Control | Default | Required |
| --- | --- | --- | --- |
| `goal_type` | radio | `custom` | yes |
| `goal_name` | text | empty | optional |
| `current_cost` | number + ₹ | empty | yes |
| `years` | number + range (0.01–100, step 0.01) | empty | yes |
| `existing_savings` | number + ₹ | `0` | yes |
| `inflation_percent` | number + % | `6` | yes |
| `expected_return_percent` | number + % | `10` | yes |

---

## 3. New single-page form architecture

- `goal-form.blade.php`: `yk-goal--single`, no wizard attr/nav; one Calculate submit.
- `goal-fields.blade.php`: continuous `.yk-goal__panel` with Task 34 section hierarchy (all sections visible).
- Marker: `data-yk-goal-single="1"`.
- Shared `form.blade.php` FG map steps cleared to `[]` (FG still uses dedicated goal-form).
- POST `.calculate` → existing engine → existing `.results` route unchanged.

---

## 4. Removed wizard components

- Step progress (`yk-goal__steps` / Calculator steps).
- `data-pfm-wizard`, `data-pfm-step`, `data-pfm-next`, `data-pfm-prev`.
- Continue / Back buttons.
- Per-step `hidden` panels and step-only review `dl`.
- Eyebrow “3 quick steps” → “all inputs on one page”.

**Not removed:** Calculate, years ±/slider/quick chips, View history, sidebar live summary.

---

## 5. Desktop and mobile layout

- Desktop: two-column layout (form + sticky summary); assumptions in two columns; years slider full-width; ~48px controls retained.
- Mobile: single column; vertical scroll for all sections; Calculate full-width in actions bar.
- No horizontal overflow observed at 390px viewport emulation.

---

## 6. Years slider preservation

Task 33 intact: min/max/step/default, live readout, sync, ± bump, quick chips, draft/history restore. Browser: 10 → 11 synced; Calculate produced 11-year plan (₹16,285 / month).

---

## 7. Unified validation

- Backend `PfmCalculator` validation unchanged (`withInput` + errors on form).
- No step gating; all required fields present for HTML5 + server validation.
- Field-level `@error` hooks + scroll/focus helper in `yuktione-pfm-goal.js` after failed submit.
- User remains on single-page form (no obsolete step).

---

## 8. Draft compatibility

Session draft normalized inputs restore into the single form (same `$old` / draft path). Wizard-era drafts remain readable (field-keyed, not step-keyed).

---

## 9. Saved calculation compatibility

Save / history show / recalculate / new-test unchanged. Calculate does not auto-persist DB rows (asserted). Explicit Save creates one record.

---

## 10. Financial formula verification

Identical payload vs `PfmCalculator` engine: monthly, corpus, gap deltas asserted in Task 35 tests. Unit `PfmCalculatorTest` FG cases still pass.

---

## 11. JavaScript cleanup

- FG markup no longer activates shared `initWizard` (no `data-pfm-wizard` / steps).
- Shared `yuktione-pfm.js` **unchanged** (other tools keep wizards).
- `yuktione-pfm-goal.js`: preserved years sync; added post-error scroll/focus only.

---

## 12. Accessibility

- All seven fields in DOM without navigation; semantic section headings.
- Slider ARIA + named ± buttons retained.
- Calculate always available; labels/`aria-describedby` on money/% fields.
- Logical tab order through continuous form.

---

## 13. Modified files

| File | Change |
| --- | --- |
| `resources/views/frontend/sections/pfm/goal-form.blade.php` | Single-page shell; remove wizard UI |
| `resources/views/frontend/sections/pfm/partials/goal-fields.blade.php` | Continuous sections; field errors |
| `resources/views/frontend/sections/pfm/form.blade.php` | FG steps → `[]` safety |
| `public/frontend/assets/css/yuktione-pfm-goal.css` | `--single` panel/actions tweaks |
| `public/frontend/assets/js/yuktione-pfm-goal.js` | Error scroll/focus |
| `tests/Feature/FinancialGoalSinglePageFormTest.php` | New suite (5 tests) |
| `docs/audits/task-35-financial-goal-single-page/*` | Audit + screenshots |
| `docs/TASK_35_FINANCIAL_GOAL_SINGLE_PAGE_REPORT.md` | This report |

---

## 14. Database changes

None.

---

## 15. Automated test counts

**FG Tasks 31–35:** **19 passed / 256 assertions**

| Suite | Tests |
| --- | --- |
| BannerRemoval (31) | 2 |
| DesignConsistency (32) | 2 |
| YearsSlider (33) | 7 |
| Formatting (34) | 3 |
| SinglePageForm (35) | 5 |

**Related:** `PfmCalculatorTest` pass; Account Recovery pass; EF UI formatting pass. Broader filter: **93 passed**, **1 failed** (pre-existing).

---

## 16. Existing unrelated test failures

`PfmToolsTest` emergency-fund flow expects button copy `"Save result"` but EF UI shows `"Save Calculation"`. Documented earlier; not changed in Task 35. (MMP “New Test” label mismatch may also appear in broader runs — out of scope.)

---

## 17. Actual browser verification

**Method:** Cursor IDE browser vs local `http://127.0.0.1:8000` (not physical device).

| Check | Result |
| --- | --- |
| All seven inputs visible without Next | OK |
| No step nav / Continue | OK |
| Years ± sync | OK (10→11) |
| Calculate → results | OK (11y → ₹16,285 / month) |
| Draft values on form | OK |
| Compact header + plum/pearl | OK |
| Eyebrow “all inputs on one page” | OK |

---

## 18. Responsive verification

| Width | Method | Notes |
| --- | --- | --- |
| Desktop | Live browser | Two-column; all sections; Calculate |
| 390px | CDP mobile emulation | All fields + Calculate in a11y tree; vertical scroll |
| Other listed widths | CSS media rules | Existing ≤767 / ≤1023 breakpoints retained |

Physical-device testing not performed.

---

## 19. Before-and-after screenshots

`docs/audits/task-35-financial-goal-single-page/`:

| File | Content |
| --- | --- |
| `01-before-wizard-years-squeezed.png` | Pre-single-page (wizard era reference) |
| `02-after-single-page-header.png` | Single-page header / eyebrow |
| `03-after-single-page-years.png` | Full-width duration on continuous form |
| `04-after-results.png` | Results after single-page Calculate |
| `05-mobile-390-form.png` | Mobile viewport emulation |

---

## 20. Remaining limitations

- Long form still scrolls on small screens (intentional).
- Results screen structure unchanged (Task 36).
- Backend validation messages remain mostly bag-level (`calculate`), with optional field `@error` hooks ready.
- Physical devices not tested.

---

## 21. Confirmation: Tasks 31–34 remain functional

Regression suite green: compact header, brand tokens, years slider, formatting/section hierarchy.

---

## 22. Confirmation: other calculators unaffected

EF / Money Planner still use `data-pfm-wizard` + Continue (asserted). Shared `yuktione-pfm.js` untouched. FG-only CSS/JS scope retained.

---

**Task 35 complete. Stopped. Awaiting further instructions.**
