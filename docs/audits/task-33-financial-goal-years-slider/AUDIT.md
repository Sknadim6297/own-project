# Task 33 — Years duration audit

## Field

| Item | Value |
|---|---|
| Name | `years` |
| UI | `type="number"` in `goal-fields.blade.php` (`#pfm-years`) |
| HTML min / max / step | `0.01` / `100` / `0.01` |
| Fresh default | Empty (no prefilled value; placeholder `0`) |
| Quick picks | 3, 5, 10, 15, 20 via `[data-goal-years]` |
| Backend | `PfmCalculator::positiveFloat` → `> 0` and `<= 100`, rounded to 2 dp |
| Fractional | Yes |
| Draft / old | Restored via `$old` / `PfmDraft::normalized` → `$v('years')` |

## Notes

- Do not change min/max/step or formula.
- Slider must not submit a second `years` field.
- Empty default preserved until user interacts with slider/±/quick/number.
