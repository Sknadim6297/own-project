# Video Issue 03 — Career Assessment View History

**Date:** 2026-11-07  
**Project root:** `C:\xampp\htdocs\own-project`  
**Laravel:** 10.49.1  
**Scope:** Career Interest Assessment — customer access to previous saved results  
**Out of scope:** Scoring / recommender changes, Issue 04 (Explore Career Insights), Issue 05 (broader button positioning), payment / booking / legal

---

## Root cause

History **already existed** end-to-end (`tools.career-interest.history`, `CareerInterestAttempt` ownership filtering, history Blade). The stakeholder gap was **discoverability on the saved result page**: the prior CTA was effectively “Back to history” (or otherwise unclear), so customers on a result screen did not see a clear **View History** path to prior assessments.

Secondary gap: guest hits on history/result did not reliably remember a post-login destination the same way Mood / Journal history does (`session(['url.intended' => …])`).

---

## Whether history already existed

**Yes.** Reused; no duplicate tables, controllers, routes, or pages.

| Piece | Status before Issue 03 |
|-------|------------------------|
| Route `GET tools/career-interest/history` | Present |
| Route `GET tools/career-interest/result/{attempt}` | Present |
| Table `career_interest_attempts` | Present |
| Ownership via `user_id` + `authorizeAttempt()` | Present |
| History Blade with dates / top categories | Present |
| Clear **View History** on result | Missing / unclear |
| Explicit **View Result** label on history rows | Weak / missing |
| Friendly empty history copy | Incomplete |
| Guest → login with intended URL (Mood-style) | Incomplete |

---

## Files modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Frontend/CareerInterestController.php` | Guest history / result / destroy → login; remember `url.intended` (Mood pattern) |
| `resources/views/frontend/sections/career-interest/result.blade.php` | Primary **View History** CTA (replaces unclear back link) |
| `resources/views/frontend/sections/career-interest/history.blade.php` | **View Result** per row; empty-state copy |
| `resources/views/frontend/sections/career-interest/welcome.blade.php` | Authenticated **View History** secondary CTA |
| `public/frontend/assets/css/yuktione-career-interest.css` | `.yk-cit__list-cta` for history row action label |
| `tests/Feature/CareerInterestTest.php` | History access, ownership, guest intended URL, empty state, View History visibility |

**Not changed:** `CareerInterestScorer`, recommender, catalogue, migrations, other tools.

---

## Routes reused or added

**Reused only** (no new routes):

- `tools.career-interest.history` → `CareerInterestController@history`
- `tools.career-interest.result` → `CareerInterestController@result`
- `tools.career-interest.destroy` → `CareerInterestController@destroy` (auth + ownership unchanged)

---

## UI changes

- **Result page:** primary `yk-cit__btn` **View History** → existing history route; Retake / Delete remain secondary.
- **History page:** each saved attempt shows date (Asia/Kolkata), top interest category label(s), and **View Result**.
- **Empty history:** “No saved assessments yet” + short guidance to complete and save.
- **Welcome (signed-in customers):** secondary **View History** next to Start/Continue.
- Styling stays on existing YuktiOne career-interest tokens (`yk-cit__*`); no full redesign.

---

## Security checks

| Check | Behaviour |
|-------|-----------|
| Guest history / result / destroy | Redirect to customer `login`; `url.intended` set to requested history or result URL |
| After login | Intended URL restored (verified in browser JSON login response → `/tools/career-interest/history`) |
| Own history | Only `CareerInterestAttempt` rows for `Auth::guard('user')->id()` |
| Own result | `authorizeAttempt()` → 403 if `user_id` mismatch |
| Cross-customer result / delete | 403 |
| Professional guard | Unchanged; career routes use customer `user` guard checks only |

---

## Test results

```bash
php artisan test --filter="CareerInterestTest|CareerInterestScorerTest"
```

**16 passed** (12 feature + 4 scorer/unit), **117 assertions**.

Covered against the Issue 03 checklist:

1. Customer can open own assessment history  
2. Customer can open own previous result  
3. Customer cannot open another customer’s result  
4. Guest redirected to login  
5. Intended URL restored after login (`url.intended` + `IntendedUrl::pull('user')`)  
6. Empty history friendly state  
7. **View History** on result page  
8. Scoring unchanged (`CareerInterestScorerTest` still green)

---

## Browser verification results

App: `http://127.0.0.1:8000` (artisan serve).

| Check | Result |
|-------|--------|
| Guest `GET /tools/career-interest/history` | Redirect → `/login` |
| Post-login `redirect_url` | `…/tools/career-interest/history` (intended restored) |
| History lists saved attempt | Investigative + date + **View Result** |
| Result page actions | **View History**, Retake, Delete (View History first) |
| 375px mobile | View History visible; full-width stack; **no horizontal overflow** |
| 768px tablet | View History visible; no overflow |
| 1024 / 1440 desktop | CDP device metrics flaky in this environment (viewport often stayed ~1345); actions still present and ordered correctly with no overflow when measurable |

---

## Remaining issues

- None blocking Issue 03.
- CDP desktop viewport emulation remains intermittently unreliable in this automation host (same class of limitation noted in F14); mobile/tablet checks and feature tests are solid.
- Local QA seed user `cit-view-history-qa@example.com` may remain in the QA DB from browser verification; not required for the product fix.

---

## Verdict

**Issue 03 complete.** Existing Career Interest history was reused; result/welcome CTAs and guest intended-URL behaviour were the minimal fixes. Scoring untouched. Stopped before Issue 04 / Issue 05.
