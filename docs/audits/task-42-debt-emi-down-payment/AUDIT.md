# Task 42 — Pre-implementation audit (Down payment)

## Architecture decision (backward compatible)

| Field | Meaning |
| --- | --- |
| `principal` | **Total purchase amount** (unchanged request key). With `down_payment = 0`, this equals the financed loan — identical to pre-Task-42 behaviour for API clients and historical records. |
| `down_payment` | Optional upfront payment; default / missing → `0`. |
| `financed_principal` | Server-only: `principal − down_payment`. Never trusted from the client. |

Do **not** introduce a separate purchase-amount request field (would break API). Label UI as “Total purchase amount” while keeping `name="principal"`.

## principal touchpoints

- `PfmCalculator::debtEmi` → `PfmMath::emiPlan($principal, …)`
- API `DebtEmiController` validate/calculate/save/presentResult/history
- `debt-fields.blade.php`, result-metrics, result-charts, history-card
- Unit `PfmCalculatorTest` DE01/DE02; Feature `DebtEmiSinglePageFormTest`, `ApiV1DebtEmiTest`, `PfmToolsTest`

## Full down payment

When financed principal is 0: safe no-loan result (EMI/interest/saved = 0) without amortize division issues. `emiPlan` already short-circuits on `principal <= 0`; enrich totals in `debtEmi`.
