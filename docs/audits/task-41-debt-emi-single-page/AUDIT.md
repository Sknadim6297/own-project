# Task 41 — Pre-implementation audit (Debt & EMI)

## Previous two-step architecture

| Step | Label | Fields |
| --- | --- | --- |
| 0 | Loan details | `principal`, `annual_rate_percent`, `tenure_years` |
| 1 | Extra payment | `extra_payment` (optional, default `0`) |

Mechanics (shared `form.blade.php` + `yuktione-pfm.js` `initWizard`):

- `partialMap['debt-emi-calculator'].steps = ['Loan details', 'Extra payment']`
- `data-pfm-wizard="1"`, `[data-pfm-step]` + `hidden`, Continue / Back, submit hidden until last step
- Calculate endpoint: `POST tools.pfm.debt-emi.calculate` → `PfmCalculator::debtEmi` → `PfmMath::emiPlan` → results draft

## Task 40 status in this workspace

- Brief assumes Task 40 (`.yk-pfm--debt`, `.yk-debt__control`, affixes, `DebtEmiCalculatorStructureTest` 6/87).
- **Not found** in repo (no Task 40 docs/tests/CSS markers). Current `debt-fields.blade.php` is plain number inputs without affixes.
- Task 41 will apply the Task 40 presentation described in the brief while converting to a single page (affixes are presentation-only).

## Reference

Financial Goal Task 35: clear `steps` → `[]` so shared wizard does not activate; keep `yuktione-pfm.js` unchanged for EF/MMP.

## Constraints

- Do not change formulas / validation bounds.
- Do not add down payment (Task 42).
- Do not redesign results (Task 43).
- Do not alter other calculator wizards.
