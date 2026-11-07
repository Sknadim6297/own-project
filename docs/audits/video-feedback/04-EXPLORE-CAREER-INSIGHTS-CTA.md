# Video Issue 04 — Explore Career Insights CTA

**Date:** 2026-11-07  
**Project root:** `C:\xampp\htdocs\own-project`  
**Laravel:** 10.49.1  
**Scope:** Remove nonessential **Explore Career Insights** CTA from career-assessment result surfaces  
**Out of scope:** Issue 05 button-position redesign, scoring, migrations, auth/payment/legal, other tools (Mood, Calorie, etc.)

---

## 1. Root cause and CTA destination

### Investigation finding

The brief named the **Career Interest Assessment** saved-result page. That page **does not** (and did not after Issue 03) render **Explore Career Insights**. Its actions are **View History**, **Retake**, and **Delete**.

The exact label **Explore Career Insights** existed only on **Skills & Strengths Assessment** (career-guidance sibling tool):

| Location | Before | Destination |
|----------|--------|-------------|
| `resources/views/frontend/sections/skills-strengths/result.blade.php` | Secondary CTA | `route('blog.index')` → general `/blog` listing |
| `resources/views/frontend/sections/skills-strengths/profile.blade.php` | Secondary CTA | same `blog.index` |

### What the click did

Opened the public **Blog** index — a generic content listing, **not** a skills/career-insights product tied to the assessment result, scoring, or saved attempt. No JavaScript special-case; plain `<a href>`.

### Essential dependency?

**No.** Assessment complete → profile → save → result / history / retake / delete / expert discovery do **not** depend on this link. Blog routes and controllers were left in place.

Stakeholder line *“Explore Career Insights not relevant now”* matches this dead-end blog CTA. Removal was safe.

---

## 2. Whether removal was safe

**Yes.**

- No scoring / recorder / draft changes.
- No route or controller deletion.
- Issue 03 Career Interest **View History** untouched and still present.
- Remaining SSA actions: Back to history, Career Expert discovery, Retake, Delete (result); Retake, Career Expert, Save (profile).

---

## 3. Files modified

| File | Change |
|------|--------|
| `resources/views/frontend/sections/skills-strengths/result.blade.php` | Removed Explore Career Insights CTA |
| `resources/views/frontend/sections/skills-strengths/profile.blade.php` | Removed same CTA (same nonessential mid-flow button) |
| `tests/Feature/SkillsStrengthsTest.php` | Assert CTA absent on profile + saved result |

**Not modified:** Career Interest blades/controllers (Issue 03 preserved), `blog.index`, ExpertsDiscovery, scorers, migrations.

---

## 4. Tests executed and results

```bash
php artisan test --filter="SkillsStrengthsTest|CareerInterestTest"
```

**21 passed** (12 Career Interest + 9 Skills Strengths), **169 assertions**.

Verified:

- Assessment submission / save-after-login still works (SSA guest flow).
- Results still render.
- Career Interest **View History** still asserted in `CareerInterestTest`.
- **Explore Career Insights** no longer visible on SSA profile or saved result.
- Blog footer links unaffected (CTA label removed only).

---

## 5. Responsive verification

App: `http://127.0.0.1:8000`

| Viewport | Result |
|----------|--------|
| Career Interest result | No Explore CTA; actions View History / Retake / Delete |
| SSA result @ 375px | CTA gone; actions Back to history / Connect with a Career Expert / Retake / Delete; **no horizontal overflow** |
| SSA result @ 768px | Same; no overflow |
| 1024 / 1440 | CDP desktop metrics flaky (same host limitation as Issue 03/F14); actions present, no Explore CTA, no overflow when measurable |

No empty dedicated “slot” left — flex `yk-ssa__actions` gap continues to space remaining controls.

---

## 6. Remaining issues

- None blocking Issue 04.
- Brief’s “Career Interest result page” attribution was inaccurate; the live CTA was on **Skills & Strengths**. Career Interest needed no CTA removal.
- Issue 05 (broader button positioning) not started.

---

## Verdict

**Issue 04 complete.** Nonessential **Explore Career Insights** → Blog CTAs removed from Skills & Strengths profile and saved-result pages. Career Interest Issue 03 left intact. Stopped before Issue 05.
