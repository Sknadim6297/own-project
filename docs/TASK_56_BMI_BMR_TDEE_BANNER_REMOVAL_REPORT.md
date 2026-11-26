# Task 56 — BMI, BMR & TDEE Calculator Banner Removal Report

**Status:** Verified complete (already implemented by Task 37; no redundant presentation changes in Task 56)  
**Date:** 2026-11-26  
**Scope:** Presentation-only. Tasks 57–62 not started. No formula, field-order, gender-option, meter, or history redesign changes.

---

## 1. Implementation status

Task 56 is **already satisfied** by the Task 37 global tools banner-removal / compact-header work. Local inspection and browser verification confirm:

- No oversized decorative hero art, pills, or leaf banner on `/tools/bmi-bmr`.
- Compact header marker `data-yk-tool-compact-header` + `yk-bmi__hero--compact` present.
- Title **BMI, BMR & TDEE Calculator** and approved short lead remain.
- Calculator form, calculate action, results, save/history behaviour unchanged.

No new Blade/CSS redesign was required for Task 56. Work delivered: automated lock test, browser verification, screenshots, and this report.

---

## 2. Affected route and component

| Item | Value |
|------|--------|
| Route | `GET /tools/bmi-bmr` → `tools.bmi-bmr` |
| Calculate | `POST /tools/bmi-bmr/calculate` → `tools.bmi-bmr.calculate` |
| Results | `GET /tools/bmi-bmr/results` → `tools.bmi-bmr.results` |
| History | `tools.bmi-bmr.history` / `tools.bmi-bmr.history.show` |
| Controller | `App\Http\Controllers\Frontend\BmiBmrController` |
| Form view | `resources/views/frontend/sections/bmi-bmr/form.blade.php` |
| CSS | `public/frontend/assets/css/yuktione-bmi-bmr.css` (`.yk-bmi__hero--compact`) |
| JS | `public/frontend/assets/js/yuktione-bmi-bmr.js` (unchanged) |
| Lead copy | `ToolPageLeads::forBmi()` |

BMI, BMR and TDEE are a **single combined calculator** (one entry route). No separate BMI/BMR/TDEE entry points were created or removed.

---

## 3. Existing calculator architecture

1. Guest/user opens `BmiBmrController@create` → `bmi-bmr/form.blade.php`.
2. Compact header + live preview aside + measurement form (`data-bmi-form`).
3. POST calculate → session draft → results blade.
4. Optional auth-gated save → `BmiBmrRecorder` / `BmiBmrCalculation`.
5. History list/show for owned records.
6. Engine: `BmiBmrCalculator` (WHO adult BMI + Mifflin–St Jeor BMR × activity → TDEE).

---

## 4. Identified banner / spacing problem (historical)

Pre–Task 37 the BMI form used a large hero band (art ≤ **~420px**), decorative pills, and leaf artwork that pushed the first inputs far down the viewport.

Task 37 removed that art and introduced the compact header. Task 56 re-verified that state; the oversized banner is **absent**.

---

## 5. Banner removal (current)

Absent from the form markup:

- `yk-bmi__hero-art`
- `bmi-hero-woman`
- `yk-bmi__pills` / promotional pill rows
- Decorative `yk-bmi__leaf` hero wrappers (aside leaf for live preview remains functional UI, not a promotional banner)

Shell class `yk-bmi--compact-header` keeps compact CSS active.

---

## 6. Compact header implementation

```blade
<header class="yk-bmi__hero yk-bmi__hero--compact" … data-yk-tool-compact-header>
  …
  <h1>…BMI, BMR & TDEE Calculator…</h1>
  <p class="yk-bmi__lead" data-yk-tool-page-lead>{{ ToolPageLeads::forBmi() }}</p>
</header>
```

**Preserved approved wording** (not replaced with the suggested alternate):

> Estimate body mass index, metabolic rate, and daily energy requirements. Educational estimates only—not medical advice.

Page structure: Back to Tools → compact title + lead → form / preview → Calculate Results → medical disclaimer in lead.

---

## 7. Before-and-after header dimensions

| State | Approximate header / art band | First interactive field |
|-------|-------------------------------|-------------------------|
| Pre–Task 37 (documented) | Hero art ≤ **~420px** + pills | Form pushed below large banner |
| Post–Task 37 / Task 56 desktop | Compact header **~100–131px** (`[data-yk-tool-compact-header]` ~131px in live Chromium) | First number input ~**407px** from viewport top (desktop) |
| Task 56 mobile emulation 390×844 | Compact header ~**168px** | Form reachable without banner scroll waste |

Reference: Task 37 report — BMI art ≤420px → **~100px** compact header.

---

## 8. Input field compatibility

Unchanged:

- Gender: Female / Male / Prefer not to say (`unspecified`)
- Age before Weight (Task 57 not started)
- Height/weight units cm|ft/in, kg|lb
- Activity multipliers / five activity radios
- Excluded-population checkbox
- Calculate Results CTA

---

## 9. Calculation formula compatibility

Synthetic fixture (male, 30, 80 kg, 180 cm, sedentary):

| Metric | Value |
|--------|-------|
| BMI | **24.7** (Healthy weight) |
| BMR | **1,780** kcal |
| TDEE | **2,136** kcal |

Identical inputs → identical outputs in unit + feature tests and browser results page. No formula edits.

---

## 10. Result and history compatibility

- Results route still shows BMI / BMR / TDEE cards and formula version chip.
- Save-after-login and ownership/delete covered by existing `BmiBmrTest`.
- History UI not redesigned (Tasks 61–62 not started).

---

## 11. Modified / added files (Task 56)

| File | Change |
|------|--------|
| `tests/Feature/BmiBmrTdeeBannerRemovalTest.php` | **Added** — presentation + formula lock |
| `docs/TASK_56_BMI_BMR_TDEE_BANNER_REMOVAL_REPORT.md` | **Added** — this report |
| `docs/audits/task-56-bmi-bmr-tdee-banner-removal/*` | **Added** — verification screenshots |

No production Blade/CSS/JS/DB changes required for Task 56 (already compact from Task 37).

---

## 12. Database changes

**None.**

---

## 13. Automated test results

```
BmiBmrTdeeBannerRemovalTest … 5 passed
BmiBmrCalculatorTest … passed
BmiBmrTest … passed
ToolsLayoutConsistencyTest … passed
ToolsShortDescriptionTest … passed
ToolsVisibilityTest … passed
TopicLabelFormattingTest … passed
ToolsCompactHeaderTest … 1 pre-existing failure
```

**Suite slice:** `1 failed, 48 passed (752 assertions)`.

**Pre-existing failure (not weakened):**

- `ToolsCompactHeaderTest::pfm tools use compact header without decorative art` — known PFM calculator **icon assertion** failure from earlier tasks; unrelated to BMI banner removal. BMI assertion inside the same class (`mood bmi career journal tarot ps drop large decorative banners`) **passes**.

Legacy catalogue chip markup failures noted in prior task reports remain out of scope for Task 56.

---

## 14. Actual browser verification

Server: `http://127.0.0.1:8001` (local).

Workflow: Open Calculator → enter synthetic details → Calculate Results → results showed **24.7 / Healthy weight / 1,780 / 2,136**.

Checks:

- Large decorative banner absent (`hasDecorativeArt: false`)
- Compact title + lead visible
- Input form earlier in viewport vs historical ~420px art band
- Fields / gender options / validation surface unchanged
- No calculator JS console blockers observed during submit
- WhatsApp/help float did not cover primary controls

---

## 15. Responsive verification

| Width | Method | Result |
|-------|--------|--------|
| 390px | Chromium device metrics (emulation) | Compact header readable; no promo banner |
| 360 / 430 / 768 / 1024 / 1280 / 1440 | CSS compact stacking + prior Task 37 responsive notes | Compact header remains; form usable |

**Emulation vs physical device:** All Task 56 checks used **browser/CDP emulation** in Cursor’s IDE browser, not physical phones/tablets.

---

## 16. Accessibility verification

- `h1` present; lead text readable; medical disclaimer retained in lead.
- Gender radios remain keyboard-focusable (label-associated).
- Focus rings / existing form controls preserved (no control reordering).
- No clipped compact title at 390px in screenshot.

---

## 17. Before-and-after screenshots

Directory: `docs/audits/task-56-bmi-bmr-tdee-banner-removal/`

| File | Content |
|------|---------|
| `01-reference-task37-bmi-compact.png` | Post–Task 37 BMI compact reference (pre-Task-56 baseline) |
| `02-desktop-compact-header.png` | Current desktop compact header |
| `03-desktop-form.png` | Current input form |
| `04-results-24-7.png` | Successful calculation result |
| `05-mobile-compact-header.png` | Mobile (390px) compact header |

**Honesty note:** A live “previous large banner” screenshot is not available in-repo after Task 37 removed the art. Historical dimensions are taken from `TASK_37_GLOBAL_TOOLS_BANNER_REMOVAL_REPORT.md` (BMI art ≤420px → ~100px). Task 56 screenshots document the **current verified compact** state.

History screen: guest session used for form→results; authenticated history not re-shot in this pass; history routes/tests remain covered by `BmiBmrTest`.

---

## 18. Remaining limitations

- Historical large-banner PNG not retained; dimensions documented via Task 37.
- Authenticated history screenshot deferred (behaviour locked by automated tests).
- Physical-device QA not performed.
- Pre-existing `ToolsCompactHeaderTest` PFM icon failure remains.

---

## 19. Unrelated tools unaffected

Homepage hero, tools catalogue banners, Tarot artwork, other tool compact headers, and FG Tasks 31–36 layouts were not modified in Task 56. Scoped CSS assertions in `BmiBmrTdeeBannerRemovalTest` confirm homepage hero does not reference `yk-bmi__hero--compact`.

---

## 20. Tasks 57–62 not started

Confirmed **not** implemented in this work:

- Task 57 — Move Age below Weight  
- Task 58 — Remove Prefer Not to Say  
- Task 59 — Stored input formatting  
- Task 60 — Health meter and final summary  
- Task 61 — History health meter and input details  
- Task 62 — Edit functionality  

Gender still includes Prefer not to say; Age still appears before Weight; no new health meters or history edit UI introduced.

---

## Verdict

**Task 56 complete by verification.** Banner already removed under Task 37 compact-header architecture; automated regression lock added; browser workflow and screenshots recorded. No commit / push / deploy performed.
