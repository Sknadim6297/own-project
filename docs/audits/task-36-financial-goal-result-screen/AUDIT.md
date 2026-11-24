# Task 36 — Pre-implementation audit

## Existing result architecture

| Piece | Status |
| --- | --- |
| Route `tools.pfm.financial-goal.results` | Exists |
| View `goal-results.blade.php` | Dedicated (no input form) |
| Controller `results()` | Redirects to form if draft result missing |
| Outputs | `required_corpus`, `future_value_existing`, `funding_gap`, `monthly_investment`, `summary` |
| Charts | Funding bar + `goal-chart` SVG (savings vs cost) |
| Actions | Save, Save as draft, Edit & recalculate, View history |

## Confirmed issues

1. Result H1 uses goal name / tool name; Task 36 wants compact title **Your Financial Goal Plan** with type/name/duration facts.
2. Empty `goal_name` falls back to tool name (`Financial Goal Calculator`) — should use type label.
3. Missing **New Test** and **Back to Calculator** on result actions.
4. FG `recalculate` calls `forget()` and clears all seven inputs — conflicts with “restore previous inputs”; EF already uses `clearResult`.
5. FG results response lacks private/no-store cache headers (history show has them).
6. Breakdown can surface total contributions / projected growth (derived from existing outputs, not new engine).

## Constraints

- Reuse existing `.results` route (no duplicate).
- Preserve formulas, Task 35 single-page form, years slider.
- Do not redesign other PFM tools.
