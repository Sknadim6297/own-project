# Task 51 — Savings & Investment Calculator: Maturity Amount & Returns

**Status:** Implemented and verified locally  
**Date:** 26 Nov 2026  
**Scope:** Result presentation and projection visualization only. No financial formula changes. No migrations. No Task 52.

---

## 1. Actual implementation status

Task 51 is **complete**.

Consolidated Savings & Investment results under **Your Investment Results** with:

- Primary Estimated Maturity Amount KPI
- Supporting invested / gains / duration / frequency / Estimated Total Gain (%) metrics
- Investment breakdown with capital vs grown-value labels
- Invested-vs-gains donut chart (server SVG)
- Dual-series investment growth chart (server SVG)
- Expandable year-wise projection table
- Investment plan details
- Improved Task 47 dynamic conclusion
- Assumptions + disclaimer preserved

Engine outputs (`corpus`, `invested`, `gains`, `fv_initial`, `fv_contributions`, `months`) remain authoritative. Frontend does not recalculate maturity.

---

## 2. Existing result architecture

| Layer | Role |
|---|---|
| `PfmToolController` | Calculate (preview), save, history, show |
| `PfmCalculator::savingsInvestment` | Authoritative maturity / invested / gains |
| `PfmMath::futureValuePlan` / `futureValuePlanPeriodic` | FV math (unchanged) |
| `SavingsInvestmentPresentation` | View model: money labels, donut, gain %, conclusion, plan meta |
| `SavingsInvestmentProjection` (**new**) | Month series + sampled chart points + year rows; snaps finals to engine |
| `savings-conclusion.blade.php` | Consolidated SI results UI |
| `results.blade.php` / `show.blade.php` | Skip generic PFM metrics/charts for SI; include SI conclusion block |
| `PfmDraft` / snapshots | Preview + saved historical records |

---

## 3. Identified presentation problems (pre-Task 51)

- Result content was fragmented across generic metrics, charts, and Task 47 conclusion.
- No dedicated maturity KPI hierarchy.
- No invested-vs-gains donut or dual-series growth visualization reconciled to engine finals.
- No expandable year-wise projection tied to the same model.
- `fv_initial` / `fv_contributions` risked being read as original capital without grown-value labels.
- Estimated Total Gain (%) was not explicitly separated from CAGR/XIRR.

---

## 4. Updated maturity KPI

Heading: **Your Investment Results**  
Primary KPI: **Estimated Maturity Amount** (`data-si="maturity"`)

Also shown:

- Total invested amount
- Estimated investment returns
- Investment duration
- Selected contribution frequency
- Estimated Total Gain (%)

---

## 5. Investment breakdown

Displays:

- Initial investment (contributed)
- Total recurring contributions (when applicable)
- Total invested amount
- Estimated gains
- Estimated maturity amount
- Grown value of initial investment (`fv_initial`)
- Grown value of recurring contributions (`fv_contributions`)

Help text clarifies grown values include investment growth.

---

## 6. Donut chart implementation

Server-built SVG donut in `SavingsInvestmentPresentation`:

- Segments: total invested vs estimated gains
- Legend with money + % of maturity
- Center: compact maturity amount
- Safe for zero gains / zero maturity / sparse history (`available: false`, no divide-by-zero)
- Accessible `aria-label` / textual legend

---

## 7. Growth chart implementation

`SavingsInvestmentProjection`:

- Monthly compounding, end-of-interval contributions
- Contributions only on completed frequency intervals
- Sampled display points for long durations
- Final invested/value snapped to engine `invested` / `corpus`
- Dual polylines + legends + `<title>` tooltips on points
- X duration labels, Y monetary ticks

---

## 8. Year-wise projection

Expandable `<details>` table (`data-yk-si-projection-table`):

- Start + whole years + final partial duration when needed
- Columns: period, total invested, estimated value, estimated gains
- Same series as chart; collapsed by default for large tables
- Verified Case A: Start … Year 10 ends at ₹700,000.00 / ₹1,294,929.04 / ₹594,929.04

---

## 9. Investment plan details

Shows type, initial, contribution amount/frequency, contributions per year, scheduled contribution count, duration, effective months, expected return, compounding, contribution timing.

Lump-sum path hides irrelevant recurring fields.

Legacy snapshots without frequency default to monthly interpretation.

---

## 10. Estimated Total Gain (%)

`gains / invested × 100` when invested > 0.

Label: **Estimated Total Gain (%)**  
Explicit note: not annual return, CAGR, or XIRR.

Case A: **84.99%**. Zero invested handled safely.

---

## 11. Dynamic conclusion

Preserved and improved Task 47 narrative using actual calculated values, including maturity line e.g.:

> With a total investment of ₹700,000.00 over 10 years, your estimated maturity amount is ₹1,294,929.04, including estimated gains of ₹594,929.04.

Supports lump / recurring / combined / all four frequencies / zero return / partial periods. Does not claim guaranteed returns. Assumptions + disclaimer retained.

---

## 12. Financial verification table

| Case | Setup | Contribs | Invested | Maturity | Gains | Result |
|---|---|---:|---:|---:|---:|---|
| A Monthly combined | 1L + 5k/mo, 10y, 10% | 120 | 700,000.00 | 1,294,929.04 | 594,929.04 | PASS |
| B Quarterly | 1L + 5k/qtr, 10y, 10% | 40 | 300,000.00 | 609,283.12 | 309,283.12 | PASS |
| C Half-yearly | 1L + 5k/half, 10y, 10% | 20 | 200,000.00 | 437,886.39 | 237,886.39 | PASS |
| D Yearly | 1L + 5k/yr, 10y, 10% | 10 | 150,000.00 | 352,214.58 | 202,214.58 | PASS |
| E Lump sum | 1L only, 10y, 10% | 0 | 100,000.00 | 270,704.15 | 170,704.15 | PASS |
| F Zero return | Case A @ 0% | 120 | 700,000.00 | 700,000.00 | 0.00 | PASS |
| G 1.5y yearly | Combined yearly | 1 | 105,000.00 | 121,366.50 | 16,366.50 | PASS |
| H Short quarterly | 0.1y quarterly | 0 | 100,000.00 | 100,833.33 | 833.33 | PASS |

Chart finals snapped to engine for all cases above.

---

## 13. Historical snapshot compatibility

- Legacy monthly snapshots without frequency → monthly interpretation
- Sparse outputs → donut/projection unavailable; safe fallback copy; no invented values
- Saved history show uses original snapshots (not recomputed)
- Covered in `SavingsInvestmentMaturityReturnsTest` Case I + save/ownership tests

---

## 14. API compatibility

No calculator API contract changes. Existing calculate/save/history/show routes and payload fields preserved (`contribution_amount`, `investment_frequency`, legacy `monthly_contribution`).

---

## 15. Modified / added files (Task 51 focus)

**Added**

- `app/Support/Pfm/SavingsInvestmentProjection.php`
- `tests/Feature/SavingsInvestmentMaturityReturnsTest.php`
- `docs/TASK_51_SAVINGS_INVESTMENT_MATURITY_RETURNS_REPORT.md`
- `docs/audits/task-51-savings-investment-maturity-returns/*`

**Updated for Task 51 presentation**

- `app/Support/Pfm/SavingsInvestmentPresentation.php`
- `resources/views/frontend/sections/pfm/partials/savings-conclusion.blade.php`
- `resources/views/frontend/sections/pfm/results.blade.php`
- `resources/views/frontend/sections/pfm/show.blade.php`
- `public/frontend/assets/css/yuktione-pfm.css`
- `tests/Feature/SavingsInvestmentSummaryTest.php` (heading “Your Investment Results”)

Task 48–50 files remain in the working tree from prior completed tasks (`PfmCalculator`, `PfmMath`, `savings-fields`, frequency/time-period tests, etc.). Task 51 did **not** alter FV formulas.

---

## 16. Database changes

**None.** No migrations. No historical rewrite.

---

## 17. Automated test results

```
PASS  SavingsInvestmentSummaryTest
PASS  SavingsInvestmentAmountTest
PASS  SavingsInvestmentTimePeriodTest
PASS  SavingsInvestmentFrequencyTest
PASS  SavingsInvestmentMaturityReturnsTest
PASS  PfmCalculatorTest
```

Focused SI + calculator suite: **69 passed**.

Broader PFM-oriented filter also exercised other calculators. Documented **unrelated** existing failure:

- `ToolsCompactHeaderTest` — expects `data-yk-icon="calculator"` on a tools compact header path; **pre-existing / unrelated** to SI maturity presentation. Not weakened.

---

## 18. Actual browser verification

Local workflow exercised on `http://127.0.0.1:8001`:

Investment Amount → Type → Frequency → Duration → Expected Return → Calculate → Results.

Verified in browser:

| Scenario | Maturity KPI | Notes |
|---|---|---|
| Monthly Case A | ₹1,294,929.04 | KPI, breakdown, donut, growth, year table, conclusion |
| Quarterly Case B | ₹609,283.12 | Earlier session capture |
| Half-yearly | ₹437,886.39 | Via authenticated session POST |
| Yearly | ₹352,214.58 | Via session POST |
| Zero return | ₹700,000.00 / gains ₹0 | Donut 100% invested |
| Lump sum | ₹270,704.15 | Recurring details excluded in conclusion/plan |

Actions present: Save (sign-in gated for guest), Edit & recalculate, New Test. Calculate remains preview-only (asserted in tests). Guest browser save/history UI not fully signed-in; automated tests cover explicit save + ownership.

---

## 19. Responsive and accessibility verification

Overflow checks (`scrollWidth > clientWidth + 2`):

| Width | Overflow |
|---:|---|
| 360 | none |
| 390 | none |
| 430 | none |
| 768 | none |
| 1024 | none |
| 1280 | none |
| 1440 | none |

Also verified:

- Regions / headings / legends / SVG `aria-label` / `<title>` tooltips
- Expandable year projection keyboard-focusable summary
- No console SI JS errors observed during verification
- Branding plum `#8A0F4D`, pearl `#FFF8FB`, soft plum `#F8EAF1`
- Existing SVG icon registry only

---

## 20. Screenshots

Directory: `docs/audits/task-51-savings-investment-maturity-returns/`

| File | Content |
|---|---|
| `si51-01-desktop-results-quarterly.png` | Prior/quarterly results viewport |
| `si51-02-donut-and-growth.png` | Breakdown / chart area (quarterly) |
| `si51-03-mobile-results-390.png` | Mobile 390 |
| `si51-09-clip-results.png` / `si51-10-kpi-desktop.png` | Monthly KPI (Case A) |
| `si51-11-breakdown.png` | Investment breakdown |
| `si51-12-growth-chart.png` | Donut + growth chart |
| `si51-13-yearwise-table.png` | Year-wise projection |
| `si51-14-zero-return.png` | Zero-return results |
| `si51-15-lump-sum.png` | Lump-sum results |
| `si51-16-mobile-360-lump.png` | Mobile 360 lump |

---

## 21. Remaining limitations

1. **SI Recalculate draft-clearing** (documented since earlier tasks) remains; not changed in Task 51.
2. Guest Save requires authentication; browser history/show-saved path covered primarily by feature tests when signed in.
3. Growth chart samples intermediate points for long horizons; financial model and final snap remain authoritative.
4. Unrelated `ToolsCompactHeaderTest` failure remains outside SI scope.

---

## 22. Final status of Tasks 47–51

| Task | Topic | Status |
|---:|---|---|
| 47 | Dynamic investment summary | Complete |
| 48 | Investment amount & type | Complete |
| 49 | Duration slider / presets / timeline | Complete |
| 50 | Contribution frequencies | Complete |
| 51 | Maturity amount & returns presentation | **Complete** |

---

## 23. Other calculators unaffected

Inflation and other PFM calculators remain free of SI result markers (`data-yk-si-results`, year-wise investment projection). Asserted in `SavingsInvestmentMaturityReturnsTest` and prior SI suite guards. FV helpers used only through existing SI paths; no silent formula edits.

---

**End of Task 51 report.** Local only — no commit, push, or deploy.
