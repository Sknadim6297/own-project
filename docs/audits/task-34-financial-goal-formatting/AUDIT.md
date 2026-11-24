# Task 34 — Formatting audit

## Dependencies

- Task 31 compact header: present (`yk-goal--compact-header`)
- Task 32 plum/pearl tokens: present
- Task 33 years slider: present (`data-yk-goal-years`)
- Regression: Banner + Design + YearsSlider tests passed (11)

## Confirmed issues

1. Years slider squeezed beside savings in `.yk-goal__two` (uneven column heights).
2. Input min-height ~58px larger than shared PFM ~48px.
3. Weak visual grouping within Cost & timeline / Assumptions steps.
4. Affix padding / large-amount readability can improve.
5. Years readout + slider + number stack feels tall / loose.
6. Results KPI / figure padding and overflow risk for large INR values.
7. Scale markers (25/75) compete with bounds labels on narrow widths.
8. Actions bar spacing vs form fields slightly uneven.

## Constraints

- Keep wizard steps (Task 35 later).
- Keep results route/structure (Task 36 later).
- No formula / field / validation changes.
