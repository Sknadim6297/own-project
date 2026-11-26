# Task 48 — Savings & Investment Calculator: Investment Amount

**Status:** Complete (implemented and re-verified locally)  
**Date:** 25 Nov 2026 (implementation); re-verified 26 Nov 2026 after stash merge  
**Scope:** Investment amount UI only. No commit / push / deploy. Tasks 49–51 not started.

---

## 1. Actual implementation status

**IMPLEMENTED.** Form amount UX, type selector, quick amounts, contribution preview, CSS/JS, automated tests, browser verification, and this report are in place.

---

## 2. Modified files

| File | Change |
| --- | --- |
| `resources/views/frontend/sections/pfm/partials/savings-fields.blade.php` | Reworked Investment Amount section |
| `resources/views/frontend/sections/pfm/form.blade.php` | `yk-pfm--si` / `data-yk-si-form` on SI form |
| `public/frontend/assets/css/yuktione-pfm.css` | `.yk-siamt*` styles |
| `public/frontend/assets/js/yuktione-pfm.js` | `initSavingsInvestmentAmount` |
| `tests/Feature/SavingsInvestmentAmountTest.php` | **New** Task 48 suite |
| `docs/TASK_48_SAVINGS_INVESTMENT_AMOUNT_REPORT.md` | This report |
| `docs/audits/task-48-savings-investment-amount/*` | Screenshots |

**Unchanged (intentionally):** `PfmCalculator::savingsInvestment`, `PfmMath::futureValuePlan`, Task 47 presentation, API contracts, years field semantics, other calculators.

---

## 3. New investment amount UI

- Section heading **Investment amount** with helper copy.
- Visible **₹** prefixes on money inputs.
- Clear labels + help text for initial and monthly fields.
- Backend names preserved: `initial_amount`, `monthly_contribution`.
- Duration/return remain separate under **Duration and return** (years input not redesigned).

---

## 4. Investment type selector behaviour

Presentation-only radios (not a backend field):

| Type | Active field(s) | Inactive on submit |
| --- | --- | --- |
| Lump Sum | Initial | `monthly_contribution = 0` |
| Monthly Investment | Monthly | `initial_amount = 0` |
| Combined Investment | Both | Neither forced to zero |

- Inactive field panels are hidden.
- Previous non-zero values are retained in JS memory when switching types.
- Type is inferred from restored draft / `old()` values (`lump` / `monthly` / `combined`).
- Extra POST key `si_investment_type_ui` is ignored by the engine (verified: identical corpus/invested/gains).

**Validation conflict note:** Server still requires at least one of initial/monthly &gt; 0 (unchanged). Client mirrors that after interaction; no financial rule changes.

---

## 5. Quick amount buttons

Initial: ₹10,000 · ₹50,000 · ₹1L · ₹5L  
Monthly: ₹1,000 · ₹2,000 · ₹5,000 · ₹10,000  

All presets are within existing `MAX_AMOUNT` / non-negative money rules. Manual number entry remains available. Choosing a preset for a hidden field switches to Combined when needed.

---

## 6. Live preview

Contribution-only preview (no JS maturity/returns):

- Initial investment  
- Monthly contribution  
- Contribution months = `round(years × 12)` (same conversion as engine)  
- Planned total contributions = initial + monthly × months  

Explicit note: preview **excludes** estimated investment returns.

---

## 7. Validation verification

| Case | Result |
| --- | --- |
| Negative amounts | Rejected by server (`calculate` error) |
| Both zero | Rejected (“Enter an initial amount or a monthly contribution”) |
| Client empty lump/monthly after interaction | Accessible inline alert; submit blocked until fixed |
| Zero allowed on inactive field | Submitted as `0` |

---

## 8. Financial output verification

| Scenario | Inputs | Result |
| --- | --- | --- |
| A Lump | ₹1,00,000 + ₹0 | Invested ₹1,00,000; Task 47 lump wording |
| B Monthly | ₹0 + ₹5,000 × 10y | Invested ₹6,00,000 |
| C Combined baseline | ₹1,00,000 + ₹5,000 × 10y @ 10% | **Invested ₹7,00,000 · Corpus ₹12,94,929.04** |

Browser combined run and feature tests matched the engine baseline. Preview for Case C showed planned total ₹7,00,000.00 before Calculate.

---

## 9. Automated test results

`SavingsInvestmentAmountTest`: **9 passed / 95 assertions**

`SavingsInvestmentSummaryTest` (Task 47): **7 passed** (compatibility)

Focused regressions including `PfmCalculatorTest`, Debt explanatory, Retirement conclusion, Inflation graph + SI suites: **65 passed / 543 assertions**

Known unrelated failures (EF Save-label / MMP New Test / NineTools) were not touched.

---

## 10. Browser verification

Local server `http://127.0.0.1:8001` (Cursor IDE browser).

Verified:

- Amount UI + type cards + presets + preview  
- Combined calculate → Task 47 summary with ₹12,94,929.04 / ₹7,00,000  
- Lump preset ₹1L → monthly forced to 0 → invested ₹1,00,000 + lump conclusion  
- Type switching / hidden field exclusion  
- Guest Save label still present on results  

Physical device testing: not performed.

---

## 11. Responsive verification

| Width | Method | Notes |
| --- | --- | --- |
| 360 / 390 | CDP device metrics | Type cards stack; presets wrap |
| 430 / 768 | CSS media rules | Single-column grids |
| 1024+ | Desktop browser | Two-column amount grid + three type cards |

Horizontal overflow not observed on the amount card in tested viewports.

---

## 12. Screenshots

Under `docs/audits/task-48-savings-investment-amount/`:

| File | Content |
| --- | --- |
| `si48-00-hero-before-scroll.png` | Form hero context |
| `si48-01-amount-presets-desktop.png` | Presets + preview (combined) |
| `si48-02-results-task47-compatible.png` | Results after calculate |
| `si48-03-mobile-390-type-cards.png` | Mobile type cards |
| `si48-04-presets-and-preview.png` | Presets near preview |
| `si48-05-mobile-360-types.png` | 360px type stack |

Before Task 48 the form was a plain four-field grid without type selector, ₹ affixes, presets, or contribution preview (`savings-fields.blade.php` prior state).

---

## 13. Remaining limitations

- Years / frequency / maturity-return UX belong to Tasks 49–51 (not done).  
- Type selector is presentation-only (no persisted “investment type” column).  
- SI Recalculate still clears draft (`forget()`), same as before.  
- Contribution preview uses whole-rupee Indian grouping in JS display; server results still use engine rounding/format.

---

## 14. Task 47 compatibility

Results still render **Your Investment Summary / Conclusion / Assumptions / Disclaimer**. Scenario wording still tracks lump / monthly / combined. Feature tests assert Task 47 markers after amount flows. No changes to `SavingsInvestmentPresentation` or `savings-conclusion.blade.php` in this task.

---

## Stop

Task 48 complete. Awaiting further instructions (do not start Task 49 unless requested).
