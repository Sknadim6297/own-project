# Priority 05 — Production Readiness Report

**Project:** YuktiOne Laravel Website  
**Date:** 2026-11-27  
**Audit type:** Pre-deployment only (no commit / push / deploy / production data changes)  
**Branch:** `main` (tracking `origin/main` @ `4e1d3ec`)  
**Remote:** `https://github.com/Sknadim6297/own-project.git`

---

## Final verdict: **NO-GO** for production release right now

Local Priorities 1–4 are substantially implemented and the **P1–P4 scoped automated suites pass**, but production release is blocked until the items in §12 are cleared.

| Classification | Status |
|----------------|--------|
| Locally verified (P1–P4 scope) | Yes — see §6 / §8 |
| Production verified | **No** — not deployed; SMTP/`APP_URL` unproven on Hostinger |
| Not tested | Full booking/payment E2E on prod-like copy; physical devices; real inbox delivery; Hostinger PHP version |
| Client approval pending | Priority 4 layout choice (A/B/C) — **A remains default** |
| Deployment blockers | Uncommitted release bundle; production mail/`APP_URL`; Hostinger PHP confirm; prefer staging migrate |

---

## 1. Complete change audit

### 1.1 Git state

- **Staged:** none  
- **Unstaged modified:** 35 files (~+1369 / −649)  
- **Untracked:** migrations, seeders, `CategoryCatalog`, `TopicLayout`, Priority 02–04 docs/screenshots, new Feature tests, admin `_form` partial  
- **Secrets:** `.env` is gitignored and **not** tracked. Safe.  
- **Experimental:** Priority 4 B/C layouts are opt-in only (`topic_layout` default `single-row`).  
- **Unrelated / noise risk:** ~1.7MB screenshot PNGs under `docs/priority03-screenshots` and `docs/priority04-screenshots` (ok for docs; optionally omit from deploy artifact).  
- **Missing deliverable:** no `docs/PRIORITY_01_*.md` file found (P1 work exists in code/tests).  
- **Merge conflicts:** none observed locally; branch up to date with `origin/main` before these local changes.  
- **Compiled frontend:** YuktiOne home CSS/JS edited under `public/frontend/assets/` (no Vite build required for those). Vite `public/build` absent; `public/hot` present locally (dev only — must not ship `public/hot` to production).

### 1.2 Files ready for deployment (after commit approval)

| Area | Paths |
|------|--------|
| P1 recovery | `AccountRecovery`, Forgot/Professional/API password controllers, `AccountRecoveryTest`, `.env.example` mail/`APP_URL` comments |
| P1 migration (already on `origin/main`) | `2026_11_24_120000_create_professional_password_reset_tokens_table.php` |
| P2 categories | `CategoryCatalog`, `CategoryTool`, Service/SubService models, Admin CRUD views/controllers, 4× `2026_11_27_120*` migrations, `CanonicalCategorySeeder` |
| P2/P3 catalogs | `ToolsCatalog`, `ExploreCatalog`, `CategoryHub`, CategoryController redirects |
| P3 scroller | `yuktione-home.js`, categories/tools CSS |
| P4 layout (default A) | `TopicLayout`, `config/yuktione.php`, home partials, CSS modifiers |
| Docs/tests | Priority 02–05 docs, Priority02–04 tests, updated scroller/recovery tests |

### 1.3 Incomplete / do-not-enable

- Priority 4 **B/C must not be set** as `YUKTIONE_TOPIC_LAYOUT` without client approval.  
- Local `MAIL_MAILER=log` is **not** production email proof.  
- Excel feedback tasks beyond P1–P4: **not started** (per instructions).

---

## 2. Password recovery

| Check | Evidence | Status |
|-------|----------|--------|
| Customer email-only recovery | `ForgotPasswordController` + `AccountRecovery::sendCustomerReset` | Locally verified (tests) |
| Professional email-only recovery | `Professional\AuthController` + `sendProfessionalReset` | Locally verified (tests) |
| Separate brokers/tables | `config/auth.php`: `users` → `password_reset_tokens`; `professionals` → `professional_password_reset_tokens` | Locally verified |
| Token isolation | `PasswordResetTokenIsolationTest` **PASS** | Locally verified |
| Expire / throttle | 60 min expire, 60 s throttle in `config/auth.php` | Config verified |
| Role-specific URLs | Relies on `APP_URL` for absolute reset links | **Production blocker** if `APP_URL` wrong |
| Professional token migration | `2026_11_24_120000_…` already on `origin/main`, Ran locally batch 2 | Code on remote; still confirm Ran on Hostinger |
| Actual SMTP | Local `MAIL_MAILER=log` | **Not production-verified** |
| Queue | Reset send is synchronous (`QUEUE_CONNECTION=sync` locally); schedule empty | OK for sync mail; if prod uses queue workers, confirm mail not queued |

**Do not claim real email delivery from local log mailer.**

---

## 3. Dynamic categories and services

| Check | Evidence | Status |
|-------|----------|--------|
| Nine client categories | Local DB: 9 active; homepage 9 | Locally verified |
| CRUD | Admin Service/SubService controllers + P2 tests | Locally verified |
| Table compatibility | Additive columns on `services` / `sub_services`; no rename | Designed safe |
| `category_tool` | 23 pivot rows locally; FK to `services` + `tools` | Locally verified |
| 23 tools | `tools=23` | Locally verified |
| Idempotent seeder | `CanonicalCategorySeeder` + P2 test | Locally verified |
| Bookings / professionals | Additive only; no destructive wipe | Risk low if migrate only |
| Legacy redirects | CategoryController + aliases | Locally verified in P2 tests |
| Images / SEO | Seeder should not overwrite uploaded production images (confirm seeder update rules before prod) | **Confirm on staging** |
| Sitemap | P2 report: static sitemap may still hold legacy hosts | Known follow-up |

---

## 4. Scroller and layout

| Item | Status |
|------|--------|
| Priority 3 scroll (arrows, swipe, keyboard, last card, hide when no overflow) | Locally verified (browser CDP + tests) |
| Priority 4 default **Option A** `single-row` | Confirmed (`config('yuktione.topic_layout')=single-row`) |
| Options B/C preview via `?topic_layout=` | Implemented; **query does not write config/DB** |
| Preview affecting SEO canonical | Layout: `url()->current()` in `layouts/layout.blade.php` (no query string). Preview `?topic_layout=` does not persist config/DB. Avoid promoting preview URLs as marketing links. |
| Client screenshots | `docs/priority04-screenshots/` | Emulated only |

---

## 5. Migration inventory (pending on a fresh Hostinger DB)

Locally these are already **Ran** (batch 3). On production they are expected **Pending** until migrate.

### 5.1 `2026_11_24_120000_create_professional_password_reset_tokens_table.php`

| Field | Detail |
|-------|--------|
| Tables | Creates `professional_password_reset_tokens`; may clear `password_reset_tokens` on up |
| Data | Clears outstanding shared reset tokens (forces re-request) |
| FK | None |
| Locking | Brief table create + delete |
| Rollback | Drops professional token table; does **not** restore cleared user tokens |
| Notes | Already committed on `origin/main` — confirm status on Hostinger |

### 5.2 `2026_11_27_120000_add_category_fields_to_services_table.php`

| Field | Detail |
|-------|--------|
| Tables | `services` |
| Columns added | `short_description`, `icon_name`, `icon_path`, `display_order`, `show_on_homepage`, `show_in_explore_tools`, `discovery_needles`, `tone` |
| Data | Existing rows keep data; new cols nullable/defaults |
| Locking | `ALTER TABLE` — MySQL may lock depending on version/engine |
| Rollback | Drops those columns (loses Admin-entered metadata) |

### 5.3 `2026_11_27_120100_add_service_fields_to_sub_services_table.php`

| Field | Detail |
|-------|--------|
| Tables | `sub_services` |
| Columns | `slug` (unique), `meta_title`, `meta_description`, `display_order` |
| Risk | Unique `slug` — ensure no duplicate non-null slugs before migrate |
| Rollback | Drop unique + columns |

### 5.4 `2026_11_27_120200_create_category_tool_table.php`

| Field | Detail |
|-------|--------|
| Tables | Creates `category_tool` |
| FKs | `service_id` → `services`, `tool_id` → `tools` (cascade delete) |
| Data | Empty until seeder |
| Rollback | Drop table (loses mappings) |

### 5.5 `2026_11_27_120300_seed_canonical_categories.php`

| Field | Detail |
|-------|--------|
| Tables | Writes via `CanonicalCategorySeeder` into `services` + `category_tool` |
| Data | Idempotent upsert of nine categories + tool maps |
| Rollback | **Non-destructive** (rows retained) |
| Risk | Confirm seeder does not overwrite production-uploaded `image` / SEO fields unexpectedly |

**Never run `migrate:fresh`, `db:wipe`, or destructive seeders on production.**

---

## 6. Automated testing

### 6.1 P1–P4 scoped (locally verified PASS)

```
AccountRecoveryTest
PasswordResetTokenIsolationTest
PasswordResetTokenIsolationReproduceTest
Priority02CategoryServiceCatalogTest
Priority03ScrollerTest
Priority04TopicLayoutTest
SolutionsHorizontalScrollTest
ToolsScrollerTest
ToolsVisibilityTest
TopicLabelFormattingTest
NineToolsCategoriesTest
```

Focused P1–P4 + recovery/isolation reconfirm: **70 passed** (590 assertions), 2 PHP 8.5 PDO deprecations.

### 6.2 Full suite

A full `php artisan test` run was executed during this audit. Mid-run / partial log observation: **many FAIL suites outside P1–P4** (examples: `CareerInterestScorerTest`, `AdminPsychologySettingsTest`, `AdminToolRegistryTest`, `CategoryIntegrityFilteringTest`, `DigitalJournalHistoryTest`, `DigitalJournalWriteTest`, `ExpertsDirectoryTest`, `ExplorePageTest`, `ExploreToolsFormattingTest`, …). Rough mid-run class counts observed ~31 FAIL headers vs ~117 PASS headers before completion.

**Treat out-of-scope failures as pre-existing unless proven introduced by P1–P4.** Named P1–P4 suites in §6.1 passed in the same overall audit window.

**Do not claim the entire application test suite is green.** Full-suite wall-clock on this PHP 8.5 local environment is long and noisy (PDO deprecations + HTML assertion dumps).

### 6.3 Explicitly not claimed as working

- Production SMTP inbox delivery  
- Hostinger runtime  
- Full booking + payment money path on production  
- Physical-device touch (emulation only)  
- Mobile native app binary (API contracts partially covered by existing Feature API tests)

---

## 7. Production environment (Hostinger-oriented)

| Item | Finding |
|------|---------|
| Laravel | 10.x (`composer.json`) |
| PHP constraint | `^8.1` — prefer Hostinger **8.1–8.3** (local audit machine is PHP **8.5** with historical `--ignore-platform-reqs` risk) |
| Composer | Required on deploy (`composer install --no-dev --optimize-autoloader`) |
| Node/Vite | Needed only if deploying packages that rely on `public/build`; home scroller CSS/JS are static under `public/frontend/assets/` |
| Storage | `storage/` + `bootstrap/cache` writable; `php artisan storage:link` |
| Cache/queue | File/sync common on shared hosting; if Redis/queue used, configure workers |
| Scheduler | `app/Console/Kernel` schedule currently empty — cron optional |
| Git | Production should pull approved commit from `main` after review |
| Credentials | Do not commit `.env`; set Hostinger `.env` separately |

---

## 8. Evidence snapshot (local)

| Metric | Value |
|--------|-------|
| Active categories | 9 |
| Homepage categories | 9 |
| Tools | 23 |
| category_tool | 23 |
| topic_layout | `single-row` |
| mailer | `log` (local only) |
| queue | `sync` |
| APP_URL | `http://127.0.0.1:8001` (local) |

---

## 9. Companion documents

- `docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md`  
- `docs/PRODUCTION_ROLLBACK_PLAN.md`  
- Prior reports: `docs/PRIORITY_02_*.md`, `docs/PRIORITY_03_*.md`, `docs/PRIORITY_04_*.md`

---

## 10. Client approval pending

1. Priority 4 layout: keep **A**, or enable **B** / **C**.  
2. Explicit written approval to commit + deploy P1–P4 bundle.  
3. Confirm production mail domain / from-address (not legacy Tazen).

---

## 11. GO criteria (when to flip to GO)

All must be true:

1. Changes committed (no secrets) and pushed to the agreed branch.  
2. Hostinger PHP version compatible (≥8.1, ideally ≤8.3).  
3. Production `.env`: real `APP_URL`, SMTP (`MAIL_MAILER=smtp` or provider), authorized `MAIL_FROM_*`.  
4. DB backup + file/image backup completed.  
5. Migrations dry-checked on a copy (or accepted risk on additive alters).  
6. Post-deploy smoke checklist green (including **real** reset email).  
7. `YUKTIONE_TOPIC_LAYOUT` remains `single-row` unless client approved B/C.

Until then: **NO-GO**.

---

## 12. Deployment blockers (summary)

1. Work is **uncommitted** / not an approved release artifact.  
2. **Production email + APP_URL** not verified with a real inbox.  
3. **Hostinger PHP** not confirmed against Laravel 10.  
4. Full suite has **non-P1–P4 failures** — triage before blaming this release.  
5. Prefer migrate on a **DB copy** first (not done in this audit).  
6. P4 client confirmation still open (default A is safe).

---

*End of Priority 05 readiness report. No production changes were made.*
