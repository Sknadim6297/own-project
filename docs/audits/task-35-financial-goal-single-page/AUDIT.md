# Task 35 — Pre-implementation audit

## Field inventory (7)

| Name | Type | Default | Required |
| --- | --- | --- | --- |
| `goal_type` | radio | `custom` | yes |
| `goal_name` | text | empty | optional |
| `current_cost` | number | empty | yes |
| `years` | number + range slider | empty (slider idle 10) | yes |
| `existing_savings` | number | `0` | yes |
| `inflation_percent` | number | `6` | yes |
| `expected_return_percent` | number | `10` | yes |

## Previous three-step distribution

| Step | UI label | Fields |
| --- | --- | --- |
| 0 | Goal type | goal_type, goal_name |
| 1 | Cost & timeline | current_cost, years, existing_savings |
| 2 | Assumptions | inflation_percent, expected_return_percent (+ review dl) |

## Wizard mechanics

- Markup: `data-pfm-wizard="1"`, `[data-pfm-step]`, step nav, `data-pfm-next` / `data-pfm-prev`, submit hidden until last step.
- JS: shared `yuktione-pfm.js` `initWizard()` (layout-loaded). FG-specific `yuktione-pfm-goal.js` does **not** own wizard navigation.
- Validation: HTML5 per-step via `checkValidity` on Continue; full backend validation on Calculate POST (unchanged).
- Draft: `PfmDraft` stores normalized + result after calculate; form restore via `$old` / draft normalized — not step-indexed.
- Routes: `tools.pfm.financial-goal` (form), `.calculate`, `.results`, `.save`, `.history` — unchanged.

## Dependencies Tasks 31–34

- Compact header, plum/pearl tokens, years slider, section hierarchy — must remain.
- Other PFM tools keep their wizards via shared form + `yuktione-pfm.js`.
