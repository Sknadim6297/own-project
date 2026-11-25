# Task 47 — Savings & Investment Calculator: Improve Result Summary

**Status:** Complete  
**Date:** 25 Nov 2026  
**Scope:** Local only — presentation/summary improvements for Savings & Investment Calculator. No push, deploy, or Tasks 48–51 (amount / duration / frequency / maturity engine changes).

---

## 1. Existing Savings & Investment Calculator architecture

| Piece | Detail |
| --- | --- |
| Route | `tools.pfm.savings-investment` (+ calculate / results / save / history / show / recalculate / new-test) |
| Controller | Shared `PfmToolController` |
| Engine | `PfmCalculator::savingsInvestment` → `PfmMath::futureValuePlan` |
| Form | Single-page `savings-fields.blade.php` via `form.blade.php` |
| Results | Shared `results.blade.php` / `show.blade.php` + SI-specific conclusion include |
| Draft | Session `PfmDraft` (Calculate = preview only) |
| Save | Explicit Save → `PfmCalculation` JSON snapshot |
| Chart | Existing segmented “Investment growth mix” stack bar in `result-charts.blade.php` |
| Presentation (new) | `SavingsInvestmentPresentation` (view-model only; no formula changes) |

---

## 2. Exact existing input inventory

| Field name | UI label | Defaults / bounds | Notes |
| --- | --- | --- | --- |
| `initial_amount` | Initial investment (₹) | Default form `0`; ≥ 0 | Lump-sum / starting capital |
| `monthly_contribution` | Monthly investment (₹) | Default form `0`; ≥ 0 | Recurring monthly only |
| `years` | Years | Required; 0.01–100 | Duration |
| `expected_return_percent` | Expected annual return (%) | Default `10`; **0–50** | Negative rates rejected by `ratePercent` |

Validation rule: at least one of initial or monthly must be > 0.

---

## 3. Exact existing output inventory

| Key | Meaning |
| --- | --- |
| `corpus` | Estimated maturity amount (FV of initial + FV of contributions) |
| `invested` | Total contributions = initial + monthly × months |
| `gains` | corpus − invested |
| `fv_initial` | Grown value of the initial investment alone |
| `fv_contributions` | Grown value of the monthly contribution stream |
| `months` | `round(years × 12)` |
| `summary` | Compact string: `Corpus ₹… · Invested ₹… · Gains ₹…` |
| `tool_key` / `content_version` | Metadata |

Rounding: `PfmMath::roundMoney` on monetary outputs; rate stored to 4 decimal places.

---

## 4. Supported investment types

Documented from the **actual** engine (not assumed product catalogue):

| Scenario | Supported? | How |
| --- | --- | --- |
| Lump-sum only | Yes | `initial > 0`, `monthly = 0` |
| Recurring monthly only | Yes | `initial = 0`, `monthly > 0` |
| Combined initial + monthly | Yes | Both > 0 |
| SIP / quarterly / annual contribution frequencies | **No** | Frequency is fixed monthly |
| Negative expected return | **No** | Validation: rate must be 0–50 |
| Zero expected return | Yes | Rate = 0 → gains = 0; corpus = invested |

There is no separate “investment type” selector — type is inferred from contribution amounts.

---

## 5. Existing financial assumptions

| Assumption | Engine behaviour |
| --- | --- |
| Contribution frequency | Monthly only |
| Contribution timing | End-of-month ordinary annuity (`monthly × (((1+r)^n − 1) / r)`) |
| Compounding | Monthly: `r = annualReturn / 12` |
| Initial growth | `initial × (1+r)^months` |
| Invested total | Counts initial **once** + each monthly contribution (no double-count) |
| Taxes / fees / inflation | **Not** included |
| Guarantees | None — educational estimates |

---

## 6. Identified summary problems (before)

Reproduced on guest results (`http://127.0.0.1:8001/tools/pfm/savings-investment/results`):

1. Hero lead reused raw engine `summary` string (`Corpus · Invested · Gains`) without explanation.
2. No dedicated “Your Investment Summary” hierarchy or maturity KPI narrative.
3. No personalized investment conclusion from inputs/outputs.
4. Unclear distinction between invested capital and estimated returns in prose.
5. No listed assumptions (frequency vs compounding, timing).
6. Generic one-line disclaimer only (shared non-SI tools pattern).
7. Metric labels still used older “Corpus / Gains” terminology in places.
8. No next-steps guidance.

Before screenshot: `docs/audits/task-47-savings-investment-summary/si47-00-before-summary.png`

---

## 7. Improved result structure

Order on SI results / saved show:

1. Compact hero: “Your investment plan” + contribution/growth lead  
2. Primary metrics (estimated maturity KPI + invested + returns + FV contributions)  
3. Existing investment growth mix chart (labels clarified)  
4. **Your Investment Summary** (`savings-conclusion`)  
   - Primary estimated maturity KPI  
   - Metric grid (initial, monthly, frequency, duration, return, invested, gains, maturity)  
   - **Your Investment Conclusion** (dynamic paragraphs)  
   - Understanding the numbers (terminology)  
   - Next Steps  
   - Assumptions used  
   - Financial disclaimer  
5. Existing actions (Save / Edit & recalculate / New Test)

No promotional banner. Route unchanged.

---

## 8. Dynamic investment conclusion

`SavingsInvestmentPresentation::build()` generates paragraphs from validated inputs + engine outputs.

Combined example (₹1,00,000 + ₹5,000/mo, 10y, 10%):

- Plan sentence with initial, monthly, years, total invested ₹7,00,000.00  
- Growth sentence with 10%, maturity ₹12,94,929.04, returns ₹5,94,929.04  
- Split of FV initial ₹2,70,704.15 + FV contributions ₹10,24,224.89 (initial counted once in invested)  
- Key assumptions line  

Lump + 0% example (₹2,50,000, 8y, 0%):

- Lump-sum wording; no recurring claims  
- Explicit “no additional investment growth” sentence  
- Maturity = invested = ₹2,50,000.00  

Sparse historical snapshots: safe fallback paragraph; no invented amounts.

---

## 9. Supported investment scenarios (presentation)

| Key | Behaviour |
| --- | --- |
| A Lump-sum | Explains initial, duration, return, maturity, growth; omits recurring claims |
| B Recurring | Monthly amount, frequency, duration, total invested, maturity, growth |
| C Combined | Separates initial vs monthly; no double-count |
| D Zero return | Maturity = contributions; no growth under assumptions |
| E Negative return | Presentation-ready if gains &lt; 0; **engine cannot produce** (rate ≥ 0). Chart avoids misleading mix proportions |
| F No recurring | Same as lump-sum path |
| G Missing history | Unavailable labels + sparse fallback |

---

## 10. Investment breakdown

- Metric cards: maturity / invested / returns / FV contributions  
- Growth mix stack bar when corpus &gt; 0 and gains ≥ 0  
- Accessible text alternative under chart  
- Negative-gains path: text-only note (no pie proportions)

---

## 11. Financial terminology explanations

Provided in “Understanding the numbers”:

- Invested amount  
- Estimated returns  
- Estimated maturity amount  
- Expected annual return (annual assumption → monthly compounding)  
- Investment frequency (monthly model)

---

## 12. Next Steps section

Educational bullets only: review contributions/duration, revisit return assumptions, recalculate when circumstances change, consider a qualified professional. No product recommendations.

---

## 13. Financial disclaimer

SI-specific note:

> These investment projections are estimates based on the entered contribution amounts, investment duration, and expected return assumptions. Actual investment returns may vary, and the estimated maturity amount is not guaranteed. This model does not include taxes, fees, inflation adjustments, or product-specific charges unless you already reflected them in the amounts you entered. These figures are educational estimates only and are not financial advice.

---

## 14. Financial formula verification

Identical inputs → identical engine outputs before/after (presentation only).

Reference: ₹1,00,000 + ₹5,000/mo × 10y @ 10%:

| Output | Value |
| --- | --- |
| corpus | 1294929.04 |
| invested | 700000 |
| gains | 594929.04 |
| fv_initial | 270704.15 |
| fv_contributions | 1024224.89 |
| months | 120 |

Unit tests `si01` / `si02` and feature assertion `financial outputs unchanged from engine` remain green.

**No changes** to `futureValuePlan`, maturity, invested, gains, compounding, timing, rounding, validation, or API contracts.

---

## 15. Historical calculation compatibility

- `show.blade.php` includes the same SI conclusion for saved records  
- Presentation reads original snapshot inputs/outputs only  
- Sparse snapshots → Unavailable / fallback text  
- No DB rewrite, no auto-recalculate of history  
- IDs, timestamps, ownership preserved

---

## 16. Save, Recalculate, and History verification

| Flow | Result |
| --- | --- |
| Calculate | Preview only; does not write DB |
| Results | Improved summary renders |
| Save | Explicit; guest prompts sign-in |
| Recalculate | Existing SI behaviour: `forget()` draft → cleared form (documented; not changed) |
| New Test | Remains functional |
| History / Show Saved | Conclusion applies when snapshot sufficient |
| Auth / ownership | Covered by Task 47 feature tests |

---

## 17. Modified files (Task 47)

| File | Change |
| --- | --- |
| `app/Support/Pfm/SavingsInvestmentPresentation.php` | **New** presentation view-model |
| `resources/views/frontend/sections/pfm/partials/savings-conclusion.blade.php` | **New** summary UI |
| `resources/views/frontend/sections/pfm/results.blade.php` | SI hero + conclusion include |
| `resources/views/frontend/sections/pfm/show.blade.php` | SI saved conclusion include |
| `resources/views/frontend/sections/pfm/partials/result-metrics.blade.php` | SI null-safe maturity labels |
| `resources/views/frontend/sections/pfm/partials/result-charts.blade.php` | SI mix chart labels / negative-gains guard |
| `public/frontend/assets/css/yuktione-pfm.css` | `.yk-siconc*` + metric-note styles |
| `tests/Feature/SavingsInvestmentSummaryTest.php` | **New** Task 47 suite |
| `docs/TASK_47_SAVINGS_INVESTMENT_SUMMARY_REPORT.md` | This report |
| `docs/audits/task-47-savings-investment-summary/*` | Screenshots |

---

## 18. Database changes

**None.** No migrations. No historical record rewrites.

---

## 19. Automated test counts and assertions

| Suite | Result |
| --- | --- |
| `SavingsInvestmentSummaryTest` | **7 passed**, **70 assertions** |
| `PfmCalculatorTest` SI cases (`si01`, `si02`) | Passed |
| Focused SI + unit SI filter | **10 passed / 74 assertions** |

Coverage highlights:

1. Route + summary render with actual values  
2. Summary changes by scenario (lump / recurring / combined / zero)  
3. Engine corpus / invested / gains unchanged  
4. Frequency & duration described  
5. Zero-return safe wording  
6. No double-count messaging for combined  
7. Assumptions + disclaimer visible  
8. Sparse history fallback  
9. Calculate does not auto-save; Save + ownership  
10. Recalculate / New Test  
11. Guest preview; other calculators unaffected  

---

## 20. Existing unrelated test failures

Confirmed still pre-existing / out of SI Task 47 scope:

| Failure | Notes |
| --- | --- |
| `MonthlyMoneyPlannerNewTestTest` | MMP New Test assertion mismatch |
| `NineToolsCategoriesTest` | Catalogue empty-category `(0)` / nine-categories order |
| `PfmToolsTest` emergency-fund “Save result” vs “Save Calculation” label | EF Save-label mismatch |

Debt/EMI, Retirement, Inflation, Financial Goal, Net Worth suites in the filtered run remained green aside from the known failures above. Task 47 did not weaken or “fix” these unrelated tests.

---

## 21. Actual browser verification

Server: `http://127.0.0.1:8001` (PHP built-in, local).

Flows exercised:

1. Combined plan (₹1,00,000 + ₹5,000, 10y, 10%) → summary, conclusion, assumptions, disclaimer, actions  
2. Recalculate → cleared form (existing forget behaviour)  
3. Lump-sum + 0% (₹2,50,000, 8y, 0%) → lump wording + zero-growth sentence; maturity ₹2,50,000.00  
4. Guest preview Save label  
5. Amounts matched engine dump  

Method: Cursor IDE browser automation (not physical device). No JS console blockers observed for SI summary. Icons from existing `x-yk-icon` registry.

---

## 22. Responsive verification

| Width | Method | Notes |
| --- | --- | --- |
| 390px | CDP `Emulation.setDeviceMetricsOverride` | Metrics stack readable; no horizontal overflow observed on summary card |
| Desktop (~1280+) | Browser viewport | Summary KPI + conclusion readable |

Physical-device testing: **not** performed. Viewport emulation only for mobile.

---

## 23. Accessibility verification

- Heading hierarchy: H1 tool → H2 Investment Summary → H3 subsections  
- Conclusion list and disclaimer `role="note"`  
- Chart has text alternative / status when mix unavailable  
- Focusable Save / Recalculate / New Test controls  
- WhatsApp/help tab present; summary content not overlapped in tested viewports  
- Contrast uses existing plum / pearl tokens (`#8A0F4D`, `#FFF8FB`, `#F8EAF1`)

---

## 24. Before-and-after screenshots

Located under `docs/audits/task-47-savings-investment-summary/`:

| File | Content |
| --- | --- |
| `si47-00-before-summary.png` | Pre-fix sparse Corpus/Invested/Gains hero |
| `si47-01-improved-desktop-summary.png` | Improved maturity KPI summary |
| `si47-02-disclaimer-actions.png` | Disclaimer + Save / Recalculate / New Test |
| `si47-03-lump-zero-return.png` | Lump + zero-return maturity KPI |
| `si47-04-hero-desktop.png` | Updated hero lead |
| `si47-05-mobile-390-metrics.png` | 390px metrics / chart |
| `si47-06-dynamic-conclusion-lump-zero.png` | Dynamic conclusion paragraphs |

---

## 25. Remaining limitations

- Contribution frequency remains **monthly only** (Tasks 48–50 out of scope).  
- Negative annual return not accepted by validation (presentation guards exist; engine path unused).  
- SI Recalculate still clears inputs via `forget()` — documented, not redesigned.  
- Detailed maturity/return UX refinements reserved for Task 51.  
- Physical-device and full keyboard audit beyond spot-check not expanded.

---

## 26. Confirmation — previous tasks remain functional

Regression subset: Retirement conclusion/sufficiency, Inflation graph values, Financial Goal 31–36 suites (in broader filter), Debt/EMI tests, Net Worth, and SI unit maths — **passed**. Known MMP / NineTools / EF label failures unchanged and unrelated.

---

## 27. Confirmation — other calculators unaffected

SI conclusion gated by `$isSavings` / `toolKey === 'savings-investment-calculator'`. Guest-preview test asserts other tool result pages do not receive SI conclusion markup. No MMP / NW / EF / FG / Debt / Retirement / Inflation formula or API changes in Task 47.

---

## Stop

Task 47 complete. Awaiting further instructions (do not start Tasks 48–51).
