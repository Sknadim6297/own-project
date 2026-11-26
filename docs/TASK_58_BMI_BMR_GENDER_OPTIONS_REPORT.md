# Task 58 — BMI, BMR & TDEE: Remove Prefer Not to Say

**Status:** Implemented and verified  
**Date:** 2026-11-26  
**Scope:** BMI calculator web form + web calculate validation only. Historical `unspecified` records preserved. Tasks 59–62 not started.

---

## 1. Implementation status

Prefer not to say is removed from the BMI calculator gender UI. Only **Female** and **Male** remain selectable. New web calculate submissions reject `unspecified` and other unsupported values. Calculator engine still understands `unspecified` so saved history does not break.

---

## 2. Existing calculator architecture

| Item | Value |
|------|--------|
| Route | `GET /tools/bmi-bmr` |
| Calculate | `POST /tools/bmi-bmr/calculate` |
| Controller | `BmiBmrController` |
| Form | `resources/views/frontend/sections/bmi-bmr/form.blade.php` |
| Catalog | `BmiBmrCatalog` (`sexKeys` vs `webSexKeys`) |
| Engine | `BmiBmrCalculator` (unchanged formulas) |

No dedicated public BMI API routes exist (web routes only).

---

## 3. Previous gender options

Female · Male · Prefer not to say (`unspecified`)

Prefer not to say skipped BMR/TDEE (binary-sex Mifflin–St Jeor) while still allowing adult BMI.

---

## 4. Updated gender options

Female · Male only (`yk-bmi__seg--2`, equal column widths).

---

## 5. Backend validation changes

- `BmiBmrCatalog::webSexKeys()` → `['male', 'female']`
- `BmiBmrCatalog::sexKeys()` still includes `unspecified` for engine/history
- `BmiBmrController@calculate` validates `Rule::in(webSexKeys())`
- Missing / `unspecified` / other values → session errors on `sex`

---

## 6. Historical record compatibility

Synthetic saved record with `sex = unspecified` remains readable:

- Show page loads
- BMI **24.7** preserved
- BMR/TDEE remain ineligible / null as originally calculated
- Stored `sex` not rewritten to Male/Female

---

## 7. Existing draft compatibility

`create()` clears draft/old `sex === unspecified` to empty string and does **not** auto-select Male or Female. Other draft inputs (age, weight, height, activity) are kept. User must choose Male or Female before calculate.

---

## 8. Male calculation verification

30 / 80 kg / 180 cm / sedentary → BMI **24.7**, BMR **1780**, TDEE **2136**

---

## 9. Female calculation verification

Same body inputs → BMI **24.7**, BMR **1614**, TDEE **1937**

---

## 10. API compatibility

No separate BMI HTTP API found. Restriction applies to the web calculate endpoint only. Engine/`sexKeys()` still accept `unspecified` for historical payloads and unit tests.

---

## 11. Modified files

| File | Change |
|------|--------|
| `app/Support/BmiBmr/BmiBmrCatalog.php` | Added `webSexKeys()` |
| `app/Http/Controllers/Frontend/BmiBmrController.php` | Web validation + draft sex clear |
| `resources/views/frontend/sections/bmi-bmr/form.blade.php` | Two options; `seg--2` |
| `public/frontend/assets/css/yuktione-bmi-bmr.css` | `.yk-bmi__seg--2` |
| `tests/Feature/BmiBmrGenderOptionsTest.php` | **Added** |
| `tests/Feature/BmiBmrTdeeBannerRemovalTest.php` | Superseded Prefer-not-to-say asserts |
| `tests/Feature/BmiBmrAgeFieldOrderTest.php` | Superseded Prefer-not-to-say asserts |
| `docs/TASK_58_BMI_BMR_GENDER_OPTIONS_REPORT.md` | This report |
| `docs/audits/task-58-bmi-bmr-gender-options/*` | Screenshots |

---

## 12. Database changes

**None.** No migrations. Historical rows untouched.

---

## 13. Automated test results

```
BmiBmrGenderOptionsTest …… 7 passed
BmiBmrAgeFieldOrderTest …… 5 passed
BmiBmrTdeeBannerRemovalTest  5 passed
BmiBmrTest ………………… passed
BmiBmrCalculatorTest ……… passed (incl. unspecified energy skip)
```

**Slice:** `31 passed (243 assertions)`.

**Pre-existing (not weakened):** `ToolsCompactHeaderTest` PFM icon expects `calculator` but Retirement uses `wallet`.

---

## 14. Actual browser verification

Local `http://127.0.0.1:8001/tools/bmi-bmr`:

- Only Female / Male visible  
- Selected states work  
- Missing gender → “Please select one of these options.”  
- Male & Female calculate correctly  
- Weight → Age order preserved (Task 57)  
- Compact header preserved (Task 56)  

---

## 15. Responsive verification

| Width | Method | Result |
|-------|--------|--------|
| 390px | Emulation | Two equal options (~159px each); no third slot |
| 360–1440 | Emulation / CSS `--2` grid | Balanced two-column gender pills |

Physical-device testing not performed.

---

## 16. Accessibility verification

- Radiogroup still labelled “Gender”  
- Options remain label-associated  
- Required radios; focus + selected styles retained  
- Keyboard can move between Female and Male  

---

## 17. Screenshots

`docs/audits/task-58-bmi-bmr-gender-options/`

| File | Content |
|------|---------|
| `01-before-three-gender-options.png` | Before: three options |
| `02-desktop-female-male-only.png` | Updated desktop (Female selected) |
| `03-desktop-male-selected-attempt.png` | Desktop capture mid-flow |
| `04-mobile-female-male-only.png` | Mobile two options |
| `05-missing-gender-validation.png` | Missing gender validation |
| `06-male-results.png` | Male results |
| `07-female-results.png` | Female results |

Historical compatibility covered by automated test (authenticated show). No live auth history screenshot in this pass.

---

## 18. Remaining limitations

- Physical devices not tested  
- Live authenticated history UI not re-shot (covered by tests)  
- Pre-existing CompactHeader PFM icon failure remains  

---

## 19. Tasks 56 and 57 compatibility

Compact header markers and Weight-before-Age DOM order still green in Task 56/57 tests and browser checks.

---

## 20. Unrelated gender options

No site-wide Prefer-not-to-say removal. `SEX_UNSPECIFIED` constant and `sexKeys()` retained. Registration/profile surfaces not modified.

---

## 21. Tasks 59–62 not started

No stored-input formatting, health meters, history meters, or edit UI changes.

No commit / push / deploy.
