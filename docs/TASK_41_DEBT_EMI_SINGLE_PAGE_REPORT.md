# Task 41 — Debt & EMI Calculator: All Inputs on One Screen

**Status:** Complete  
**Date:** 25 Nov 2026  
**Scope:** Local only — no push, deploy, or Tasks 42/43.

---

## 1. Previous two-step architecture

| Step | Label | Fields |
| --- | --- | --- |
| 0 | Loan details | `principal`, `annual_rate_percent`, `tenure_years` |
| 1 | Extra payment | `extra_payment` (optional) |

Mechanics: shared `form.blade.php` set `steps => ['Loan details', 'Extra payment']` → `data-pfm-wizard="1"`, `[data-pfm-step]` + `hidden`, Continue / Back, Calculate hidden until last step. Wizard driven by shared `yuktione-pfm.js` `initWizard()`.

Pre-impl audit: `docs/audits/task-41-debt-emi-single-page/AUDIT.md`.

---

## 2. Exact four-field inventory

| Name | Control | Default | Required | HTML bounds |
| --- | --- | --- | --- | --- |
| `principal` | number + ₹ affix | empty | yes | min 0.01 |
| `annual_rate_percent` | number + % affix | empty | yes | 0–50, step 0.01 |
| `tenure_years` | number + years affix | empty | yes | 0.01–100, step 0.01 |
| `extra_payment` | number + ₹ affix | `0` | optional | min 0 |

Backend tenure (`positiveFloat`): greater than 0 and ≤ 100 (aligned with HTML min 0.01).

---

## 3. New single-page form architecture

- `form.blade.php`: debt `steps => []` (wizard off); `yk-pfm--debt` + `data-yk-debt-single`; eyebrow **EMI planner · all inputs on one page**; primary button **Calculate EMI**.
- `debt-fields.blade.php`: continuous sections **Loan details** + **Additional repayment**; marker `data-yk-debt-single="1"`; no `data-pfm-step`.
- POST `.calculate` → unchanged `PfmCalculator::debtEmi` / `PfmMath::emiPlan` → existing `.results`.

Reference pattern: Financial Goal Task 35 (clear steps; leave shared wizard JS intact).

---

## 4. Removed wizard components

- Step nav / “Calculator steps”.
- `data-pfm-wizard`, `data-pfm-step`, `data-pfm-next`, `data-pfm-prev`.
- Continue / wizard Back.
- Per-step `hidden` panels.
- Eyebrow “2 quick steps”.

**Not removed:** page-level Back to Tools, shared wizard JS for other tools, Calculate / history links.

---

## 5. Input grouping and formatting

### Sections
- **A — Loan details:** principal, rate, tenure (desktop grid; mobile stacks).
- **B — Additional repayment:** optional extra monthly payment + behaviour copy.
- **C — Actions:** Calculate EMI (+ View History when authenticated).

### Affixes (presentation-only)
Scoped `.yk-debt__control` with ₹ / % / years — submitted values remain bare numbers.

**Note on Task 40:** Brief assumed Task 40 (`.yk-debt__control`, structure tests) was already shipped. Those artifacts were **not** present in this workspace; Task 41 applied the described presentation while converting to a single page. No separate Task 40 test suite was invented.

---

## 6. Unified validation

- Backend validation unchanged (`withInput` + errors on form).
- No step gating; invalid tenure keeps the user on the single-page form with restored values.
- Browser: `Tenure must be between 0 and 100.` with principal `1500000`, rate `8.5`, extra `2500` restored; `wizard=false`.

---

## 7. Draft compatibility

- Session drafts remain field-keyed; `$old = old() ?: draft->normalized()`.
- Prior two-step drafts restore into the single form.
- Recalculate / New Test behaviour unchanged for Debt (still uses shared paths; Debt is not on the EF/FG `clearResult` list).
- Other PFM drafts isolated (asserted).

---

## 8. Financial formula verification

No changes to `PfmCalculator::debtEmi` or `PfmMath::emiPlan`.

Task 41 feature test compares draft result to calculator engine for identical payload (`emi`, `interest_saved`, `months_saved`, `standard_interest`, `accelerated_interest`).

Browser synthetic case (₹20,00,000 / 9% / 15y / ₹5,000 extra): **EMI ₹20,285.33 · Interest saved ₹607,406.12 · Months saved 59**.

---

## 9. Save and history verification

- Calculate does not auto-save (asserted).
- Explicit Save + history show + ownership 403 covered in `DebtEmiSinglePageFormTest`.
- Result screen UI unchanged (Task 43 deferred). Guest sees **Save result (sign in)**.

---

## 10. JavaScript cleanup

- Debt markup no longer sets `data-pfm-wizard` → shared `initWizard` does not activate.
- `yuktione-pfm.js` **unchanged** (EF / MMP / Net Worth / Retirement keep wizards).
- No Debt-specific wizard JS existed to delete.

---

## 11. Modified files

| File | Change |
| --- | --- |
| `resources/views/frontend/sections/pfm/form.blade.php` | Debt steps cleared; debt shell/eyebrow/Calculate EMI |
| `resources/views/frontend/sections/pfm/partials/debt-fields.blade.php` | Single-page sections + affixes |
| `public/frontend/assets/css/yuktione-pfm.css` | Scoped `.yk-pfm--debt` / `.yk-debt__*` |
| `tests/Feature/DebtEmiSinglePageFormTest.php` | **New** Task 41 regression |
| `docs/audits/task-41-debt-emi-single-page/*` | Audit + screenshots |
| `docs/TASK_41_DEBT_EMI_SINGLE_PAGE_REPORT.md` | This report |

---

## 12. Database changes

**None.** No migrations. Historical records unchanged.

---

## 13. Automated test counts and assertions

| Suite | Result |
| --- | --- |
| `DebtEmiSinglePageFormTest` (Task 41) | **6 passed, 87 assertions** |
| `FinancialGoal*` + `PfmCalculatorTest` + `ApiV1DebtEmi` (combined filter run) | **54 passed, 465 assertions** |
| Broader Debt/PFM/FG/EF/MMP/NW/API filter | **170 passed**, **2 failed** (unrelated — see §14) |
| Isolated `PfmToolsTest` | **1 failed, 18 passed (191 assertions)** |

---

## 14. Existing unrelated test failures

### `PfmToolsTest` — Save button label mismatch (pre-existing)

- `tests/Feature/PfmToolsTest.php:193` expects EF (and non-MMP tools) to show `Save result`, while MMP expects `Save Calculation`.
- Observed mismatch remains on Emergency Fund / MMP labelling.
- **Not fixed in Task 41.** Debt EMI Save label behaviour unchanged on the existing results template.

---

## 15. Actual browser verification

**Method:** Cursor IDE browser → `http://127.0.0.1:8001` (PHP built-in server + local MySQL).  
**Not** physical-device testing.

Verified:

1. Fresh Debt & EMI form — all four inputs visible; no Continue / wizard steps.
2. Affixed ₹ / % / years controls.
3. Fill principal 2000000, rate 9, tenure 15, extra 5000 → **Calculate EMI** → results (EMI ₹20,285.33…).
4. Validation: tenure `0` → error on single page; values restored; no wizard.
5. Guest save CTA present on results.

Save → History authenticated click-path covered by automated tests; browser session remained guest for most captures.

---

## 16. Responsive verification

| Viewport | Method |
| --- | --- |
| Desktop (~1280+) | Actual browser interaction |
| 390×844 | Device metrics override (mobile form) |
| 360 / 430 / 768 / 1024 / 1440 | CSS stack rules for `.yk-debt__grid` / full-width actions |

No horizontal overflow observed on captured mobile/desktop views. Requirement is one continuous page (vertical scroll OK).

**Distinction:** Viewport emulation ≠ physical-device lab testing.

---

## 17. Accessibility verification

- Semantic section headings (Loan details / Additional repayment).
- Associated labels + `aria-describedby` helper text.
- Affixes `aria-hidden`; meaning conveyed in labels/help.
- Calculate EMI always visible (no step unlock).
- Keyboard tab order through continuous form.
- Existing focus ring on `.yk-debt__control:focus-within`.

---

## 18. Before-and-after screenshots

Under `docs/audits/task-41-debt-emi-single-page/`:

| File | Content |
| --- | --- |
| `01-before-two-step-wizard-reference.png` | Prior themed form reference (QA archive) |
| `01b-before-form-1440.png` | Prior form 1440 capture |
| `02-desktop-single-page-loan-fields.png` | New desktop loan fields + affixes |
| `03-desktop-calculate-emi-action.png` | Calculate EMI action |
| `04-existing-result-screen.png` | Unchanged results screen |
| `05-validation-error-state.png` | Validation + restored values |
| `06-mobile-single-page-form.png` | Mobile (390px emulation) |

---

## 19. Remaining limitations

- Physical-device QA not run.
- Authenticated Save → History browser walkthrough deferred to automated coverage (guest browser session).
- Task 40 structure test file was never in-repo; presentation applied here as specified.
- Shared PFM results template not redesigned (Task 43).
- Down payment not added (Task 42).

---

## 20. Confirmation — previous tasks remain functional

| Area | Confirmation |
| --- | --- |
| Financial Goal 31–36 | FG single-page + result tests still green in combined run |
| EF / MMP wizards | Still `data-pfm-wizard` + Continue (asserted) |
| Debt formulas | Unchanged unit/API behaviour |
| Task 40-style affixes | Present on single-page Debt form |

---

## 21. Confirmation — other calculators remain unaffected

- Shared `yuktione-pfm.js` untouched.
- Only Debt map entry cleared `steps`.
- Scoped CSS under `.yk-pfm--debt` / `.yk-debt`.
- EF / MMP / FG markers asserted in Task 41 tests.

---

## Stop

Task 41 complete. Awaiting further instructions. Do **not** start Task 42 or Task 43.
