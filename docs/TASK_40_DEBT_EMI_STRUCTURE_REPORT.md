# Task 40 — Debt & EMI Calculator Structure Improvement

**Status:** Complete  
**Date:** 24 Nov 2026  
**Scope:** Local only — no push, deploy, Task 41 (single page), Task 42 (down payment), or Task 43 (explanatory results).

---

## 0. Previous task verification

| Dependency | Result |
| --- | --- |
| Tasks 37–39 (compact header, shell, short leads) | Preserved (`data-yk-tool-shell-ref="debt-emi"`, `ToolPageLeads`, no large banners) |
| Financial Goal 31–36 | Untouched; regression green |
| Incomplete dependencies | **None** |

---

## 1. Existing Debt & EMI architecture

| Piece | Detail |
| --- | --- |
| Route | `tools.pfm.debt-emi` → shared `PfmToolController` |
| Form Blade | `resources/views/frontend/sections/pfm/form.blade.php` |
| Fields partial | `partials/debt-fields.blade.php` |
| Wizard | 2 steps: Loan details → Extra payment (`data-pfm-wizard`) |
| Engine | `App\Services\Pfm\PfmCalculator::debtEmi()` → `PfmMath::emiPlan()` |
| Draft / save | Shared PFM draft + `PfmCalculation` model |
| CSS | `yuktione-pfm.css` (+ Task 38 shell) |
| JS | Shared `yuktione-pfm.js` wizard |

---

## 2. Exact input field inventory

| Name | Type | Required | Min | Max | Step | Default |
| --- | --- | --- | --- | --- | --- | --- |
| `principal` | number | yes | 0.01 | — | 0.01 | empty |
| `annual_rate_percent` | number | yes | 0 | 50 | 0.01 | empty |
| `tenure_years` | number | yes | 0.01 | 100 | 0.01 | empty |
| `extra_payment` | number | no | 0 | — | 0.01 | `0` |

**No fields added or removed.** No down payment (Task 42 deferred). Tenure remains **years only** (no months unit / no new slider).

---

## 3. Existing financial output inventory

From `PfmMath::emiPlan` / results UI:

- `emi`
- `standard_interest`
- `standard_total`
- `standard_months`
- `accelerated_interest`
- `accelerated_months`
- `accelerated_total`
- `interest_saved`
- `months_saved`
- `monthly_rate`
- Summary string on results

Result screen structure **not redesigned** (Task 43 deferred).

---

## 4. Confirmed structural issues (pre-fix)

- Flat grid of three inputs without section hierarchy
- Currency / % / years encoded only in labels (easy to miss; values could feel cramped)
- Generic wizard chrome; weak visual grouping between loan details and extra payment
- Limited field icons / helper guidance
- Button row lacked Debt-specific primary/secondary icon cues

---

## 5. New calculator structure

1. Site nav  
2. Compact breadcrumb + shell header  
3. Short `ToolPageLeads` description (unchanged wording)  
4. Wizard nav (Loan details / Extra payment) — **still 2 steps**  
5. Sectioned loan details card content (icons + helpers + affixes)  
6. Sectioned optional extra payment step  
7. Primary Calculate / secondary Back & View History  
8. Existing disclaimer via page lead  

Markers: `yk-pfm--debt`, `data-yk-debt-structure="1"`, `data-yk-debt-fields`.

---

## 6. Input grouping improvements

**Step 1 — Loan details**

- Loan principal (₹)
- Annual interest rate (%)
- Loan tenure (years)

**Step 2 — Extra monthly payment**

- Extra monthly payment (₹, optional)

Clear section heads, required asterisks, helper copy under each field.

---

## 7. Currency and percentage formatting

Scoped `.yk-debt__control` affixes (FG/EF visual reference, Debt-only CSS):

- Leading `₹` for money fields  
- Trailing `%` for rate  
- Trailing `years` for tenure  

Affixes sit in tinted boxes; inputs keep numeric values only (submit precision unchanged).

---

## 8. Loan duration presentation

- Still `tenure_years` with min 0.01 / max 100 / step 0.01  
- Unit visibly shown as `years` affix  
- Helper: “EMI months = years × 12”  
- No new unit conversion or duration slider  

---

## 9. Button hierarchy

Debt-scoped action row:

- Primary: Continue / Calculate (+ calculator icon on Calculate)  
- Secondary ghost: Back (+ arrow), View History (+ history)  
- Labels unchanged (`Calculate`, `Recalculate`, `View History`, `Back`, `Continue`)  
- Routes / CSRF / wizard JS unchanged  

---

## 10. SVG icon implementation

Registry icons only: `calculator`, `wallet`, `chart-pie`, `calendar-days`, `plus`, `info`, `arrow-left`, `arrow-right`, `history`, `refresh-cw`. No emoji / new library.

---

## 11. Financial formula verification

Identical synthetic input:

`principal=2000000`, `annual_rate_percent=9`, `tenure_years=15`, `extra_payment=5000`

Browser result summary: **EMI ₹20,285.33 · Interest saved ₹607,406.12 · Months saved 59** — matches engine. Unit tests `test_de01` / `test_de02` and API debt tests remain green.

---

## 12. Draft and saved calculation verification

- Draft restoration: returning to form after calculate restores principal / rate / tenure  
- Validation error path preserves field names  
- Authenticated save + ownership isolation covered in `DebtEmiCalculatorStructureTest`  
- No migrations; historical records untouched  

---

## 13. Modified files

- `resources/views/frontend/sections/pfm/partials/debt-fields.blade.php`
- `resources/views/frontend/sections/pfm/form.blade.php` (Debt-scoped class/eyebrow/button icons only)
- `public/frontend/assets/css/yuktione-pfm.css` (`.yk-pfm--debt` / `.yk-debt*` block)
- `tests/Feature/DebtEmiCalculatorStructureTest.php` *(new)*
- `docs/TASK_40_DEBT_EMI_STRUCTURE_REPORT.md`
- `docs/audits/task-40-debt-emi-structure/*`

---

## 14. Database changes

**None.**

---

## 15. Automated tests

| Suite | Result |
| --- | --- |
| `DebtEmiCalculatorStructureTest` | **6 passed / 87 assertions** |
| `PfmCalculatorTest` (+ debt cases) | Passed |
| `ApiV1DebtEmiTest` | **5 passed** |
| Tasks 37–39 suites | Passed |
| Financial Goal 31–36 | Passed (prior run) |

Combined calculator/debt/API run: **29 passed / 143 assertions**.

---

## 16. Existing unrelated test failures

`PfmToolsTest::test_every_tool_runs_the_full_signed_in_flow` still expects **“Save result”** for non–money-planner tools while UI shows **“Save Calculation”** / related wording. Documented only — **not weakened**.

---

## 17. Actual browser verification

**Method:** Cursor IDE browser → `http://127.0.0.1:8000`  
**Type:** Actual browser interaction + 390px viewport emulation (not physical-device lab).

Workflow completed:

Debt & EMI → enter loan details → Continue → extra ₹5000 → Calculate → results (EMI / interest saved / months saved) → return form shows draft values + View results / New Test.

---

## 18. Responsive verification

- Desktop canvas screenshot of header + grouped inputs  
- 390px emulation of compact header  
- Affixes readable; no intended horizontal overflow from Debt CSS  
- Physical device: not performed  

---

## 19. Accessibility

- One page H1; section H2s for Loan details / Extra payment  
- Labels associated with inputs; helpers via `aria-describedby`  
- Affixes `aria-hidden`; focus ring on `.yk-debt__control:focus-within`  
- Wizard buttons remain keyboard-operable via existing PFM JS  

---

## 20. Screenshots

`docs/audits/task-40-debt-emi-structure/`

- `task40-debt-desktop.png` — compact header + lead  
- `task40-debt-inputs.png` — grouped fields + ₹ / % / years affixes  
- `task40-debt-mobile-390.png` — mobile header  
- `task40-debt-results.png` — existing result screen intact  

---

## 21. Remaining limitations

1. Still a **2-step wizard** (Task 41 will put all inputs on one screen).  
2. No down payment field (Task 42).  
3. Result explanation not redesigned (Task 43).  
4. Tenure unit remains years-only (as implemented).  
5. Physical-device pass not run.  

---

## 22–23. Confirmation

- Tasks **37–39** remain functional.  
- Financial Goal **31–36** remain functional.  
- Other PFM tools **not** wrapped in `yk-pfm--debt` (asserted in tests).  
- Formulas, field names, validation bounds, and history behaviour unchanged.  
- Task 41 / 42 / 43 **not** started.  
- Local only — no GitHub push, no production deploy.
