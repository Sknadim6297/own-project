# Task 42 — Debt & EMI Calculator: Down Payment Field

**Status:** Complete  
**Date:** 25 Nov 2026  
**Scope:** Local only — no push, deploy, or Task 43.

---

## 1. Previous financial architecture

Four inputs → `PfmCalculator::debtEmi` → `PfmMath::emiPlan($principal, rate, years, extra)` where `principal` was the financed loan amount. API and history stored the same key. Task 41 single-page form + Task 40-style affixes.

---

## 2. New down payment architecture

**Backward-compatible contract (chosen):**

| Key | Role |
| --- | --- |
| `principal` | **Total purchase amount** (unchanged request/storage key) |
| `down_payment` | Optional; missing/empty → `0` |
| `financed_principal` | Server-computed `principal − down_payment` (never trusted from client) |

When `down_payment = 0`, financed equals `principal` → identical EMI to pre-Task-42 for web, API, and historical zero-down records.

No separate purchase-amount API field; no migration; no double subtraction.

Audit: `docs/audits/task-42-debt-emi-down-payment/AUDIT.md`.

---

## 3. Exact input and output field inventory

### Inputs (normalized)

- `principal`, `down_payment`, `financed_principal`, `annual_rate_percent`, `tenure_years`, `extra_payment`

### Outputs (additive on existing plan keys)

- Existing: `emi`, `standard_*`, `accelerated_*`, `interest_saved`, `months_saved`, `summary`
- New: `purchase_amount`, `down_payment`, `financed_principal`, `no_loan`, `loan_repayment_total`, `total_purchase_outflow`

---

## 4. Purchase amount vs financed principal semantics

- UI label for `principal`: **Total purchase amount**
- Financed preview (client display only) + server verification
- Charts/metrics use **financed** principal for loan repayment breakdown
- `total_purchase_outflow` = down payment + loan repayment (not labelled as loan repayment alone)

---

## 5. Down payment validation

- Optional; default 0; empty → 0  
- Reject negative (existing `money`)  
- Reject `down_payment > principal`  
- Allow equal (full pay → no loan)  
- Same amount ceilings / rounding as other money fields  

---

## 6. Updated calculation flow

1. Validate purchase + down payment  
2. `financed = roundMoney(purchase − down)`  
3. If financed ≤ 0 → safe no-loan result (zeros; no amortize)  
4. Else `PfmMath::emiPlan(financed, …)`  
5. Attach purchase/down/financed + outflow metrics  

Extra payment remains monthly only (not confused with down payment).

---

## 7. Standard EMI verification

| Case | Result |
| --- | --- |
| A: ₹5L / 0 down / 10% / 5y | EMI ≈ **₹10,623.52** (legacy unchanged) |
| B: ₹5L / ₹1L down / 10% / 5y | EMI ≈ **₹8,498.82**; interest ≈ **₹1,09,929.07**; loan total ≈ **₹5,09,929.07** |
| F: ₹5L / ₹1L / 0% / 5y | EMI ≈ **₹6,666.67** |

Browser Case B: EMI **₹8,498.82**; interest **₹109,929.07**; loan repayment **₹509,929.07**; outflow **₹609,929.07**.

---

## 8. Accelerated repayment verification

Partial down + extra ₹2,000 matches engine run with financed ₹4,00,000 and same extra (asserted).

---

## 9. Full down payment handling

Financed ₹0 → `no_loan`, EMI/interest/saved = 0, summary “No loan required…”, metrics “No borrowing required”. Browser confirmed.

---

## 10. Zero-down-payment backward compatibility

Omitting `down_payment` or sending `0` / `''` matches legacy EMI. Task 41 + DE01/DE02 unit tests still pass.

---

## 11. Existing API compatibility

- `down_payment` optional on calculate/save  
- Options document additive field  
- Response adds `purchase_amount`, `down_payment`, `financed_principal`, …  
- `breakdown.principal` = financed (equals purchase when down = 0)  
- Legacy four-field POST still returns ≈ ₹10,623.52 EMI  

---

## 12. Draft and Recalculate verification

- New drafts store `down_payment` + `financed_principal`  
- Form restores purchase + down after calculate  
- Recalculate behaviour unchanged (Debt still uses shared `forget` path)  
- New Test resets down payment to `0`  
- Client `financed_principal` tampering ignored  

---

## 13. Saved history compatibility

- New saves store purchase, down, financed in inputs/outputs  
- Legacy snapshots without `down_payment` still render (metrics fall back to `principal`)  
- No auto-rewrite of historical rows  

---

## 14. Modified files

| File | Change |
| --- | --- |
| `app/Services/Pfm/PfmCalculator.php` | Down payment + no-loan branch |
| `app/Http/Controllers/Api/V1/DebtEmiController.php` | Optional DP; additive presentResult |
| `resources/views/.../debt-fields.blade.php` | Purchase/DP labels + live preview |
| `resources/views/.../result-metrics.blade.php` | Purchase/DP/financed/outflow |
| `resources/views/.../result-charts.blade.php` | Financed breakdown / no-loan copy |
| `public/frontend/assets/js/yuktione-pfm.js` | Financed preview helper |
| `public/frontend/assets/css/yuktione-pfm.css` | Preview styles |
| `tests/Feature/DebtEmiDownPaymentTest.php` | **New** |
| `docs/audits/task-42-debt-emi-down-payment/*` | Audit + screenshots |
| `docs/TASK_42_DEBT_EMI_DOWN_PAYMENT_REPORT.md` | This report |

---

## 15. Database changes

**None.**

---

## 16. Automated test counts and assertions

| Suite | Result |
| --- | --- |
| `DebtEmiDownPaymentTest` | **9 passed, 91 assertions** |
| `DebtEmiSinglePageFormTest` + `PfmCalculatorTest` + Task 42 | **33 passed, 212 assertions** (combined run) |
| Broader Debt/PFM/FG/EF/MMP filter | **162 passed**, **2 failed** (unrelated — §17) |

---

## 17. Existing unrelated test failures

`PfmToolsTest` Save-label mismatch (`Save Calculation` vs `Save result` for EF/MMP) — pre-existing; not changed.

---

## 18. Actual browser verification

Cursor IDE browser on `http://127.0.0.1:8001` (not physical device):

- Fresh form with down payment + live **Actual loan amount** preview  
- Partial DP ₹1L → EMI ₹8,498.82 + metrics  
- Full DP → no borrowing  
- Invalid DP ₹6L → validation error; value restored  
- Draft restore of purchase/down after calculate  

---

## 19. Responsive verification

Desktop interaction + **390px** viewport emulation for mobile form. CSS single-column stack preserved from Task 41. Continuous page (scroll OK). Emulation ≠ device lab.

---

## 20. Accessibility verification

- Labels + helper text; financed preview `role="status"` / `aria-live="polite"`  
- Affixes `aria-hidden`  
- Error via `role="alert"`  
- Keyboard-accessible inputs; focus styles retained  

---

## 21. Before-and-after screenshots

`docs/audits/task-42-debt-emi-down-payment/`:

| File | Content |
| --- | --- |
| `01-partial-down-payment-preview.png` | Purchase + DP + ₹4,00,000 preview |
| `02-standard-emi-result-header.png` | Result header EMI ₹8,498.82 |
| `03-standard-emi-result-metrics.png` | Interest / loan / outflow metrics |
| `04-full-down-payment-result.png` | No borrowing required |
| `05-invalid-down-payment.png` | Validation error |
| `06-mobile-calculator.png` | Mobile form |

---

## 22. Remaining limitations

- Task 43 explanatory redesign not done  
- Authenticated Save→History browser path covered mainly by tests (guest browser session)  
- Common Tools 37–39 not present as separate suites in this workspace  

---

## 23. Confirmation — previous tasks remain functional

Task 41 single-page (no wizard) preserved; Task 40 affixes retained; FG/EF/MMP regressions green aside from known PfmTools label issue; Debt unit DE01/DE02 pass.

---

## 24. Confirmation — other calculators remain unaffected

Scoped Debt CSS/JS preview; API changes additive; EF still has wizard and no `down_payment` (asserted).

---

## Stop

Task 42 complete. Awaiting further instructions. Do **not** start Task 43.
