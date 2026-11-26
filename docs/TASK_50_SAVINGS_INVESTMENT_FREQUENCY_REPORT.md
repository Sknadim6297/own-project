# Task 50 — Savings & Investment Calculator: Investment Frequency

**Status:** Complete (implemented and verified locally)  
**Date:** 26 Nov 2026  
**Scope:** Frequency-aware recurring contributions for Savings & Investment only. No commit / push / deploy. Task 51 not started.

---

## 1. Actual implementation status

**IMPLEMENTED.** Frequency selector, amount/duration/preview integration, frequency-aware engine path with exact monthly legacy compatibility, Task 47 summary wording updates, automated tests, browser verification, and this report.

---

## 2. Existing architecture

Monthly-only assumptions found before Task 50:

| Area | Assumption |
| --- | --- |
| `PfmCalculator::savingsInvestment` | Always read `monthly_contribution` and called `PfmMath::futureValuePlan` |
| `PfmMath::futureValuePlan` | End-of-month deposits every month; monthly compounding |
| Task 48 UI | “Monthly Investment” type + monthly labels/preview |
| Task 49 timeline | “monthly contribution periods” |
| Task 47 presentation | Hard-coded monthly wording / frequency label |

`PfmMath::futureValuePlan` is shared with Retirement sufficiency. Task 50 adds `futureValuePlanPeriodic` and keeps interval `1` delegated to `futureValuePlan` so Retirement/FG semantics stay untouched.

---

## 3. Supported investment frequencies

| Frequency | Interval (months) | Contributions / year |
| --- | ---: | ---: |
| `monthly` | 1 | 12 |
| `quarterly` | 3 | 4 |
| `half_yearly` | 6 | 2 |
| `yearly` | 12 | 1 |

Default when absent: **monthly**.

---

## 4. Request field compatibility

| Field | Role |
| --- | --- |
| `investment_frequency` | Optional; defaults to `monthly`; invalid values rejected |
| `contribution_amount` | Preferred per-period contribution amount |
| `monthly_contribution` | Legacy alias; still accepted alone |
| Both amount fields | Allowed only when values match (±₹0.005); otherwise rejected |

Existing clients need not send `investment_frequency` or `contribution_amount`.

---

## 5. Frequency selector implementation

Accessible radio cards under Investment amount (`.yk-sifreq`):

- Monthly / Quarterly / Half-Yearly / Yearly with interval hints  
- Hidden for Lump Sum  
- Does **not** convert the entered contribution amount when switching  

---

## 6. Investment amount integration

- Type label renamed to **Recurring Investment** (internal radio value remains `monthly`)  
- Dynamic amount label/help by frequency  
- Visible field: `contribution_amount`; hidden synced `monthly_contribution` alias  
- Preview shows frequency, per-contribution amount, contributions/year, scheduled count, recurring total, total invested  

---

## 7. Investment duration integration

Task 49 controls update frequency-aware period counts (`floor(N / k)`). Short durations with no complete interval show an explicit “no complete … period” message. No prorated payments.

---

## 8. Financial formula and contribution timing

- `N = round(years × 12)`  
- `r = annualReturn / 12` (monthly compounding)  
- `k` = contribution interval months  
- `n = floor(N / k)`  
- Contributions at months `k, 2k, …, n·k` (end of each completed interval)  
- `FV_initial = P × (1+r)^N`  
- `FV_contributions = Σ C × (1+r)^(N − j·k)` for `j = 1..n`  
- `invested = P + C·n`  
- Monthly (`k = 1`) uses the existing `futureValuePlan` path exactly  

---

## 9. Partial-period handling

Example: 18 months, yearly, ₹12,000 → exactly **1** contribution at month 12; no second contribution at month 18. Durations shorter than `k` schedule **0** recurring contributions. No prorating; duration is not extended.

---

## 10. Financial verification table

Base: P = ₹1,00,000, C = ₹5,000, 10 years, 10% (unless noted).

| Case | Frequency | Count | Recurring | Invested | Corpus |
| --- | --- | ---: | ---: | ---: | ---: |
| A legacy / explicit monthly | monthly | 120 | 6,00,000 | 7,00,000 | **12,94,929.04** |
| B | quarterly | 40 | 2,00,000 | 3,00,000 | **6,09,283.12** |
| C | half_yearly | 20 | 1,00,000 | 2,00,000 | **4,37,886.39** |
| D | yearly | 10 | 50,000 | 1,50,000 | **3,52,214.58** |
| E | all four @ 0% | — | — | corpus = invested | — |
| F lump sum | any | 0 | 0 | 1,00,000 | unchanged across frequencies |
| G | yearly, 1.5y, C=12,000 | 1 | 12,000 | 1,12,000 | verified |
| H | yearly, 0.5y | 0 | 0 | initial only | verified |

---

## 11. Legacy monthly baseline verification

Legacy request without frequency and explicit `investment_frequency=monthly` both reproduce:

- Count 120  
- Invested ₹7,00,000  
- Corpus ₹12,94,929.04  

`futureValuePlanPeriodic(..., interval=1)` returns the identical array as `futureValuePlan`.

---

## 12. Task 47 summary compatibility

`SavingsInvestmentPresentation` now:

- Defaults missing frequency to monthly (historical snapshots)  
- Uses frequency-aware amount labels and conclusion wording (“every three months”, etc.)  
- States end-of-period contributions + monthly compounding  
- Avoids monthly-only wording for non-monthly results  

---

## 13. Historical data compatibility

- No migrations  
- Old JSON without frequency treated as monthly  
- Saved outputs/inputs/IDs/timestamps/ownership unchanged  
- New saves store `investment_frequency` + `contribution_amount` (+ legacy alias)  

---

## 14. API compatibility

- Existing output keys preserved (`corpus`, `invested`, `gains`, `fv_*`, `months`, `summary`)  
- Additive keys: `investment_frequency`, `contribution_amount`, `contribution_count`, `contribution_interval_months`  
- Legacy monthly POST bodies still work  

---

## 15. Modified files

| File | Change |
| --- | --- |
| `app/Services/Pfm/PfmMath.php` | `futureValuePlanPeriodic`; monthly path adds count metadata |
| `app/Services/Pfm/PfmCalculator.php` | Frequency + contribution resolution; SI-only wiring |
| `app/Support/Pfm/SavingsInvestmentPresentation.php` | Frequency-aware summary |
| `resources/views/.../savings-fields.blade.php` | Frequency UI + amount/preview |
| `resources/views/.../savings-conclusion.blade.php` | Frequency labels / counts |
| `public/frontend/assets/css/yuktione-pfm.css` | `.yk-sifreq*` |
| `public/frontend/assets/js/yuktione-pfm.js` | Frequency sync + preview |
| `tests/Feature/SavingsInvestmentFrequencyTest.php` | **New** |
| `tests/Feature/SavingsInvestmentAmountTest.php` | Label/field assertions |
| `tests/Feature/SavingsInvestmentSummaryTest.php` | Lump wording |
| `docs/TASK_50_SAVINGS_INVESTMENT_FREQUENCY_REPORT.md` | This report |
| `docs/audits/task-50-savings-investment-frequency/*` | Screenshots |

---

## 16. Database changes

**None.** JSON storage only.

---

## 17. Automated test results

```text
php artisan test --filter="SavingsInvestmentFrequencyTest|SavingsInvestmentAmountTest|SavingsInvestmentSummaryTest|SavingsInvestmentTimePeriodTest|PfmCalculatorTest|FinancialGoalYearsSliderTest|RetirementInvestmentSufficiencyTest"
```

- First full run: **79 passed, 1 failed** (lump `contribution_count` assertion) → fixed  
- Frequency suite re-run: **14 passed**  
- Related Task 47/48/49, FG slider, Retirement sufficiency, and `PfmCalculatorTest` passed in the combined run  

Known unrelated failures elsewhere were not weakened.

---

## 18. Actual browser verification

Local SI form/results verified:

- Frequency cards (monthly → quarterly)  
- Label updates to “Quarterly investment” without converting ₹5,000  
- Preview: 40 periods, recurring ₹2,00,000, total ₹3,00,000  
- Duration label: “40 quarterly contribution periods”  
- Lump Sum hides frequency controls  
- Calculate → summary “every three months”, corpus ₹609,283.12, invested ₹300,000.00  
- Draft restoration keeps quarterly  

---

## 19. Responsive and accessibility verification

- 390px: no horizontal overflow; frequency cards stack via CSS breakpoints  
- Radio cards are keyboard-focusable with visible focus styles  
- Help chrome did not block frequency controls in verified viewports  

Widths reviewed: 360 / 390 / 430 / 768 / 1024 / 1280 / 1440 (CSS + sampled live 390).

---

## 20. Screenshots

`docs/audits/task-50-savings-investment-frequency/`:

- `si50-00-before-related.png` / `si50-00b-before-related.png`  
- `si50-01-frequency-quarterly-preview.png`  
- `si50-02-results-quarterly-summary.png`  
- `si50-03-mobile-390-header.png`  
- `si50-04-desktop-frequency-restored.png` (if captured in browser screenshots folder)

---

## 21. Remaining limitations and business assumptions

1. Contribution frequency ≠ compounding frequency (compounding stays monthly).  
2. Incomplete final intervals never receive a prorated contribution.  
3. Switching frequency never auto-converts the rupee amount.  
4. Recalculate still clears the SI draft (Task 47 limitation).  
5. Task **51** result redesign not implemented.  
6. Internal investment-type radio value remains `monthly` for recurring (display label is Recurring Investment).

---

## 22. Other calculators unaffected

Retirement still uses `futureValuePlan` monthly semantics (verified by `RetirementInvestmentSufficiencyTest`). Inflation / Financial Goal pages do not render SI frequency controls (asserted in Task 50 tests).
