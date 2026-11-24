# Task 38 — Common Tools Layout Consistency (Debt & EMI Reference)

**Status:** Complete  
**Date:** 24 Nov 2026  
**Scope:** Local only — no push, deploy, Task 39, or Tasks 40–43.

---

## 0. Task 37 dependency

Verified before proceeding:

- `ToolsCompactHeaderTest` — **6 passed**
- Financial Goal Tasks 31–36 — **25 passed, 322 assertions**
- Decorative banners remain removed; FG dedicated layout intact

---

## 1. Debt & EMI reference layout architecture

| Piece | Detail |
| --- | --- |
| Route | `tools.pfm.debt-emi` → `PfmToolController` |
| Blade | Shared `pfm/form.blade.php` + `partials/debt-fields` |
| Header | `partials/page-hero` → `yk-pfm__hero--compact` |
| Structure | Site nav → crumbs → eyebrow badge → 48px icon + H1 + lead → `yk-pfm__wrap--wide` (1040px) → white card → wizard/form |
| Marker | `data-yk-tool-shell-ref="debt-emi"` |
| Measured | Header ~**196px**; icon **48px**; shell CSS loaded |

No Debt & EMI formula/input changes (Tasks 40–43 deferred).

---

## 2. Total tools audited

**23** registered tools (`ToolsCatalog::TOOL_PLACEMENT`).

---

## 3. Confirmed layout inconsistencies (pre-fix)

| Issue | Examples |
| --- | --- |
| Uneven content max-widths | BMI 1120, Career 820, PS 780, Journal 600, Mood home 1238 vs PFM 1040 |
| Uneven gutters | 48px vs 2rem hero/content mismatch on PFM |
| Icon boxes | Different sizes/colors across tools |
| Title scale | Inconsistent clamp sizes after Task 37 |
| No shared shell tokens | Each tool CSS redeclared spacing |

---

## 4. Total tools modified

**Layout/shell markers + CSS alignment applied to entry surfaces for:**

PFM form/results (incl. Debt & EMI reference marker), Mood, BMI, Career, PS forms, Tarot intro, Journal write, Skills welcome, Gratitude write, Water welcome, Nutrition welcome, Weight Journey welcome.

Shared CSS also normalizes wrap/icon/title rules for SSA / water / nut / wj / journal / gratitude / tarot under `.yk-tools-ds`.

---

## 5. Tools requiring no structural redesign

| Tool | Why |
| --- | --- |
| **Financial Goal** | Tasks 31–36 dedicated architecture preserved |
| **Debt & EMI** | Reference only — no Task 40–43 changes |
| Astrology / Business / Tax | Empty categories — no tools |
| Tracking dashboards (auth water/nut/wj) | Functional tracking UIs; welcome shells aligned only |

---

## 6. Shared layout component architecture

| Asset | Role |
| --- | --- |
| `partials/tool-compact-header.blade.php` | Reusable Debt & EMI–style compact header (crumbs, eyebrow, 48px icon, title, lead, optional history) |
| `yuktione-tool-shell.css` | Shell tokens + alignment for all `tools.*` pages |
| `data-yk-tool-shell` | Marker on applicable tool mains |
| Existing `page-hero` / Task 37 compact headers | Retained; styled to shell tokens |

Loaded from `layouts/layout.blade.php` for all `tools.*` routes (after tools-ds + tool-chrome).

---

## 7. Container and spacing improvements

| Token | Value |
| --- | --- |
| `--yk-tool-shell-max` | **1040px** (calculator/form tools) |
| `--yk-tool-shell-max-reading` | **820px** (journal / gratitude / tarot) |
| `--yk-tool-shell-gutter` | **2rem** (1.5rem ≤768px) |
| `--yk-tool-shell-icon` | **48px** soft plum `#F8EAF1` / `#8A0F4D` |
| Sheet gap | **22px** after compact header |

Mood home may use up to **1120px** for its two-column functional grid (allowed wider exception).

---

## 8. SVG icon consistency

- Registry icons only (`x-yk-icon`)
- Shared 48×48 soft-plum icon containers
- Skills welcome updated to `lightbulb` title row
- No emoji / no new icon library
- Mood selectors, Tarot cards, FG charts preserved

---

## 9. Functional exceptions preserved

- Mood follow-up / 4-step flow  
- Journal / Gratitude writing  
- Tarot steps + disclaimer + Start a Reading  
- Career / Skills Start–Continue assessments  
- PS wizards  
- Water / Nutrition / Weight tracking workflows  
- All PFM calculate/save/history semantics  
- FG seven inputs, years slider, dedicated results  

---

## 10. Modified files

| File | Change |
| --- | --- |
| `public/frontend/assets/css/yuktione-tool-shell.css` | **New** shell CSS |
| `resources/views/frontend/sections/partials/tool-compact-header.blade.php` | **New** shared header partial |
| `resources/views/layouts/layout.blade.php` | Load shell CSS on `tools.*` |
| `resources/views/frontend/sections/pfm/form.blade.php` | `yk-tool-shell` + debt-emi ref marker |
| `resources/views/frontend/sections/pfm/results.blade.php` | Shell marker |
| Mood / BMI / Career / PS / Tarot / Journal / Skills / Gratitude / Water / Nutrition / Weight welcome|entry mains | Shell markers (+ Skills title row) |
| `tests/Feature/ToolsLayoutConsistencyTest.php` | **New** Task 38 tests |

---

## 11. Database changes

**None.**

---

## 12. Automated tests

| Suite | Result |
| --- | --- |
| `ToolsLayoutConsistencyTest` | **5 passed** |
| Combined with Task 37 + Financial Goal | **36 passed, 526 assertions** |

---

## 13. Existing unrelated test failures

Documented (not fixed): `PfmToolsTest` EF Save label expects `Save result` while UI shows **Save Calculation**.

---

## 14. Actual browser verification

**Method:** Cursor IDE browser → `http://127.0.0.1:8000`  
**Not** physical-device testing.

Verified:

- Debt & EMI reference (shell CSS, 48px icon, compact header, loan fields)  
- Money Planner aligned shell  
- BMI / Career compact + first controls  
- Financial Goal preserved (years slider, seven inputs)  
- Mobile 390 emulation on Debt & EMI  

---

## 15. Responsive verification

| Viewport | Method |
| --- | --- |
| 390×844 | Device metrics — Debt & EMI mobile screenshot |
| Desktop (narrow IDE tab) | Reference + aligned tools |
| 360–1440 | Shell CSS gutters / single-column rules |

---

## 16. Accessibility

- H1 hierarchy retained  
- Focus styles on crumbs/history links in shared header  
- Decorative icons `aria-hidden`  
- Functional labels / disclaimers unchanged  

---

## 17. Screenshots

`docs/audits/task-38-tools-layout-consistency/`

| File | Content |
| --- | --- |
| `01-debt-emi-reference.png` | Reference layout |
| `02-bmi-aligned.png` | BMI shell alignment |
| `03-career-aligned.png` | Career shell alignment |
| `04-financial-goal-preserved.png` | FG Tasks 31–36 intact |
| `05-debt-emi-mobile-390.png` | Mobile reference |
| `06-money-planner-aligned.png` | Money Planner shell |

---

## 18. Remaining limitations

- Physical-device QA not run.  
- Not every internal assessment step page was rewritten onto `tool-compact-header` partial (entry pages + shared CSS cover Task 38).  
- Description copy standardization is **Task 39** — not done here.  
- Debt & EMI single-page / down payment / result narrative = Tasks 40–43 — not started.  

---

## 19. Previous tasks remain functional

- Task 37 banner removal: green  
- Financial Goal 31–36: green (dedicated views untouched)  

---

## 20. Unrelated website sections unaffected

Homepage hero, catalogue heroes, services, professionals, auth, admin — not modified. Asserted via source checks in tests.

---

## Stop

Task 38 complete. Awaiting further instructions. Do **not** start Task 39.
