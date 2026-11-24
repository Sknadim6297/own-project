# Task 37 — Global Tools: Remove Unnecessary Large Banners

**Status:** Complete  
**Date:** 24 Nov 2026  
**Scope:** Local only — no push, deploy, or Task 38.

---

## 1. Total registered tools audited

`ToolsCatalog::TOOL_PLACEMENT` = **23** implemented tools across nine client categories.

| Category | Tools |
| --- | --- |
| Mental Wellness | 8 |
| Career Counselling | 2 |
| Nutrition | 2 |
| Fitness | 2 |
| Astrology | 0 (empty — no tools created) |
| Tarot | 1 |
| Business Consulting | 0 |
| Tax Matters | 0 |
| Investments (PFM) | 8 |

---

## 2–4. Banners identified / removed / functional intros retained

| Metric | Count |
| --- | --- |
| Unnecessary large decorative banners identified | **16** tool surfaces (7 non-goal PFM shared hero art + Mood + BMI + Career + Journal + 5 PS head arts + Tarot stack) |
| Decorative banners removed / compacted | **16** |
| Functional introductions retained | Career Start/Continue + how-it-works; Tarot steps/disclaimer/Start a Reading; Water/Nutrition/Weight welcome/auth flows; Skills welcome; Gratitude compact; FG Tasks 31–36 |

---

## 5. Complete tool-by-tool status

| Tool | Existing banner | Action | Before ≈ | After ≈ | Instructions preserved | Browser |
| --- | --- | --- | --- | --- | --- | --- |
| Daily Mood Check-in | Large photo + glows/leaf | Removed decor; compact header + smile icon | ~290–380px art band | **116px** header | Mood grid + 4-step flow | Verified |
| Digital Journal | Side 3D art 150–180px | Removed art; book-open icon | art present | compact | Prompts + private note | Test |
| Gratitude Journal | Already compact | No change | — | — | Next / prompts | Test |
| Daily Mindfulness | PS head art | Removed art; leaf icon | 96–150px art | compact head | Wizard + disclaimer | Test |
| Purpose & Values | PS head art | Removed art; sparkles | same | compact | Wizard | Test |
| Parenting Stress | PS head art | Removed art; users | same | compact | Wizard | Test |
| Child Routine | PS head art | Removed art; users | same | compact | Wizard | Test |
| Parent–Child Guide | PS head art | Removed art; users | same | compact | Wizard | Test |
| Career Interest | 330px 3D webp | Removed art; briefcase-business | ~330px | compact + Start | How-it-works + disclaimer | Verified |
| Skills & Strengths | Near-compact | No large art change | — | — | Start/Continue | Test |
| Water Intake | Functional welcome | No change | — | — | Demo / dashboard | N/A |
| Nutrition Tracker | Functional welcome | No change | — | — | Welcome / dashboard | N/A |
| BMI/BMR/TDEE | Large hero + pills + leaves | Removed; activity icon | art ≤420px | **100px** | Medical disclaimer + form | Verified |
| Weight Journey | Functional welcome | No change | — | — | Welcome / journey | N/A |
| Tarot Reflection | Decorative card stack | Removed stack; moon-star | stack ~9.5rem | compact | Steps + disclaimer + Start a Reading | Test (auth-gated in browser) |
| Monthly Money Planner | Shared PFM SVG art | Compact page-hero | art ~300px | **~196px** | Wizard + disclaimer | Verified |
| Net Worth | Shared PFM SVG | Compact + chart-pie | same | ~196px | Form | Test |
| Emergency Fund | Shared PFM SVG | Compact + shield-check | same | ~196px | Form + EF copy | Test |
| Financial Goal | Task 31 compact | **Preserved** | 179px (T31) | unchanged | Tasks 31–36 | Verified |
| Debt & EMI | Shared PFM SVG | Compact + calculator | art present | **196px** | Wizard + disclaimer | Verified (+390 mobile) |
| Retirement / Inflation / Savings | Shared PFM SVG | Compact | same | ~196px | Forms | Test |

**No change required (already compact / functional intro only):** Gratitude, Skills, Water, Nutrition, Weight Journey, Financial Goal.

---

## 6. Shared header architecture

- **PFM:** `pfm/partials/page-hero.blade.php` converted to compact (`yk-pfm__hero--compact`, `data-yk-tool-compact-header`); decorative SVG removed.
- **FG:** existing `goal-compact-header` preserved (Task 31).
- Tool-specific compact headers: Mood, BMI, Career, Journal, PS, Tarot — title row + registry SVG icon + short lead.
- Marker: `data-yk-tool-compact-header` for regression.

---

## 7. Before-and-after header heights (desktop Chromium)

| Page | Before (approx from CSS/audit) | After (measured) |
| --- | --- | --- |
| Debt & EMI / Money Planner (shared hero) | Decorative art max-width 300px + large H1 | **196px** compact header |
| BMI | Hero art ≤420px + pills | **100px** |
| Mood | Photo band ~290–380px | **116px** |
| Financial Goal | Task 31 already **179px** | Preserved |

---

## 8. SVG icon implementation

Via existing `YuktioneIconRegistry` / `<x-yk-icon>`:

| Tool family | Icon |
| --- | --- |
| Mood | `smile` |
| Journal | `book-open` |
| Mindfulness | `leaf` |
| Values | `sparkles` |
| Parenting tools | `users` |
| Career | `briefcase-business` |
| BMI | `activity` |
| Tarot | `moon-star` |
| Money Planner | `wallet` |
| Net Worth | `chart-pie` |
| Emergency Fund | `shield-check` |
| Other PFM | `calculator` |
| Financial Goal | `target` (unchanged) |

No emoji keyboard icons added. No new icon library.

---

## 9. Required instructions / disclaimers preserved

- PFM educational “not financial advice” leads  
- BMI “not medical advice”  
- Career aptitude disclaimer + Start Assessment  
- Tarot reflective disclaimer + Start a Reading / Continue Reading  
- Mood 4-step check-in controls  
- FG seven inputs + years slider + single-page form  

---

## 10. Modified files

| File | Change |
| --- | --- |
| `resources/views/frontend/sections/pfm/partials/page-hero.blade.php` | Compact header; art removed |
| `resources/views/frontend/sections/pfm/form.blade.php` | Per-tool hero icons |
| `resources/views/frontend/sections/pfm/results.blade.php` | Icons + `$isMoneyPlanner` |
| `resources/views/frontend/sections/pfm/history.blade.php` | Icons |
| `resources/views/frontend/sections/pfm/show.blade.php` | Icons |
| `resources/views/frontend/sections/mood-checkin.blade.php` | Compact header |
| `resources/views/frontend/sections/journal-write.blade.php` | Art removed |
| `resources/views/frontend/sections/journal-history.blade.php` | Art removed |
| `resources/views/frontend/sections/bmi-bmr/form.blade.php` | Compact header |
| `resources/views/frontend/sections/career-interest/welcome.blade.php` | Art removed |
| `resources/views/frontend/sections/ps/form.blade.php` | Art removed + icons |
| `resources/views/frontend/sections/tarot/intro.blade.php` | Stack removed + compact |
| `public/frontend/assets/css/yuktione-pfm.css` | Compact hero styles |
| `public/frontend/assets/css/yuktione-mood.css` | Compact overrides |
| `public/frontend/assets/css/yuktione-bmi-bmr.css` | Compact overrides |
| `public/frontend/assets/css/yuktione-career-interest.css` | Compact overrides |
| `public/frontend/assets/css/yuktione-ps.css` | Compact overrides |
| `public/frontend/assets/css/yuktione-tarot.css` | Compact overrides |
| `public/frontend/assets/css/yuktione-journal.css` | Hide hero art |
| `tests/Feature/ToolsCompactHeaderTest.php` | **New** Task 37 tests |

---

## 11. Removed or retained assets

- **Not deleted from disk:** tool illustration webps / mood photo (still used by catalogue cards / other pages).  
- **Removed from tool entry markup:** PFM hero SVG, mood photo band, BMI hero image/pills/leaves, career 3D art, PS head art, tarot decorative stack, journal side art.  
- No new JS dependencies.

---

## 12. Database changes

**None.**

---

## 13. Automated tests

| Suite | Result |
| --- | --- |
| `ToolsCompactHeaderTest` | **6 passed, 110 assertions** |
| Financial Goal (Tasks 31–36) | **25 passed, 322 assertions** |
| Combined FG + Task 37 + PfmCalculator + PfmTools | **67 passed**; **1 failed** (unrelated) |

---

## 14. Existing unrelated test failures

`PfmToolsTest` Emergency Fund dataset: expects `Save result` but EF UI correctly shows **Save Calculation** (same known MMP/EF label mismatch documented in Task 36). **Not weakened** for Task 37.

---

## 15. Actual browser verification

**Method:** Cursor IDE browser → `http://127.0.0.1:8000` (local).  
**Not** physical-device testing.

Verified interactively:

- Debt & EMI compact + first loan fields  
- Monthly Money Planner compact + income field  
- BMI compact + form controls  
- Mood compact + mood radios  
- Career compact + Start Assessment  
- Financial Goal compact preserved + years slider  

Tarot intro is auth-gated in browser; covered by `ToolsCompactHeaderTest`.

---

## 16. Responsive verification

| Viewport | Method |
| --- | --- |
| 390×844 | Device metrics override — Debt & EMI mobile screenshot |
| Desktop | Default tab screenshots |
| 360–1440 | CSS stacking / single-column compact headers |

No empty banner wrappers observed. WhatsApp/help tab remains edge-affixed.

---

## 17. Accessibility

- Single H1 per tool entry  
- Decorative art removed with `aria-hidden` stacks gone  
- Icons decorative via registry / title icons  
- Functional CTAs remain keyboard-reachable  
- Disclaimers retained as text  

---

## 18. Screenshots

`docs/audits/task-37-global-tools-banner-removal/`

| File | Content |
| --- | --- |
| `01-debt-emi-compact.png` | Debt & EMI compact header |
| `02-bmi-compact.png` | BMI compact |
| `03-mood-compact.png` | Mood compact |
| `04-career-compact.png` | Career compact |
| `05-financial-goal-preserved.png` | FG Tasks 31–36 intact |
| `06-debt-emi-mobile-390.png` | Mobile 390 emulation |
| `07-money-planner-compact.png` | Money Planner compact |

---

## 19. Performance

No measured bundle-size delta. Decorative images no longer requested on modified entry pages (browser Network not re-baselined). Do **not** claim quantified performance gains.

---

## 20. Remaining limitations

- Physical-device QA not run.  
- Tarot live browser check requires signed-in customer.  
- Illustration asset files retained on disk for catalogue cards.  
- Known EF/MMP Save-label assertion mismatch remains outside Task 37.  
- Debt & EMI detailed layout polish belongs to later tasks (header only here).

---

## 21. Previous tasks remain functional

Financial Goal Tasks 31–36: **25/322** green; form still `yk-goal--single` with years slider; compact FG header unchanged.

---

## 22. Unrelated website sections unaffected

Homepage `yk-hero`, tools catalogue `catalog-page-hero`, category/service/professional pages, auth, admin — not redesigned. Asserted via source markers + tools index smoke.

---

## Stop

Task 37 complete. Awaiting further instructions. Do **not** start Task 38.
