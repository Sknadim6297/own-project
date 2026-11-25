# Task 46 — Inflation Calculator: Fix Missing Graph Values

**Status:** Complete  
**Date:** 25 Nov 2026  
**Scope:** Local only — no push, deploy, or Tasks 47–51.

---

## 1. Existing Inflation Calculator architecture

| Piece | Detail |
| --- | --- |
| Route | `tools.pfm.inflation` (+ calculate / results / save / history / recalculate / new-test) |
| Controller | Shared `PfmToolController` |
| Engine | `PfmCalculator::inflation` → `PfmMath::compound` |
| Form | Single-page `inflation-fields.blade.php` |
| Results | Shared `results.blade.php` / `show.blade.php` + metrics + charts |
| Draft | Session `PfmDraft` (Calculate = preview only) |
| Save | Explicit Save → `PfmCalculation` JSON |
| Chart (before) | Static SVG two-bar “Today / Future” in `result-charts.blade.php` |
| JS | No inflation-specific chart JS |

---

## 2. Exact input and output inventory

### Inputs

| Field | Bounds / notes |
| --- | --- |
| `current_amount` | ≥ 0 INR money |
| `inflation_percent` | 0–50 (% / year), default 6 |
| `years` | 0–100, decimals allowed |

### Outputs (unchanged)

| Key | Meaning |
| --- | --- |
| `future_cost` | Present × (1 + r)^years |
| `purchasing_power` | Present / (1 + r)^years |
| `increase` | future_cost − present |
| `summary` | Compact text summary |

---

## 3. Existing financial formula

Annual compound future cost (matches engine):

`Future cost = Present cost × (1 + annual inflation rate)^years`

Purchasing power is also computed, but the graph models **future cost over time**, not purchasing-power decay.

---

## 4. Confirmed root cause of missing graph values

Reproduced on `http://127.0.0.1:8001/tools/pfm/inflation/results` with ₹1,00,000 @ 6% for 10 years.

**Root cause:** The inflation chart SVG rendered only category labels `Today` / `Future` on two bars. It did **not** place financial value labels on the graph, had **no Y-axis currency ticks**, and showed **no year-wise intermediate points**. Amounts appeared only in a small note under the chart (`Today ₹… · Future ₹…`), so the graph itself looked empty of financial values.

Not caused by: missing engine outputs, JS init failure, NaN, wrong keys, or CSS text=background (bars were visible; values were simply omitted from the SVG).

Before screenshot: `docs/audits/task-46-inflation-calculator-graph-values/inf46-00-before-missing-values.png`

---

## 5. Previous chart architecture

- Chart type: 2-bar SVG comparison  
- Data: `inputs.current_amount` + `result.future_cost` only  
- No intermediate compound points  
- No axis currency scale  
- No data-point currency labels on SVG  
- No tooltips / table alternative

---

## 6. Updated chart implementation

New presentation helper + Blade partial:

- `App\Support\Pfm\InflationChart` — builds year-wise series with `PfmMath::compound`, final point pinned to engine `future_cost`
- `resources/views/.../inflation-chart.blade.php` — line chart SVG + meta + table
- Wired via `result-charts.blade.php` for inflation only
- CSS: `.yk-infchart*` in `yuktione-pfm.css`
- Inflation metrics made null-safe for sparse historical snapshots

Engine formulas and KPI keys unchanged.

---

## 7. Graph data source

Same approved model as the engine:

1. Year 0 = current amount  
2. Intermediate years = `roundMoney(compound(amount, rate, year))`  
3. Final year = existing `result.future_cost` KPI  

No second engine. No hardcoded sample series.

---

## 8. X-axis improvements

- Labels: Today, Year 1… Year N (fractional years supported)  
- ≤12 years: yearly marks  
- Longer spans: sampled marks; start + end always shown  
- Dense charts sparse the visible X labels to avoid overcrowding  

---

## 9. Y-axis improvements

- Compact INR ticks: ₹10K / ₹1L / ₹1Cr style via `compactMoney`  
- Full amounts in tooltips + year-wise table  
- Subtle grid lines; consistent scale from min→max series values  

---

## 10. Data-point value improvements

- Visible compact labels on start/end and selected intermediates  
- Marker dots on every plotted point  
- End point highlighted  
- Mobile reduces label density via sparse indexes (not by hiding all values)  

---

## 11. Tooltip / accessible values

- Native SVG `<title>` per point: `Year N — Estimated future cost: ₹…`  
- Points are `tabindex="0"` for keyboard focus  
- Expandable **Year-wise cost projection** table with full amounts  

---

## 12. Currency formatting

- Full: `₹` + `number_format(..., 2)`  
- Compact axis/point: ₹K / ₹L / ₹Cr (1e3 / 1e5 / 1e7)  

---

## 13. Year-wise financial data presentation

Table rows share the same `$chart['points']` / `$chart['table']` array as the SVG. Heading: **Year-wise cost projection**.

---

## 14. Financial formula verification

Reference case ₹1,00,000 @ 6% / 10 years (engine + chart):

| Period | Value |
| --- | ---: |
| Today | ₹100,000.00 |
| Year 1 | ₹106,000.00 |
| Year 2 | ₹112,360.00 |
| Year 3 | ₹119,101.60 |
| Year 5 | ₹133,822.56 |
| Year 10 | ₹179,084.77 |
| Increase | ₹79,084.77 |

Final chart point matches `future_cost` KPI.

---

## 15. Edge-case verification

| Case | Result |
| --- | --- |
| Zero inflation | Flat series at present amount |
| Decimal rate | Matches `PfmMath::compound` |
| Long duration (30–40y) | Sampled points; finite SVG coords |
| Large amounts | Compact Cr/L labels |
| Sparse historical inputs | Chart unavailable message; metrics show Unavailable |
| Complete historical snapshot | Reconstructs series from saved inputs + outputs |

---

## 16. Historical calculation compatibility

- Complete snapshots reconstruct graph from original inputs/outputs  
- Incomplete snapshots show unavailable chart state  
- No DB rewrite / no silent re-assumption of missing rates  

---

## 17. Save, Recalculate, and History verification

| Flow | Behavior |
| --- | --- |
| Calculate | Preview only; no auto-save |
| Save | Explicit; ownership enforced |
| Recalculate | Existing Inflation behaviour: clears draft (`forget`) |
| New Test | Clears draft |
| Guest | Preview + Save (sign in) |
| History show | Chart when reconstructable |

---

## 18. Modified files

| File | Change |
| --- | --- |
| `app/Support/Pfm/InflationChart.php` | **New** series/view-model |
| `resources/views/.../inflation-chart.blade.php` | **New** chart UI |
| `resources/views/.../result-charts.blade.php` | Inflation branch → new partial |
| `resources/views/.../result-metrics.blade.php` | Null-safe inflation metrics |
| `public/frontend/assets/css/yuktione-pfm.css` | `.yk-infchart*` styles |
| `tests/Feature/InflationCalculatorGraphValuesTest.php` | **New** suite |
| `docs/TASK_46_INFLATION_CALCULATOR_GRAPH_VALUES_REPORT.md` | This report |
| `docs/audits/task-46-inflation-calculator-graph-values/*` | Screenshots |

---

## 19. Database changes

None.

---

## 20. Automated test counts and assertions

| Suite | Result |
| --- | --- |
| `InflationCalculatorGraphValuesTest` | **10 passed / 138 assertions** |
| Related PFM regressions (RT 44–45, Debt, `PfmCalculatorTest`, `PfmToolsTest`) | Pass except known unrelated EF Save-label mismatch |

---

## 21. Existing unrelated test failures

Confirmed pre-existing / unrelated to Task 46:

1. `PfmToolsTest` Emergency Fund expects `Save result` but UI shows `Save Calculation`  
2. Previously documented: MMP New Test assertion drift; NineTools catalogue drift  

Inflation path in `PfmToolsTest` remains functional.

---

## 22. Actual browser verification

Local server `127.0.0.1:8001` (Cursor IDE browser). Synthetic data.

| Check | Outcome |
| --- | --- |
| Before: SVG texts only Today/Future | Confirmed |
| After: line + values + Y ticks | Pass |
| Reference intermediates Year 1–5–10 | Pass |
| Long 30y / large amount | Pass |
| Year-wise table | Pass |
| Guest Save CTA | Pass |
| No calculator JS errors observed | Pass |

---

## 23. Responsive verification

| Width | Method | Notes |
| --- | --- | --- |
| Desktop | Actual browser | Full chart + table |
| 390px | CDP device metrics (emulation) | Chart readable; sparse labels |
| 360–1440 CSS | Media rules for tick/value font sizes | No horizontal page overflow observed |

Physical-device testing not performed.

---

## 24. Accessibility verification

- Chart heading + aria-label summary  
- Year-wise table alternative  
- Point titles for hover/focus  
- Keyboard-focusable points  
- Contrast: plum series on white; dark value text  

---

## 25. Before-and-after screenshots

Under `docs/audits/task-46-inflation-calculator-graph-values/`:

| File | Content |
| --- | --- |
| `inf46-00-before-missing-values.png` | Original results (pre-fix) |
| `inf46-01-fixed-desktop-graph.png` | Fixed graph + table start |
| `inf46-02-axis-and-datapoints.png` | Axes + data-point values |
| `inf46-03-long-duration.png` | 30-year projection |
| `inf46-04-mobile-390.png` | Mobile emulation |

---

## 26. Remaining limitations

1. Native SVG `<title>` tooltips; no custom rich tooltip library  
2. Inflation Recalculate still clears draft (pre-existing; not changed to `clearResult`)  
3. Compact labels are approximate readability helpers; full amounts live in table/titles  
4. Physical-device QA not run  

---

## 27. Confirmation that previous tasks remain functional

Retirement Tasks 44–45 and Debt 40–43 suites passed in the regression run. Shared chart partial only swaps the inflation branch.

---

## 28. Confirmation that other calculators remain unaffected

Feature assertion: Retirement results do not include `data-yk-inf-chart` / year-wise cost projection. Other tools keep their existing chart branches.

---

**Task 46 complete. Stopped. Awaiting further instructions.**
