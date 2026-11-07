# Video Issue 05 — Career Assessment Button Positioning

**Date:** 2026-11-07  
**Project root:** `C:\xampp\htdocs\own-project`  
**Laravel:** 10.49.1  
**Scope:** Career Interest Assessment **result** and **history** action-button layout only  
**Out of scope:** Scoring, migrations, other tools, auth/booking/payment/legal, Issue 03/04 feature undo

---

## 1. Root cause

Issue 03 left **View History** as a direct `<a class="yk-cit__btn">`. **Retake** / **Delete** (and history **Retake Assessment**) sit inside bare `<form>` wrappers inside `.yk-cit__actions`.

Mobile CSS already forced `.yk-cit__btn` / `.yk-cit__btn-secondary { width: 100% }`, but:

- The **link** is a flex child → stretches to full row.
- Each **form** is the flex child and shrink-wraps to content (~80–90px), so secondary buttons sat side-by-side under a full-width primary.

Measured before fix @ 375px on result:

| Control | Flex child width |
|---------|------------------|
| View History | 304px (full) |
| Retake (form) | 85px |
| Delete (form) | 83px (same row as Retake) |

That mismatch is the “buttons mislocated” mobile layout.

Issue 03 **View History** present; Issue 04 **Explore Career Insights** absent on Career Interest (correct).

---

## 2. Buttons affected

| Page | Buttons |
|------|---------|
| Result | View History, Retake Assessment, Delete |
| History | Retake Assessment, Assessment home (+ Back to Tools partial; View Result list CTA) |

Destinations unchanged (history / retake / destroy / assessment home / tools index / result).

---

## 3. Files modified

| File | Change |
|------|--------|
| `public/frontend/assets/css/yuktione-career-interest.css` | Actions flex rules for nested forms; mobile column stack; empty/list spacing helpers |
| `resources/views/frontend/sections/career-interest/result.blade.php` | Label **Retake** → **Retake Assessment** (same route) |
| `resources/views/frontend/sections/career-interest/history.blade.php` | Replace inline styles with classes; capitalize Retake Assessment consistently |

No controllers, routes, scorers, or Skills Strengths / Issue 04 files touched.

---

## 4. CSS / layout changes

- `.yk-cit__actions > form` → `display: flex; flex: 0 1 auto; min-width: 0; margin: 0` so forms take part in the row like links.
- Form-inner buttons `width: 100%` of the form.
- `@media (max-width: 720px)`: actions `flex-direction: column; align-items: stretch`; links + forms `width: 100%`; buttons full-width with normal wrap for long labels.
- `.yk-cit__actions--flush`, `.yk-cit__empty`, `.yk-cit__list-block` replace history inline margins.
- Preserved teal/navy tokens, 44px min-height, pill radius.

---

## 5. Mobile and desktop verification

App: `http://127.0.0.1:8000` (live browser + CDP).

| Viewport | Result page | History page |
|----------|-------------|--------------|
| **375 × 812** | All three actions **304px**, stacked, no overflow; Explore CTA absent | Retake + Assessment home full-width stack; Back to Tools + View Result present; no overflow |
| **768 × 1024** | Three actions **same row**, no overlap/overflow | — |
| **1024 × 768** / **1440 × 900** | CDP width emulation flaky (host often ~1345–1900); actions remain one row, no overflow when measured | — |

Click check: history row → `/tools/career-interest/result/1` succeeded; View History remains on result. No page JS errors observed during navigation.

---

## 6. Test results

```bash
php artisan test --filter="CareerInterestTest|CareerInterestScorerTest"
```

**16 passed** (12 feature + 4 scorer), **117 assertions**.

Scoring, result/history access, ownership, and guest intended-URL behaviour unchanged.

---

## 7. Remaining issues

- None blocking Issue 05.
- Desktop CDP viewport override remains unreliable on this automation host; 375 and 768 measurements are definitive.
- Video feedback Issues 01–05 closed for this series.

---

## Verdict

**Issue 05 complete.** Form-wrapped career action buttons now align and stack with View History on mobile; desktop row layout preserved. Issue 03/04 behaviour kept.
