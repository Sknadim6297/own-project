# Task 32 — Financial Goal Calculator: Consistent Website Design

**Date:** 2026-11-24  
**Scope:** Visual alignment of Financial Goal form + results with Yuktione plum / pearl  
**Preserved:** Task 31 compact header; formulas; wizard flow; other PFM tools  
**Out of scope:** Tasks 33–37 (slider, formatting overhaul, input restructure, result restructure, global banner removal)

---

## 1. Existing design architecture

| Surface | Stack |
|---|---|
| Form / results | Dedicated `goal-*.blade.php` + `yuktione-pfm-goal.css` (`.yk-goal` / `.yk-goalr`) |
| History / show | Shared `history.blade.php` / `show.blade.php` + `yuktione-pfm.css` |
| Brand tokens | `yuktione-brand.css` (`--y1-plum`, site surfaces) |
| Shared PFM | `yuktione-pfm.css` (`--yk-teal: #8a0f4d`) |

Task 31 compact header (`goal-compact-header`, `yk-goal--compact-header`) was already in place.

Audit notes: `docs/audits/task-32-financial-goal-design/AUDIT.md`

---

## 2. Confirmed design inconsistencies

1. Emerald brand greens (`#14705f`, `#0f4f45`) vs site plum `#8A0F4D`
2. Paper background `#f6f3ec` + green dots vs pearl `#FFF8FB`
3. Mint-green soft fills vs soft plum `#F8EAF1`
4. Primary CTAs as green/gold gradients vs solid plum PFM buttons
5. Dark emerald/gold sidebar vs white PFM cards
6. Green focus rings / shadows
7. Chart strokes in emerald
8. Inline SVGs for actions instead of `x-yk-icon`
9. Warm paper step/action footers

---

## 3. Reused website design tokens

Scoped under `.yk-goal`:

| Token | Value | Role |
|---|---|---|
| `--g-green` | `#8a0f4d` | Primary plum (legacy name kept) |
| `--g-green-2` | `#c3196b` | Hover magenta |
| `--g-deep` | `#6e0b3c` | Deep plum |
| `--g-mint` | `#f8eaf1` | Soft plum |
| `--g-mint-2` / `--g-paper` | `#fff8fb` | Pearl |
| `--g-line` | `#e8e4de` | Shared PFM line |
| `--g-ok` | `#2f9e6b` | Semantic success only |
| `--g-shadow` / `--g-focus` | plum-tinted | Match PFM |

Also: `body.yk-goal-body { background-color: #fff8fb; }`

---

## 4. Background color improvements

- Main tool surface: pearl + soft plum dot grid
- Body continuity with header/footer
- Step bar / actions footers: pearl instead of warm paper
- Removed dark emerald decorative sidebar gradient

---

## 5. Typography improvements

- Kept existing DM Sans stack (site font)
- Heading / body ink mapped to charcoal `#1f1f1f` and deep plum for H2s
- Muted copy uses `#6b6b7a`
- Result sentence / legend text no longer green-tinted greys

---

## 6. Input and button consistency

- Focus rings: plum
- Affixes / optional chips: soft plum
- Primary + Calculate/Save (`btn--gold` class retained): solid plum, 12px radius, PFM-like height
- Ghost / secondary (Edit, draft): white + plum border hover
- Results aside actions restyled for light cards (no inverted dark buttons)

---

## 7. SVG icon implementation

Via centralized `<x-yk-icon>` / registry (no new library):

- target, calculator, history, save, pencil, refresh-cw, info
- wallet, calendar-days, chart-no-axes-combined, activity (field labels)
- check, arrow-left/right, circle-check, triangle-alert

Goal-type tiles keep compact inline SVGs (no matching Lucide keys for all types) but inherit plum colour via CSS.

`trending-up` is not in the registry; `activity` used for expected return.

---

## 8. Result and history styling improvements

- Results: plum figure/KPI chrome; funding bar plum; chart plum/gold; light “Keep this plan” card
- Compact header retained; form lead suppressed on results (`lead => ''`)
- History / show already on shared PFM plum styles — unchanged (shared hero remains for Task 37)

---

## 9. CSS architecture and scope

- Changes confined to `yuktione-pfm-goal.css` + FG Blade partials
- No edits to shared `yuktione-pfm.css` tool behaviour for other calculators
- Legacy `.yk-goal__hero` styles left dormant (not rendered; Task 31)

---

## 10. Modified files

**Updated**

- `public/frontend/assets/css/yuktione-pfm-goal.css`
- `resources/views/frontend/sections/pfm/goal-form.blade.php`
- `resources/views/frontend/sections/pfm/goal-results.blade.php`
- `resources/views/frontend/sections/pfm/partials/goal-fields.blade.php`
- `resources/views/frontend/sections/pfm/partials/goal-chart.blade.php`

**New**

- `tests/Feature/FinancialGoalDesignConsistencyTest.php`
- `docs/TASK_32_FINANCIAL_GOAL_DESIGN_REPORT.md`
- `docs/audits/task-32-financial-goal-design/*`

---

## 11. Database changes

**None.**

---

## 12. Automated test results

| Suite | Result |
|---|---|
| `FinancialGoalDesignConsistencyTest` | 2 passed |
| `FinancialGoalBannerRemovalTest` (Task 31) | 2 passed |
| `GoalProjectionTest` | 5 passed |
| `PfmToolsTest` financial-goal data set | passed |
| Other PfmTools flow tools except EF | passed |
| `EmergencyFundUiFormattingTest` | 4 passed |
| Account recovery suite | passed |

**Pre-existing (not fixed):** `PfmToolsTest` emergency-fund expects `"Save result"` but EF UI uses `"Save Calculation"` (Task 30). Unrelated to Task 32.

---

## 13. Actual browser verification

URL: `http://127.0.0.1:8000/tools/pfm/financial-goal`

- Compact header + plum pearl chrome
- Light sidebar (no emerald art)
- Wizard Continue → cost → assumptions → Calculate → results
- Results: plum monthly figure, Save / draft / recalculate / history
- History empty state uses shared PFM plum styling

---

## 14. Responsive verification

| Viewport | Method | Notes |
|---|---|---|
| Desktop ~1280+ | Chromium | Form + results aligned |
| 390×844 | CDP emulation | Compact header; no large banner |

Physical-device testing not performed. Viewport emulation only.

---

## 15. Before-and-after screenshots

| Shot | Path |
|---|---|
| Before form (emerald sidebar) | `docs/audits/task-32-financial-goal-design/before-form.png` |
| After form | `docs/audits/task-32-financial-goal-design/after-form.png` |
| After results | `docs/audits/task-32-financial-goal-design/after-results.png` |
| After history | `docs/audits/task-32-financial-goal-design/after-history.png` |

---

## 16. Remaining limitations

- Shared history/show `page-hero` still present (Task 37)
- FG still uses dedicated layout (not fully merged into shared PFM form chrome)
- Years slider / amount formatting / input & result restructures deferred (Tasks 33–36)
- Goal-type icons remain custom paths (registry gap)

---

## 17. Task 31 intact

- No `yk-goal__hero` / `yk-goal__hero-art` on form
- Compact header + required title/description retained
- Confirmed by `FinancialGoalBannerRemovalTest` + browser

---

## 18. Other PFM tools unaffected

- EF / Money Planner still load `yuktione-pfm.css` only (asserted in design test)
- EF UI formatting tests pass
- `yuktione-pfm.css` not modified in this task

---

**Status:** Task 32 complete. Stopped — waiting for further instructions.
