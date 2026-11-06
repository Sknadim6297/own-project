# F14 — YuktiOne Technical Readiness & Mobile Dashboard QA Closure

**Date:** 2026-11-06  
**Project root verified:** `C:\xampp\htdocs\own-project`  
**Stack:** Laravel 10.49.1 / PHP 8.2.12  
**App exercised:** `http://127.0.0.1:8000`  
**Local env preserved:** `APP_ENV=local`, `APP_DEBUG=true` (not production)  
**Production deployment:** not performed  
**Legal P0:** remains **OPEN** (do not treat as launch-ready)

---

## Final technical readiness verdict

**TECHNICAL CONDITIONAL GO**

F13 technical blockers addressed for local/QA posture:

- Production debug safety hard-fail is in place (including against stale `config:cache`).
- Customer and Professional intended-URL / dashboard CTA Feature tests pass.
- Mobile authenticated header CTAs verified in browser for both guards.
- Unit suite and Tools-related Feature suite pass.

**Not launch-ready.** Legal P0 (approved Terms/Privacy + verified operator identity) remains OPEN. Live legal documents were not changed in F14.

---

## 1. Files inspected

| Area | Paths |
|------|--------|
| Env / config | `.env` (local only; values not copied here), `.env.example`, `config/app.php` |
| Debug guard | `app/Providers/AppServiceProvider.php` |
| Config cache | `bootstrap/cache/config.php` — **absent** (no stale cached debug) |
| Deployment | No Dockerfile / docker-compose / GitHub Actions deploy manifests found in repo root |
| Auth / intended URL | `app/Support/Auth/IntendedUrl.php`, `app/Http/Middleware/Authenticate.php`, `app/Http/Middleware/RedirectIfAuthenticated.php`, `app/Http/Controllers/Frontend/LoginController.php`, `app/Http/Controllers/Professional/ProfessionalController.php` |
| Header CTAs | `resources/views/frontend/sections/header.blade.php`, `public/frontend/assets/css/yuktione-navbar.css` |
| Tests | `tests/Unit/ProductionDebugSafetyTest.php`, `tests/Feature/CustomerLoginHomeRedirectTest.php`, `tests/Feature/ProfessionalLoginIntendedRedirectTest.php`, Tools Feature tests |
| Prior audit | `docs/audits/f13/00-F13-FINAL-PUBLIC-READINESS-REPORT.md` |

---

## 2. Files modified (F14 scope)

| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Boot-time hard fail when `APP_ENV=production` and `config('app.debug') === true` |
| `.env.example` | Comments requiring production `APP_DEBUG=false` and `config:clear` / safe `config:cache` rebuild |
| `tests/Unit/ProductionDebugSafetyTest.php` | **Added** — asserts default, provider guard, and `.env.example` documentation |

**Not modified in F14:** local `.env` (debug left `true` for development), payments/GST, migrations, legal blades, visual redesign, production server.

Auth/header work that F14 QA validated originated in prior F11/F13 auth-nav work and was **not redesigned** here.

---

## 3. Root causes (F13 carry-over)

1. **Production debug risk:** Local/example posture used `APP_DEBUG=true`. A stale `php artisan config:cache` on a server could keep `debug=true` even after `.env` correction, exposing Ignition/stack traces.
2. **Auth intended URL / mobile dashboard CTA:** Prior gaps (customer defaulting to home, professional ignoring intended URL, missing professional mobile CTA, host mismatch `localhost` vs `127.0.0.1`) were fixed before F14; F14 confirmed closure via tests + browser QA.

---

## 4. Exact fixes

### Production debug safety

In `AppServiceProvider::boot()`:

- If environment is `production` **and** `config('app.debug') === true`, throw `RuntimeException` with guidance to set `APP_DEBUG=false` and run `php artisan config:clear` (or rebuild cache with safe values).
- This runs on every request and therefore catches **cached** unsafe debug values, not only live `env()` reads.
- `config/app.php` already defaults `'debug' => (bool) env('APP_DEBUG', false)`.
- `.env.example` documents the production requirement; local `.env` remains `APP_DEBUG=true`.

### Auth / mobile dashboard (validated, not redesigned in F14)

- Guard-scoped intended URL helper + middleware/controllers restore safe same-guard destinations.
- Public header exposes **Go to Customer Dashboard** / **Go to Professional Dashboard** when the respective guard is authenticated.
- Pending Professional login still lands on pending restriction path (Feature-covered).

---

## 5. Customer mobile results

**Account:** existing QA Customer (`qa.customer@yuktione.local`) — password not recorded in this report.

| Check | Result |
|-------|--------|
| Login from protected page (`/user/dashboard` → login) | **PASS** — redirected to login |
| Intended URL restoration after login | **PASS** — landed on `/user/dashboard` |
| 375px drawer CTA label | **PASS** — **Go to Customer Dashboard** |
| CTA href | **PASS** — `/user/dashboard` |
| Dashboard content | **PASS** — Customer workspace for QA Customer |
| Logout | **PASS** — `/user/logout` → home |
| Guest navigation after logout | **PASS** — drawer shows **Login** / **Sign Up** |
| 768 / 1024 / 1440 CTA presence | **PASS** via Feature header assertion + earlier CDP checks; full Emulation loop became flaky mid-session after navigations (see §9) |
| Horizontal overflow @375 | **PASS** (`scrollWidth` not greater than `clientWidth`) |
| Guard isolation | **PASS** — Professional session cannot use Customer routes without Customer auth (Professional hit on `/user/dashboard` → login) |

**Note:** When Customer logged in while a prior Professional session still existed in the same browser, the drawer briefly showed **both** dashboard CTAs. This is Laravel multi-guard session coexistence, not cross-guard route access. After Customer logout, guest Login/Sign Up returned.

---

## 6. Professional mobile results

**Account:** existing accepted QA Professional (`qa.pro@yuktione.local`) — password not recorded in this report.

| Check | Result |
|-------|--------|
| Authenticated drawer CTA | **PASS** — **Go to Professional Dashboard** |
| CTA href | **PASS** — `/professional/dashboard` |
| Dashboard navigation | **PASS** — Professional workspace, Welcome back QA Professional |
| Intended URL / status gates | **PASS** — Feature tests: intended panel restore; pending still restricted; cross-guard intended rejected |
| Customer route isolation | **PASS** — `/user/dashboard` while only Professional authenticated → login |
| Horizontal overflow @375 | **PASS** |
| Logo asset | **PASS** — `/frontend/assets/Yuktione_logo.png` visible in drawer screenshots |

Rejected Professional browser path: **NOT RE-RUN in interactive browser** this session; covered by Feature test `pending professional still lands on pending page`.

---

## 7. Test results

Commands run from `C:\xampp\htdocs\own-project`. Failing tests were **not** hidden or rewritten.

| Suite / filter | Result |
|----------------|--------|
| `ProductionDebugSafetyTest` | **3 passed** |
| `CustomerLoginHomeRedirectTest` | **8 passed** (dashboard default, intended URL, host alternate, cross-guard reject, external reject, guest middleware, header CTA) |
| `ProfessionalLoginIntendedRedirectTest` | **5 passed** (default dashboard, intended restore, cross-guard reject, pending gate, header CTA) |
| Combined auth + debug filter | **16 passed** (43 assertions) |
| `php artisan test --testsuite=Unit` | **35 passed** (153 assertions) |
| Tools-related Feature filter (`ToolsPageTest`, `ToolsCatalogueTest`, `Phase12ToolsConsistencyTest`, plus auth CTA matches) | **24 passed** in re-run set |

No new test failures introduced for the F14 scope above.

---

## 8. Browser screenshots

Stored under `docs/audits/f14/screenshots/`:

| File | What it shows |
|------|----------------|
| `01-customer-375-drawer-cta.png` | Customer authenticated mobile drawer with **Go to Customer Dashboard** (and dual CTA when Professional session also present) |
| `01-customer-mobile-dashboard-cta.png` | Earlier Customer mobile CTA capture |
| `02-professional-375-drawer-cta.png` | Professional authenticated mobile drawer with **Go to Professional Dashboard** |
| `02-professional-mobile-dashboard-cta.png` | Professional mobile CTA capture |
| `03-guest-home-after-logout.png` | Guest drawer after Customer logout (Login / Sign Up path; logo visible) |

Brand colors/logo in screenshots match existing YuktiOne header (navy wordmark + green/blue mark). No Vite `public/hot` / `[::1]:517x` overlay observed.

**Console / assets:** No `vite-error-overlay`. One expert card image path returned broken (`professionals_photos/job-bussiness-career.jpg`) — pre-existing asset gap, unrelated to F14 auth/debug scope.

---

## 9. Remaining blockers

### Legal (blocks production launch — unchanged)

- **Legal P0 OPEN:** approved Terms/Privacy and verified legal operator identity still required.
- Live legal pages were **not** published or rewritten in F14.
- Suntree Consulting LLP remains a **proposed** operator only (per F12/F13).

### Technical / QA residuals (non-launch legal, but track)

1. **Production env must be set correctly on the real host** (`APP_ENV=production`, `APP_DEBUG=false`) before any public deploy. The AppServiceProvider guard fails closed if debug is left on; operators must clear/rebuild config cache after env changes.
2. **CDP device Emulation** for 768/1024/1440 became unreliable after multiple navigations in this browser session (metrics sometimes stuck at desktop width). 375px authenticated drawer QA and Feature header tests remain the primary evidence; re-spot-check wider breakpoints manually if needed.
3. **Automation click on drawer dashboard CTA** sometimes closed the drawer instead of navigating (overlay/hit-target). Href targets were verified via CDP; dashboards reached via intended-login redirect and direct navigation. Manual click verification recommended once on device.
4. **Dual-guard session UI:** both CTAs can appear if Customer and Professional sessions coexist in one browser — expected multi-guard behavior; route isolation still holds.
5. Pre-existing broken expert photo asset path noted above — out of F14 scope.

---

## 10. Final technical readiness verdict (restated)

| Dimension | Verdict |
|-----------|---------|
| F14 production debug safety | **CLOSED** for codebase (runtime guard + docs + unit tests); local debug preserved |
| Customer mobile login / CTA / intended URL / logout | **VERIFIED** |
| Professional mobile login / CTA / isolation / pending Feature gate | **VERIFIED** (pending via tests) |
| Auth + Unit + Tools regression | **PASS** |
| Legal P0 | **OPEN** |
| Production launch-ready? | **NO** — technical conditional go only |

**Stop after F14.** No F15 work started. No production deployment. No payment/GST/migration/legal document changes.
