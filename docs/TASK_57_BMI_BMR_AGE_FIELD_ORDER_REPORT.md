# Task 57 — BMI, BMR & TDEE: Move Age Below Weight

**Status:** Implemented and verified  
**Date:** 2026-11-26  
**Scope:** DOM field reorder only. Formulas, gender options, Task 56 compact header, results/history UI unchanged. Tasks 58–62 not started.

---

## 1. Implementation status

Age was moved from the **About You** row (beside Gender) into **Body Measurements**, immediately after the Weight block and before Height. Order is real DOM order (not CSS-only `order`), so keyboard tab order matches the visual layout.

---

## 2. Existing calculator architecture

| Item | Value |
|------|--------|
| Route | `GET /tools/bmi-bmr` (`tools.bmi-bmr`) |
| Calculate | `POST /tools/bmi-bmr/calculate` |
| Controller | `BmiBmrController` |
| Form | `resources/views/frontend/sections/bmi-bmr/form.blade.php` |
| CSS | `public/frontend/assets/css/yuktione-bmi-bmr.css` |
| Engine | `BmiBmrCalculator` (unchanged) |

Single combined BMI / BMR / TDEE calculator.

---

## 3. Previous field order

1. Gender  
2. **Age** (same row as Gender on ≥560px)  
3. Weight  
4. Height  
5. Activity  

Accessibility tree before fix: `Age` spinbutton appeared before Weight controls.

---

## 4. Updated field order

1. Gender  
2. **Weight** (with unit toggle)  
3. **Age** (full block: label, `#bmi-age`, min/max, hint)  
4. Height  
5. Activity  

Desktop (≥560px) CSS grid areas:

```
"weight height"
"age    height"
```

Mobile: vertical stack Weight → Age → Height.

---

## 5. Desktop layout verification

At 768px / 1280px / 1440px (Chromium device metrics):

- Weight and Height share the first metrics row.
- Age sits under Weight (`ageTop` > `weightTop`).
- Prefer not to say remains; compact header markers remain.

Filled baseline showed live BMI **24.7** with Weight `80` above Age `30`.

---

## 6. Mobile layout verification

At 360px / 390px:

- Weight above Age above Height.
- No horizontal overflow observed at 390px check.
- Labels and help text readable.

**Method:** Cursor IDE browser / CDP **emulation**, not physical devices.

---

## 7. Keyboard navigation verification

Focusable sequence inside `[data-bmi-form]` (names):

`sex` → `weight_unit` → `weight_kg` → **`age`** → `height_unit` → `height_cm` → `activity` …

Matches visual Weight-before-Age order. `#bmi-age` still uses `for="bmi-age"` / `id="bmi-age"`.

---

## 8. Validation compatibility

HTML5 constraints preserved:

| Field | Limits | Sample message |
|-------|--------|----------------|
| Age | min 1, max 120 | Value must be greater than or equal to 1 |
| Weight kg | min 10, max 400 | Value must be greater than or equal to 10 |

Server-side invalid age/weight still produce session errors (`BmiBmrAgeFieldOrderTest`). Input names and `old()` restoration unchanged. Session restore after calculate still showed Male / 80 / 30 / 180 / Sedentary.

---

## 9. Calculation compatibility

Synthetic baseline (Male, 30, 80 kg, 180 cm, Sedentary):

| Metric | Expected | Verified |
|--------|----------|----------|
| BMI | 24.7 | Browser results + tests |
| BMR | 1780 | Tests + results page |
| TDEE | 2136 | Tests |

No formula or API changes.

---

## 10. Modified files

| File | Change |
|------|--------|
| `resources/views/frontend/sections/bmi-bmr/form.blade.php` | Moved Age block below Weight |
| `public/frontend/assets/css/yuktione-bmi-bmr.css` | Metrics grid areas; About You single column; mobile spacing |
| `tests/Feature/BmiBmrAgeFieldOrderTest.php` | **Added** |
| `tests/Feature/BmiBmrTdeeBannerRemovalTest.php` | Comment only (points field order to Task 57) |
| `docs/TASK_57_BMI_BMR_AGE_FIELD_ORDER_REPORT.md` | This report |
| `docs/audits/task-57-bmi-bmr-age-field-order/*` | Screenshots |

---

## 11. Database changes

**None.**

---

## 12. Automated test results

```
BmiBmrAgeFieldOrderTest ………… 5 passed
BmiBmrTdeeBannerRemovalTest … 5 passed
BmiBmrTest ……………………… passed
BmiBmrCalculatorTest ………… passed
```

**Slice:** `24 passed (182 assertions)`.

**Pre-existing (documented, not weakened):** `ToolsCompactHeaderTest` PFM icon assertion expects `data-yk-icon="calculator"` while Retirement compact header uses `wallet`. Unrelated to Task 57.

Task 56 had no obsolete DOM-order assertion that needed updating beyond the class docblock note.

---

## 13. Actual browser verification

Local: `http://127.0.0.1:8001/tools/bmi-bmr`

- Weight before Age (DOM + visual + tab).  
- Compact header intact; no decorative banner.  
- Prefer not to say still present.  
- Calculate → results BMI 24.7 / Healthy weight (BMR/TDEE baseline covered by tests).  
- Native validation on Age/Weight after reorder.  
- No JS errors observed during fill/submit.  

---

## 14. Responsive verification

| Width | Method | Age below Weight |
|-------|--------|------------------|
| 360 | Emulation | Yes |
| 390 | Emulation | Yes |
| 430 | Emulation (CSS stack same as ≤559) | Yes (DOM) |
| 768 | Emulation | Yes (under Weight; Height beside Weight) |
| 1024+ | Emulation / CSS areas | Yes |
| 1280 / 1440 | Emulation | Yes |

Physical-device testing not performed.

---

## 15. Screenshots

`docs/audits/task-57-bmi-bmr-age-field-order/`

| File | Content |
|------|---------|
| `01-before-age-beside-gender.png` | Before: Age beside Gender |
| `02-desktop-updated-form.png` | Updated desktop form |
| `03-desktop-weight-then-age-filled.png` | Weight then Age filled (live BMI 24.7) |
| `04-mobile-weight-then-age.png` | Mobile Weight → Age |
| `05-validation-weight-and-age.png` | Invalid weight/age messages |
| `06-results-baseline-unchanged.png` | Results after calculate |

---

## 16. Remaining limitations

- Physical phones/tablets not tested.  
- Authenticated history UI not re-shot (unchanged; covered by `BmiBmrTest`).  
- Pre-existing CompactHeader PFM icon failure remains.

---

## 17. Task 56 compatibility

Retained: `data-yk-tool-compact-header`, `yk-bmi__hero--compact`, `yk-bmi--compact-header`, title, lead/disclaimer, no `yk-bmi__hero-art`. `BmiBmrTdeeBannerRemovalTest` still green.

---

## 18. Tasks 58–62 not started

- Prefer not to say **still present** (Task 58 not done).  
- No stored-input formatting, health meters, history meters, or edit UI changes.

No commit / push / deploy.
