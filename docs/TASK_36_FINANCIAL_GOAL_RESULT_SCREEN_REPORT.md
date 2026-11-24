# Task 36 — Financial Goal Calculator: Dedicated Result Screen

**Status:** Complete  
**Date:** 24 Nov 2026  
**Scope:** Local only — no push, deploy, or Task 37.

---

## 1. Existing result architecture

| Piece | Finding |
| --- | --- |
| Route | `tools.pfm.financial-goal.results` already existed — **reused** (no duplicate route) |
| View | `resources/views/frontend/sections/pfm/goal-results.blade.php` (dedicated; no input form) |
| Input | Task 35 single-page `goal-form.blade.php` + `goal-fields.blade.php` (`yk-goal--single`) |
| Controller | `PfmToolController::results()` / `calculate` / `save` / `recalculate` / `newTest` / history |
| Engine | `PfmCalculator` — unchanged |
| Draft | `PfmDraft` session draft; Calculate writes result preview only |
| Outputs | `required_corpus`, `future_value_existing`, `funding_gap`, `monthly_investment`, `summary` |
| Chart | Funding bar + `goal-chart` SVG (`GoalProjection`) |
| Assets | Scoped `yuktione-pfm-goal.css` / `yuktione-pfm-goal.js` |

---

## 2. Confirmed issues (pre-implementation)

1. Result H1 used goal/tool name; Task 36 needs compact title **Your Financial Goal Plan** with type / name / duration facts.
2. Empty `goal_name` fell back to tool name — should use goal type label.
3. Missing **New Test** and **Back to calculator** on result actions.
4. FG `recalculate` called `forget()` and wiped all seven inputs (EF already used `clearResult`).
5. FG results response lacked `private, no-store` cache headers.
6. Breakdown needed clearer cards for contributions / projected growth (derived from existing outputs only).

Audit notes: `docs/audits/task-36-financial-goal-result-screen/AUDIT.md`.

---

## 3. Dedicated result screen implementation

Polished existing `goal-results.blade.php` as a separate screen from the calculator:

- Compact header: SVG icon, **Your Financial Goal Plan**, goal type, custom name (when set), duration / cost facts.
- Primary KPI: **Required monthly investment** (or on-track message).
- **Financial breakdown** metric cards.
- Funding bar + savings-vs-cost SVG chart with accessible summary.
- **What this means** narrative from actual inputs/outputs.
- Action aside: Save / Save as draft / Edit & recalculate / New Test / Back to calculator / View history.
- Assumptions panel mirrors the seven inputs.

No promotional banner (Task 31 preserved). No wizard restored (Task 35 preserved).

---

## 4. Input-to-result navigation

**Flow:** Financial Goal Calculator → complete seven inputs → Calculate → `POST …/calculate` → redirect → `GET …/results`.

Result page asserts `data-yk-goal-results` and does **not** render `data-yk-goal-single`, `name="current_cost"`, or the years slider.

---

## 5. Primary financial result presentation

| Element | Source |
| --- | --- |
| Label | Required monthly investment (or Extra to invest each month when on track) |
| Amount | `result.monthly_investment` via existing INR whole-rupee formatter |
| Frequency / duration | `/ month · {years}` |
| Supporting KPIs | Goal cost in target year (`required_corpus`), savings future value, remaining gap |

No hard-coded example amounts; no new calculation engine.

---

## 6. Financial breakdown

Displayed from existing calculator outputs + direct inputs only:

- Current goal cost  
- Inflation-adjusted goal cost (`required_corpus`)  
- Existing savings today  
- Future value of savings  
- Remaining funding need (`funding_gap`)  
- Required monthly investment  
- Total projected contributions (`monthly × months`) — when not on track  
- Projected investment growth (`gap − deposits`) — when not on track  
- Expected annual return / inflation / duration  

Rounding / precision rules unchanged.

---

## 7. Chart improvements

- Funding bar: covered by savings vs monthly investing; legend with amounts and percentages; `role="img"` + `aria-label`.
- SVG growth chart: projected savings value vs goal target cost; caption uses actual return/inflation; visually-hidden numerical end-state summary.
- No new chart libraries; no dummy data.

---

## 8. Financial summary

**What this means** covers goal identity, inflation effect (including 0%), duration, monthly investment, existing savings (including zero), assumed returns as **estimates not guarantees**, plus the existing educational disclaimer.

Special cases handled in copy: no savings, on-track / zero monthly, zero return, zero inflation.

---

## 9. Action button hierarchy

| Priority | Action | Mechanism |
| --- | --- | --- |
| Primary | Save result (or Save result (sign in)) | Existing `POST …/save` + CSRF |
| Secondary | Save as draft | Same save endpoint with `as_draft` |
| Secondary | Edit & recalculate | Existing `POST …/recalculate` |
| Secondary | New Test | Existing new-calculation partial (confirm) |
| Secondary | Back to calculator | `GET` calculator route |
| Secondary | View history | Authenticated history link |

No duplicate buttons for the same action. Calculate does not auto-save.

---

## 10. Recalculate and back navigation

**Compatibility change (minimum):** FG `recalculate` now uses `clearResult()` like Emergency Fund, instead of `forget()`.

- Clears result preview only.
- Restores all seven inputs on the single-page form (including years slider state).
- Redirects to calculator with status message.

**Back to calculator** is navigation-only (does not clear draft).

---

## 11. New Calculation behaviour

Existing New Test action preserved:

- Clears only Financial Goal draft.
- Fresh form defaults on single-page calculator.
- Does not delete saved calculations.
- Does not clear other PFM tool drafts.
- Unsaved-work confirmation retained where already present.

---

## 12. Save and history verification

| Check | Result |
| --- | --- |
| Calculate → preview only | Pass (0 DB rows until Save) |
| Explicit Save | Creates `PfmCalculation` |
| History list | Shows saved FG records |
| Saved detail | Read-only snapshot of stored inputs/outputs |
| Ownership | Other user → 403 |
| Guest save | Redirect to login |

---

## 13. Session and ownership safety

| Scenario | Behaviour |
| --- | --- |
| Valid result draft | Renders dedicated results |
| Missing / expired draft | Redirect to calculator |
| Browser refresh with draft | Re-renders results |
| After Save (draft cleared) | Results URL safely redirects to calculator |
| Guest calculate | Preview OK; Save prompts sign-in |
| Unauthorized history show | 403 |
| Cache | FG results: `Cache-Control: private, no-store, no-cache, must-revalidate` |

---

## 14. Modified files (Task 36)

| File | Change |
| --- | --- |
| `app/Http/Controllers/Frontend/PfmToolController.php` | FG results cache headers; FG recalculate → `clearResult` |
| `resources/views/frontend/sections/pfm/goal-results.blade.php` | Dedicated result polish (header, breakdown, summary, actions) |
| `public/frontend/assets/css/yuktione-pfm-goal.css` | Result breakdown / hierarchy styles |
| `tests/Feature/FinancialGoalSeparateResultScreenTest.php` | **New** Task 36 regression |
| `tests/Feature/FinancialGoalFormattingTest.php` / `FinancialGoalYearsSliderTest.php` | Assert copy “Required monthly investment” |
| `docs/audits/task-36-financial-goal-result-screen/*` | Audit + screenshots |
| `docs/TASK_36_FINANCIAL_GOAL_RESULT_SCREEN_REPORT.md` | This report |

Preserved (Tasks 31–35): `goal-form`, `goal-fields`, years slider JS, banner removal, design tokens, single-page form.

---

## 15. Database changes

**None.** No migrations. Historical `pfm_calculations` rows unchanged.

---

## 16. Automated test counts and assertions

| Suite | Result |
| --- | --- |
| Financial Goal Tasks 31–36 (`--filter=FinancialGoal`) | **25 passed, 322 assertions** |
| Task 36 alone (`FinancialGoalSeparateResultScreenTest`) | **6 tests** (included above) |
| `PfmCalculatorTest` (+ FG unit coverage in same run) | **43 passed, 356 assertions** (combined file run with FG feature tests) |

Task 36 coverage highlights:

1. Dedicated result without input form  
2. Identical calculator outputs on screen  
3. Goal name / duration display  
4. Chart section copy present  
5. Calculate does not auto-save  
6. Save + history show + ownership 403  
7. Recalculate restores seven inputs on single-page form  
8. New Test → fresh calculator; saved rows retained  
9. Missing draft → redirect  
10. Guest preview + save → login  
11. Other PFM tools unaffected (EF results not FG view)  
12. `private` / `no-store` on FG results  

---

## 17. Existing unrelated test failures

Documented separately — **not weakened** for Task 36:

### `PfmToolsTest` — Save button label mismatch (pre-existing)

- Assertion at `tests/Feature/PfmToolsTest.php:193` expects:
  - MMP → `Save Calculation`
  - Other tools (including EF) → `Save result`
- Observed failure: Emergency Fund (and previously reported MMP) results UI label does not match that expectation (`Save Calculation` vs `Save result`).
- **Not fixed in Task 36** — unrelated UI; FG Save label remains **Save result** and passes FG tests.

Broader filtered run example: `2 failed, 159 passed` when including EF/MMP/recovery filters; isolated `PfmToolsTest`: **1 failed, 18 passed (191 assertions)** — same label assertion.

---

## 18. Actual browser verification

**Method:** Cursor IDE browser against `http://127.0.0.1:8000` (local Laravel).  
**Not** physical-device testing.

Synthetic flow completed:

1. Open Financial Goal Calculator (single-page form)  
2. Enter seven inputs; set years via **10y** chip / slider  
3. Calculate → dedicated result (**Your Financial Goal Plan**, ₹16,285 / month for reference case)  
4. Save result → history  
5. Open saved calculation `#6` (read-only)  
6. Mobile viewport (390×844 emulation) → Calculate → mobile dedicated result header  

Also exercised / observed: missing draft redirect after save, history list, plum brand tokens, no input form on results.

---

## 19. Responsive verification

| Viewport | Method |
| --- | --- |
| 390×844 | Device metrics override — mobile result screenshot |
| Desktop (~1280+) | Default browser tab — result / chart / actions / history |
| 360 / 430 / 768 / 1024 / 1440 | Covered by CSS layout + prior Task 35 form work; Task 36 result cards use breakdown grid with mobile stacking rules in `yuktione-pfm-goal.css` |

No horizontal overflow observed on captured mobile/desktop result views. WhatsApp help tab remains edge-affixed without covering primary KPI in captures.

**Distinction:** Viewport emulation ≠ physical-device lab testing.

---

## 20. Accessibility verification

- Semantic H1/H2 on result (`Your Financial Goal Plan`, breakdown, funding, meaning).  
- Chart `aria-label` + visually-hidden numerical summary.  
- Status chips include text + SVG icons (not color alone).  
- Actions are keyboard-reachable buttons/links with existing focus styles.  
- Icon buttons use existing `x-yk-icon` registry (no emoji).

---

## 21. Before-and-after screenshots

Saved under `docs/audits/task-36-financial-goal-result-screen/`:

| File | Content |
| --- | --- |
| `01-input-form.png` | Single-page calculator (Task 35 preserved) |
| `02-desktop-result-header.png` | Dedicated result header |
| `03-desktop-primary-kpi.png` | Required monthly investment |
| `04-investment-chart.png` | Funding / growth chart region |
| `05-financial-summary.png` | Summary / meaning |
| `06-result-action-buttons.png` | Save / recalculate / New Test / Back / History |
| `07-history-page.png` | History list |
| `08-saved-calculation-detail.png` | Saved calculation detail |
| `09-mobile-result-screen.png` | Mobile dedicated result (390px emulation) |

---

## 22. Remaining limitations

- Physical-device QA not run in this task.  
- Generic PFM `show` history detail template still used for saved FG snapshots (not the live `goal-results` preview chrome) — existing behaviour.  
- After Save, session result draft is cleared (existing recorder behaviour); revisiting `/results` redirects to the form until a new Calculate.  
- `PfmToolsTest` EF/MMP Save-label mismatch remains open outside Task 36.

---

## 23. Confirmation — Tasks 31–35 remain functional

| Task | Confirmation |
| --- | --- |
| 31 Banner removal | No large promotional banner on result; compact header only |
| 32 Design consistency | Plum `#8A0F4D`, pearl `#FFF8FB`, soft plum `#F8EAF1` |
| 33 Years slider | Present on form; restored after recalculate; absent on result |
| 34 Premium formatting | INR formatting + result hierarchy retained |
| 35 Single-page form | `yk-goal--single` preserved; wizard not restored |

Regression: **25 / 322** Financial Goal feature tests green.

---

## 24. Confirmation — other PFM tools unaffected

- Task 36 test asserts EF results do not load FG CSS / FG result markers.  
- Recalculate change scoped to `emergency-fund-calculator` **and** `financial-goal-calculator` only (EF behaviour already correct).  
- No formula changes in `PfmCalculator`.  
- MMP / Net Worth / EF UIs not redesigned.

---

## Stop

Task 36 complete. Awaiting further instructions. Do **not** start Task 37.
