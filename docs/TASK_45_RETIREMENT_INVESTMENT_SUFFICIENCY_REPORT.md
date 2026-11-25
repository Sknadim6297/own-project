# Task 45 — Retirement Calculator: Monthly Investment Sufficiency

**Status:** Complete  
**Date:** 25 Nov 2026  
**Scope:** Local only — no push, deploy, or Task 46.

---

## 1. Existing retirement architecture

| Piece | Detail |
| --- | --- |
| Route | `tools.pfm.retirement` (+ calculate / results / save / history / recalculate / new-test) |
| Controller | `PfmToolController` (shared PFM flow) |
| Engine | `PfmCalculator::retirement` + `PfmMath` |
| Presentation | `RetirementPresentation` (Task 44 conclusion + Task 45 sufficiency view-model) |
| Form | Two-step wizard — `retirement-fields.blade.php` |
| Results | Shared `results.blade.php` / `show.blade.php` + metrics + readiness chart |
| Draft | Session `PfmDraft` — Calculate stores preview only |
| Save | Explicit Save → `PfmCalculation` JSON inputs/outputs |
| API | No dedicated Retirement HTTP API (Debt EMI API only). Web uses `PfmCalculator` directly. |

---

## 2. Exact existing input and output inventory

### Inputs (preserved)

| Field | Notes |
| --- | --- |
| `current_age` | 18–80 |
| `retirement_age` | > current age, ≤ 90 |
| `life_expectancy` | > retirement age, ≤ 110 (default 85) |
| `current_monthly_expenses` | Positive money |
| `current_assets` | ≥ 0 |
| `inflation_percent` | 0–50 (default 6) |
| `pre_retirement_return_percent` | 0–50 (default 10) |
| `post_retirement_return_percent` | 0–50 (default 6 on form) |

### New optional input

| Field | Notes |
| --- | --- |
| `planned_monthly_investment` | Optional INR money ≥ 0; omitted/blank → `null`; explicit `0` → `0.00` |

### Legacy outputs (unchanged meaning)

| Key | Meaning |
| --- | --- |
| `years_to_retire` | Retirement age − current age |
| `future_monthly_expense` | Expenses inflated to retirement |
| `required_corpus` | PV of post-retirement expense annuity |
| `future_value_assets` | Current assets grown to retirement (annual compound) |
| `funding_gap` | max(corpus − FV assets, 0) |
| `monthly_investment` | Required monthly PMT to fund the gap |
| `readiness_score` | % of corpus covered by projected assets |
| `summary` | Compact numeric summary |

### Additive Task 45 outputs

| Key | Meaning |
| --- | --- |
| `planned_monthly_provided` | Whether planned investment was supplied |
| `planned_monthly_investment` | Planned amount or `null` |
| `monthly_shortfall` / `monthly_surplus` | Contribution gap vs required |
| `contribution_coverage_percent` | `(M/R)×100` or `null` when R≈0 |
| `projected_corpus` | FV assets + FV of planned contributions |
| `corpus_shortfall` / `corpus_surplus` | Corpus gap at retirement |
| `investment_status` | `unavailable` / `insufficient` / `meets` / `above` / `zero_required` |

---

## 3. Planned investment input implementation

- Label: **Planned monthly investment (optional)**
- Helper: Enter how much you currently plan to invest each month towards your retirement.
- Placed on assumptions step (step 1) with existing field styling.
- Server validation via `optionalMoney()` — negatives/non-numeric/over-limit rejected; blank stays null.

---

## 4. Required versus planned investment semantics

- **Required (`monthly_investment`)**: engine-calculated contribution to close the funding gap.
- **Planned (`planned_monthly_investment`)**: user intent; never overwrites required.
- Missing ≠ zero. Explicit zero is assessed as planned ₹0.

---

## 5. Monthly contribution gap calculation

```
monthly_shortfall = max(R − M, 0)
monthly_surplus   = max(M − R, 0)
coverage_percent  = (M / R) × 100   // only when R > 0.005
```

Rounded with existing `PfmMath::roundMoney` / `round(..., 2)` for coverage.

---

## 6. Projected retirement corpus calculation

Reuses existing math only:

1. `future_value_assets` — same annual `PfmMath::compound` path as required PMT.
2. Contribution FV — `PfmMath::futureValuePlan(0, planned, preReturn, years)['fv_contributions']` (end-of-month ordinary annuity; same timing as `pmtForFutureValue`).
3. `projected_corpus = roundMoney(fvAssets + fv_contributions)`.

No second retirement engine. Zero return handled inside `futureValuePlan`.

---

## 7. Retirement corpus shortfall calculation

```
corpus_shortfall = max(required_corpus − projected_corpus, 0)
corpus_surplus   = max(projected_corpus − required_corpus, 0)
```

Both values are money at retirement (same horizon). Monthly contribution gap is shown separately from corpus gap.

---

## 8. Financial assumptions and contribution timing

| Assumption | Behavior |
| --- | --- |
| Inflation | Annual compound on expenses |
| Pre-retirement return | Annual compound on existing assets; monthly rate for PMT / contribution FV |
| Post-retirement return | Monthly rate for corpus PV annuity |
| Contribution timing | End of month |
| Accumulation | Years until retirement × 12 months |
| Rounding | `roundMoney` to 2 decimals |

---

## 9. Supported sufficiency scenarios

| Scenario | Status | Behavior |
| --- | --- | --- |
| A Insufficient | `insufficient` | Gap + optional corpus shortfall copy |
| B Exact | `meets` | Alignment explanation; no guarantee |
| C Above | `above` | Surplus + optional corpus surplus |
| D Explicit zero | Assessed as M=0 | Usually insufficient unless R≈0 |
| E Missing | `unavailable` | Prompt to enter planned; Task 44 summary intact |
| F Zero required | `zero_required` | No division-by-zero; coverage N/A |

---

## 10. Coverage visualization

- Lightweight progress bar (`.yk-rtsuf__bar`) clamped 0–100% visually.
- Actual percentage shown as text (may exceed 100%).
- Accessible `role="img"` + visually-hidden description.
- Labelled **Monthly contribution target coverage** (not retirement readiness).

---

## 11. Task 44 conclusion integration

Preserved: Your Retirement Summary, terminology, Next Steps, assumptions grid, disclaimer.  
Extended: sufficiency conclusion paragraph(s), planned-investment term, insufficiency next-steps, disclaimer comparison wording.  
Section order: metrics → charts → **Is Your Monthly Investment Sufficient?** → Your Retirement Summary.

---

## 12. Financial disclaimer

Extended to state that the assessment compares planned vs estimated required contribution under entered assumptions, and that estimated targets are not guarantees of retirement security.

---

## 13. Draft and Recalculate verification

| Flow | Result |
| --- | --- |
| Validation error | `withInput()` restores values including planned |
| Recalculate / `clearResult` | Restores normalized inputs including planned |
| New Test | Clears draft; planned blank again |
| Missing vs zero | Distinct in normalized + result |

---

## 14. Saved history compatibility

- New saves store planned + sufficiency keys in inputs/outputs JSON.
- Historical records without planned show Task 44 conclusion + **assessment unavailable**.
- No historical rewrite / recalculation of snapshots.

---

## 15. Existing API compatibility

- No Retirement-specific API surface to version.
- Omitting `planned_monthly_investment` keeps all legacy financial keys unchanged.
- Additive result keys only.

---

## 16. Existing financial output verification

For identical legacy inputs (no planned field), `required_corpus`, `future_value_assets`, `funding_gap`, `monthly_investment`, `future_monthly_expense`, `readiness_score`, and `years_to_retire` match pre-Task-45 engine values (covered by unit/feature assertions).

---

## 17. Modified files

| File | Change |
| --- | --- |
| `app/Services/Pfm/PfmCalculator.php` | `optionalMoney`, `retirementSufficiency`, additive result keys |
| `app/Support/Pfm/RetirementPresentation.php` | Sufficiency view-model + conclusion/next-step/disclaimer extensions |
| `resources/views/.../retirement-fields.blade.php` | Optional planned monthly field |
| `resources/views/.../retirement-sufficiency.blade.php` | **New** assessment section |
| `resources/views/.../results.blade.php` | Include sufficiency before conclusion |
| `resources/views/.../show.blade.php` | Include sufficiency before conclusion |
| `public/frontend/assets/css/yuktione-pfm.css` | `.yk-rtsuf*` styles |
| `tests/Feature/RetirementInvestmentSufficiencyTest.php` | **New** Task 45 suite |
| `docs/TASK_45_RETIREMENT_INVESTMENT_SUFFICIENCY_REPORT.md` | This report |
| `docs/audits/task-45-retirement-investment-sufficiency/*` | Screenshots |

---

## 18. Database changes

None. No migrations. Snapshot JSON only.

---

## 19. Automated test counts and assertions

| Suite | Result |
| --- | --- |
| `RetirementInvestmentSufficiencyTest` | **14 passed / 146 assertions** |
| `RetirementCalculatorConclusionTest` (Task 44) | **8 passed** (regression) |
| Combined RT 44+45 earlier run | 22 passed / 228 assertions |

---

## 20. Existing unrelated test failures

Documented known unrelated failures (not introduced by Task 45):

1. **`PfmToolsTest` Emergency Fund Save-label mismatch** — page shows `Save Calculation`; test expects `Save result`.
2. **`MonthlyMoneyPlannerNewTestTest`** — New Test action assertion (pre-existing / unrelated).
3. **`NineToolsCategoriesTest`** — catalogue category count/order (unrelated catalogue drift).

Retirement signed-in flow in `PfmToolsTest` **passed**. Debt EMI, FG, EF, Net Worth, Money Planner core suites remain green aside from the known items above.

---

## 21. Actual browser verification

Environment: local Laravel server `http://127.0.0.1:8001` (Cursor IDE browser). Synthetic data.

| Check | Outcome |
| --- | --- |
| Form shows planned field | Pass |
| Insufficient (M=10000, R=14114.98) | Gap ₹4,114.98; coverage 70.85%; corpus shortfall shown |
| Exact (M=R) | Meets; coverage 100% |
| Above (M=20000) | Surplus ₹5,885.02; coverage 141.69%; corpus surplus |
| Missing planned | Assessment unavailable; Task 44 summary intact |
| Guest Save | Sign-in CTA (no auto-save) |
| Recalculate / New Test | Verified in automated tests |
| JS errors / duplicate submit | No calculator JS errors observed during flows |

---

## 22. Responsive verification

| Width | Method | Notes |
| --- | --- | --- |
| 390px | Browser CDP device metrics (emulation) | Metrics stack to one column; values readable |
| Desktop (~1280+) | Actual browser viewport | Two-column metric grids |
| 360 / 430 / 768 / 1024 / 1440 | CSS media queries + prior Task 44 patterns | `.yk-rtsuf__metrics` collapses ≤768px |

Physical-device testing not performed (viewport emulation only where noted).

---

## 23. Accessibility verification

- Associated label on planned field + `aria-describedby` helper.
- Status chips use icon + text (not color alone).
- Coverage bar has accessible text alternatives.
- Heading hierarchy: h2 assessment → h3 monthly/corpus groups.
- Focusable controls inherit existing PFM focus styles.
- Help tab / WhatsApp floating UI did not clip financial values in checked viewports.

---

## 24. Before-and-after screenshots

Saved under `docs/audits/task-45-retirement-investment-sufficiency/`:

| File | Content |
| --- | --- |
| `rt45-01-input-form.png` | Planned monthly investment field |
| `rt45-02-insufficient-result.png` | Results hero (insufficient run) |
| `rt45-03-insufficient-assessment.png` | Coverage bar + corpus shortfall |
| `rt45-04-conclusion-integration.png` | Terminology including planned investment |
| `rt45-05-meets-target.png` | Exact target / 100% coverage |
| `rt45-06-above-target.png` | Above target + corpus surplus |
| `rt45-07-missing-assessment.png` | Assessment unavailable |
| `rt45-08-mobile-390.png` | Mobile monthly contribution metrics |

---

## 25. Remaining limitations

1. When planned equals required monthly, projected corpus may differ from required corpus by a few rupees due to `roundMoney` on intermediate contribution FV vs PMT inverse (monthly status still `meets`).
2. No dedicated Retirement mobile API; web/session only.
3. Guest cannot Save — history screenshot of authenticated Save covered by automated ownership tests.
4. Physical-device QA not run in this session.

---

## 26. Confirmation that previous tasks remain functional

- Task 44 conclusion suite: 8/8 passed.
- Task 44 UI (summary, terms, next steps, assumptions) still renders with or without planned investment.

---

## 27. Confirmation that other calculators remain unaffected

Feature assertions confirm Debt EMI and Financial Goal result views do not show the Retirement sufficiency section. Broader PFM regressions green aside from documented unrelated failures.

---

**Task 45 complete. Stopped. Awaiting further instructions (Task 46 not started).**
