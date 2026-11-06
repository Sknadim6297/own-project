# F13 — YuktiOne Final Public Website Regression & Production Readiness Audit

**Date:** 2026-11-06  
**Scope:** Read-only audit after F01–F12 and auth redirect / mobile dashboard navigation fix  
**Application code modified during F13:** **None** (report only)  
**F14 / fixes:** **Not started**

---

## 1. Executive summary

Technical public-site regression is largely healthy: key public routes respond correctly, built Vite assets load without a stale `public/hot` file, auth intended-redirect Feature tests pass (13/13), programmes remain disabled, Community/Shop remain placeholders, and guest mobile navigation works at 375×812.

**Legal P0 remains OPEN.** Live Terms/Privacy still present Tazen marketplace content and unverified Suntree/Tazen contacts. F12A/F12B review packages exist; Gates A–H are not closed. Suntree Consulting LLP remains a **proposed** operator only.

**Final verdict: CONDITIONAL GO**

Meaning: technical checks for the public website are in acceptable shape for continued QA, but production publication remains blocked on explicit legal/business approvals and limited verification gaps listed below. Legal approval gaps are **not** treated as a technical pass.

---

## 2. Environment verification

| Item | Result |
|------|--------|
| Working directory | `C:\xampp\htdocs\own-project` |
| Git branch | `main` (tracking `origin/main`; local dirty tree includes prior auth/nav work — not modified by F13) |
| PHP | 8.2.12 |
| Laravel | 10.49.1 |
| `APP_ENV` | `local` |
| `APP_DEBUG` | `true` (**production risk if deployed as-is**) |
| `APP_URL` | `http://localhost` |
| DB connection | `mysql` |
| DB database name | `yuktione_fresh_qa` (credentials not disclosed) |
| App URL exercised | `http://127.0.0.1:8000` |
| `public/hot` | **Absent** |
| `public/build/manifest.json` | **Present** (mtime 06 Nov 2026 12:04:45) |
| Asset mode | **Built assets** (`/build/assets/...`); no `[::1]` / `:5173` / `:5174` references on sampled pages |

`npm run build` was **not re-executed** in F13 to preserve read-only constraints; existing production build was verified via HTTP 200 on CSS/JS assets.

---

## 3. Route audit results

`php artisan route:list` → **842 routes** listed successfully (no missing-controller fatal during listing).

### Public / guest HTTP probe (executed)

| Path | Status | Notes |
|------|--------|-------|
| `/` | 200 | Home |
| `/gridlisting` | 200 | Explore preserved |
| `/professionals` | 200 | Expert listing |
| `/professionals/details/1` | 200 | Expert detail |
| `/login` | 200 | Customer login UI |
| `/register` | 200 | Customer register |
| `/professional/login` | 200 | Professional login UI |
| `/professional/register` | 200 | Professional register |
| `/tools` | 200 | Tools catalogue |
| `/tools/psychology/mood` | 200 | Mood tool entry |
| `/about`, `/contact`, `/help` | 200 | Content pages |
| `/terms`, `/privacy` | 200 | Legal pages (content issues → §11) |
| `/career` | 200 | Careers listing (`route('career')`) |
| `/careers` | **404** | Not a registered route; footer correctly uses `/career` |
| `/programmes` | **302 → `/gridlisting`** | Programmes disabled behavior intact |
| `/user/dashboard` (guest) | **302 → `/login`** | Guard boundary OK |
| `/professional/dashboard` (guest) | **302 → `/professional/login`** | Guard boundary OK |
| `/tools/psychology/mood/history` (guest) | **302 → `/login`** | History protected OK |

### Intentionally preserved / not reintroduced

- `gridlisting` present and reachable.
- Programmes nav omitted (`programmes_enabled=false`); `/programmes` redirects to Explore.
- Community / Shop remain `placeholder => true` (“Soon”) in header.

### Route issues

| Issue | Severity | Evidence |
|-------|----------|----------|
| `/careers` 404 if typed manually | P2 | Probe; footer uses `route('career')` → `/career` |
| Duplicate `/forgot-password` registrations in `web.php` (first lacks `guest`) | P2 | Pre-existing route duplication (noted in prior auth work) |

---

## 4. Authentication redirect results

### Code reviewed (read-only)

- `app/Support/Auth/IntendedUrl.php` — guard-specific keys; host allowlist includes localhost↔127.0.0.1; cross-guard path rejection; auth-entry loop blocks
- `app/Http/Middleware/Authenticate.php` — remembers intended per `user` / `professional` before guest redirect
- `app/Http/Middleware/RedirectIfAuthenticated.php` — authenticated visitors resolve intended or role dashboard (not Home for customers)
- `app/Http/Controllers/Frontend/LoginController.php` — post-login uses `IntendedUrl::pull('user')` then booking then `user.dashboard`; request `redirect` ignored
- `app/Http/Controllers/Professional/ProfessionalController.php` — accepted → `IntendedUrl::resolve('professional')`; pending/rejected status URLs unchanged
- Login Blade no longer posts query `redirect`

### Lifecycle verification

| Scenario | Evidence | Status |
|----------|----------|--------|
| Guest → customer protected → `/login` | HTTP 302 Location `/login` for `/user/dashboard`, mood history | **PASS** |
| Customer intended restore / dashboard fallback / external reject / cross-guard reject / alt host | Feature tests | **PASS** |
| Guest → professional protected → `/professional/login` | HTTP 302 Location `/professional/login` | **PASS** |
| Professional intended / dashboard / cross-guard / pending gate | Feature tests | **PASS** |
| Failed login preserves intended | Code: pull only on success; no dedicated failing-login Feature assertion | **PASS (code review)** / not separately asserted |
| Authenticated customer visits login → dashboard | Feature test | **PASS** |
| Open redirect via `redirect` body/query | Feature test + Blade no longer sets `redirect` | **PASS** |

### Focused auth tests (executed)

```
CustomerLoginHomeRedirectTest — 8 passed
ProfessionalLoginIntendedRedirectTest — 5 passed
Total: 13 passed (37 assertions)
```

---

## 5. Mobile navigation results

### Source inspection

`resources/views/frontend/sections/header.blade.php`:

- Guest: Login / Sign Up
- Customer: **Go to Customer Dashboard** → `route('user.dashboard')`
- Professional: **Go to Professional Dashboard** → `route('professional.dashboard')`
- Both CTAs can render if both guards authenticated
- No email, IDs, or KYC fields in nav CTAs (name + role label only in account chip)

### Browser (guest) — executed

At **375×812**, home mobile drawer:

- Opens/closes via toggle
- Shows Login + Sign Up
- Community/Shop show Soon placeholders
- No Programmes item
- Careers footer href → `/career`
- Horizontal overflow: **false**

### Authenticated Customer / Professional browser click-through

**NOT VERIFIED** — no authorized QA credentials were used; accounts were not created/reset per audit rules. Header CTAs for authenticated roles are covered by Feature view-render tests only.

---

## 6. Responsive browser results

Browser automation used (cursor-ide-browser) against `http://127.0.0.1:8000`.

| Viewport | Home overflow | Notes |
|----------|---------------|-------|
| 375×812 | none | Mobile drawer verified |
| 768×1024 | none | Emulation applied |
| 1024×768 | none observed | Emulation did not reliably lock width (reported ~1345); no overflow at reported width |
| 1440×900 | none observed | Emulation similarly not locking; host width ~1900; no overflow |

### Pages HTTP-verified (200) with build assets and no broken Vite hot refs

Home, Explore (`/gridlisting`), Experts, Expert detail, Customer login/register, Professional login/register, Tools, Mood tool, About, Contact, Help, Terms, Privacy, Career.

### Auth page styling

Login/register/professional register load `/build/assets/tazen-*.css`, `auth-theme-*.css` (and customer/professional panel CSS where applicable). No stale Vite hot URLs.

### Console / network

- Full multi-page console capture across all viewports: **not exhaustively instrumented**
- Favicon / logo / built CSS: HTTP **200**
- Font Awesome (cdnjs) present on main layout pages; standalone auth pages do not include FA (by design of those templates)

### Authenticated responsive dashboards

**NOT VERIFIED** in browser (see §5).

---

## 7. Build and asset results

| Check | Result |
|-------|--------|
| `public/hot` | Absent |
| Manifest / built CSS JS | Present; HTTP 200 |
| Auth pages use build assets | Yes |
| Broken Vite IPv6 hot refs | Not present on sampled HTML |
| `npm run build` re-run | **Not executed** (read-only) |
| Favicon `/favicon.ico`, `/tazen-favicon.png` | 200 |
| Logo asset | 200 |

---

## 8. Tools and feature results

### Config / registry (QA DB `yuktione_fresh_qa`)

- `programmes_enabled` = **false**
- Psychology: `daily-mood-check-in`, `digital-journal`, `gratitude-journal` → `active` / implemented
- `calorie-nutrition-tracker` → **`coming_soon`** (not advertised as calorie tool on `/tools` HTML sample; `calorie` string count = 0)
- Other nutrition-adjacent rows currently **`active`** in QA (`bmi-bmr-calculator`, `water-intake-tracker`, `weight-journey`) and appear on `/tools` — **current QA state**, differs from earlier F12A “all nutrition coming_soon” narrative
- Career tools also `active` in QA registry

### Behaviour checks

- Mood history guest → login redirect: **PASS**
- Programmes disabled + redirect: **PASS**
- Community/Shop placeholders: **PASS** (browser)
- No evidence of a fake food catalogue introduced for calorie tracker on public tools page

### Related automated tests (executed)

Filter `Tool|Mood|Journal|Gratitude|Nutrition|Programmes|Program`:

**94 passed (1251 assertions)** including programmes-disabled and tools catalogue/psychology tests.

---

## 9. Test suite results

| Suite / filter | Result | Notes |
|----------------|--------|-------|
| Unit | **32 passed / 147 assertions** | Matches prior healthy baseline |
| Auth redirect Feature | **13 passed / 37 assertions** | New/updated suite green |
| Tools-related filter | **94 passed / 1251 assertions** | Includes programmes disabled |
| Full Feature | **182 passed / 3 failed / 1836 assertions** | Was ~175/3 historically; pass count increased |

### Current Feature failures (all **pre-existing** pattern)

| Test | Error | Classification |
|------|-------|----------------|
| `Tests\Feature\ExampleTest::test_the_application_returns_a_successful_response` | Expected 200, got **500** under PHPUnit | **Pre-existing**; live `GET /` returns **200** outside PHPUnit |
| `Tests\Feature\SubServiceBookingTest::it_can_store_sub_service_data_in_booking` | Doctrine DBAL required to change `blog_banners` columns | **Pre-existing** |
| `Tests\Feature\SubServiceBookingTest::it_can_create_booking_without_sub_service` | Same Doctrine DBAL error | **Pre-existing** |

No evidence these three failures were introduced by the auth/nav fix (auth suites green; live home OK).

---

## 10. Security findings

| Area | Result |
|------|--------|
| Customer/professional guard redirects | Correct login targets for protected URLs |
| Intended URL open redirects | Rejected in tests; query `redirect` ignored |
| Cross-guard intended destinations | Rejected in tests |
| CSRF on login POSTs | Existing Laravel CSRF + JSON login flows retained (not weakened in F13) |
| Guest access to dashboards | Blocked (302) |
| `APP_DEBUG=true` | **P0 for production** if this env were deployed publicly |
| Ignition routes present in `route:list` | Expected in local; must not ship exposed in production |
| Public Terms/Privacy identity mismatch | Compliance/trust risk (see §11) — not an auth bypass |
| IDOR deep dive across all booking IDs | **Not exhaustively executed** in F13 |
| Destructive security tests | Not performed (per rules) |

---

## 11. Legal / compliance blockers

### Live pages (still inconsistent)

- `/terms` and `/privacy`: **Tazen (Part of Suntree Consulting LLP)** marketplace copy; contacts `help@tazen.in` / `help@Tazen.com`
- Customer registration agreement: Yuktione partial with placeholders
- Professional agreement: YuktiOne brand; operator unnamed / placeholders
- PDFs: Tazen-era artifacts still routed

### Review docs present

- `docs/legal/f12a/` — audit + drafts
- `docs/legal/f12b/` — operator verification checklist; Gates A–H **OPEN**

### Unresolved (explicit)

| Item | Status |
|------|--------|
| YuktiOne brand vs registered operator | Unresolved |
| Suntree Consulting LLP independently verified | **No** — proposed only in F12B; live Tazen wording ≠ verification |
| LLPIN / MCA evidence | Not in workspace |
| Registered office / jurisdiction / effective date | Not verified (`config` address null) |
| Authorized representative | Not verified |
| Grievance contact | Not verified |
| Booking / cancel / refund policy alignment | Unresolved (product refunds still limited) |
| Privacy for KYC + sensitive tool data | Drafted in F12A/B; not live |
| Customer vs Professional agreement consistency | Unresolved pending F12C |

**Legal P0: OPEN** — requires business owner + qualified legal approval before treating public legal surfaces as production-ready.

---

## 12. P0 / P1 / P2 issue register

### P0

1. **Legal identity & live Terms/Privacy mismatch** (Tazen marketplace vs YuktiOne product; unverified operator; wrong contacts).
2. **`APP_DEBUG=true` / local Ignition exposure** if this environment were used as public production without hardening.
3. **Legal Gates A–H incomplete** (F12B) — publication of revised legal docs not authorized.

### P1

1. Authenticated Customer/Professional **mobile dashboard click-through not browser-verified** in F13.
2. QA registry shows additional nutrition-adjacent tools as `active` (BMI/water/weight) while calorie tracker remains `coming_soon` — confirm intentional product state vs prior “nutrition coming soon” messaging.
3. Refund/cancellation policy still not aligned to implemented product capabilities (carried from F12).

### P2

1. Manual `/careers` URL 404 (canonical `/career` works).
2. Duplicate forgot-password route registrations.
3. PHPUnit `ExampleTest` 500 vs live 200 (test harness / sqlite fixture issue).
4. SubServiceBooking tests blocked on missing `doctrine/dbal`.
5. Desktop viewport emulation unreliable at 1024/1440 in automation host (no overflow seen at reported widths).

---

## 13. Exact files inspected

- `app/Support/Auth/IntendedUrl.php`
- `app/Http/Middleware/Authenticate.php`
- `app/Http/Middleware/RedirectIfAuthenticated.php`
- `app/Http/Controllers/Frontend/LoginController.php`
- `app/Http/Controllers/Professional/ProfessionalController.php`
- `resources/views/frontend/sections/header.blade.php`
- `resources/views/frontend/sections/footer.blade.php`
- `resources/views/frontend/sections/terms.blade.php`
- `resources/views/frontend/sections/privacy.blade.php`
- `resources/views/frontend/login/login.blade.php` (redirect param removal check)
- `config/yuktione.php`
- `docs/legal/f12a/*`, `docs/legal/f12b/*`
- `public/build/manifest.json` (presence)
- Route files via `php artisan route:list`

---

## 14. Tests and commands actually executed

- `git rev-parse` / `git status -sb`
- `php -v`, `php artisan --version`, `php artisan env`
- `.env` key presence check (`APP_*`, `DB_CONNECTION`, `DB_DATABASE` only)
- `Test-Path public/hot`, `public/build/manifest.json`
- `php artisan route:list`
- HTTP probes via `Invoke-WebRequest` / `curl.exe` for listed public/protected paths and assets
- `php artisan test --filter="CustomerLoginHomeRedirectTest|ProfessionalLoginIntendedRedirectTest"`
- `php artisan test --testsuite=Unit`
- `php artisan test --filter="Tool|Mood|Journal|Gratitude|Nutrition|Programmes|Program"`
- `php artisan test --testsuite=Feature`
- `php artisan test --filter=ExampleTest`
- Browser automation: home/login navigation, mobile drawer, overflow checks at 375 & 768
- One-off QA tools status query (temporary helper created then **deleted**; no persistent app change)

---

## 15. Tests / browser checks NOT executed (and why)

| Item | Why |
|------|-----|
| `npm run build` fresh rebuild | Read-only audit; avoided rewriting `public/build` |
| Authenticated Customer/Professional live browser login & dashboard CTA click | No authorized credentials used; no account creation |
| Exhaustive console/network audit on every page × every viewport | Time/tooling limits; sampled instead |
| Full IDOR matrix on all booking/chat endpoints | Out of destructive/deep security scope for this pass |
| Package installs / DB mutations | Forbidden |
| F12C legal publication | Not authorized; Legal P0 open |

---

## 16. Final production readiness verdict

# CONDITIONAL GO

### Conditions that remain before an unqualified GO

1. Close **Legal P0** (operator verification, counsel approval, aligned live Terms/Privacy/agreements, refund policy).
2. Confirm production env hardening (`APP_DEBUG=false`, Ignition locked down, correct `APP_URL`).
3. Optionally complete authenticated mobile CTA browser verification with approved QA accounts.
4. Confirm intentional QA state of nutrition-adjacent tools marked `active`.
5. Track pre-existing Feature test failures separately (do not treat as new auth regressions).

**F13 stops here. No F14 / no fixes applied.**
