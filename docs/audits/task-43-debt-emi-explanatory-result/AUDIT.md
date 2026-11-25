# Task 43 audit notes

## Prior result gaps
- Shared `results.blade.php` showed metrics without scenario narrative.
- Recalculate used `forget()` (inputs wiped).
- Loan repayment vs purchase outflow labels were easy to confuse.

## Architecture chosen
- Dedicated `debt-results.blade.php` + `DebtEmiPresentation` (presentation only).
- `principal` semantics unchanged (purchase amount).
- Recalculate → `clearResult` (restore five inputs).
- No DB migration; API contracts unchanged.

## Browser
- Local php built-in server `:8001`.
- Guest Case B/C/D verified; 390px viewport emulation.
- Screenshots in this folder.
