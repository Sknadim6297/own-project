# Production Deployment Checklist — YuktiOne (Hostinger)

**Use only after explicit deployment approval.**  
Companion: `docs/PRIORITY_05_PRODUCTION_READINESS_REPORT.md`, `docs/PRODUCTION_ROLLBACK_PLAN.md`.

Legend: `[ ]` required · `(local)` already done in audit · `(prod)` must be done on Hostinger

---

## 0. Pre-flight (before touching production)

- [ ] Written approval to deploy Priorities 1–4
- [ ] Client layout decision: keep **A** (`single-row`) unless B/C approved
- [ ] Release commit exists on agreed branch (do not deploy dirty uncommitted trees)
- [ ] Confirm Hostinger PHP is **8.1–8.3** (Laravel 10)
- [ ] Confirm production Git remote/branch and SSH/panel access
- [ ] Confirm production DB name/user (do not paste secrets into tickets)
- [ ] Confirm SMTP credentials and authorized From address for YuktiOne domain
- [ ] Confirm production `APP_URL` is the real public HTTPS origin (no trailing path)

---

## 1. Production Git status

On the Hostinger app directory (SSH or Git panel):

```bash
cd /path/to/yuktione   # replace with actual Hostinger path
git status
git branch -vv
git remote -v
git log -5 --oneline
php -v
php artisan --version
php artisan migrate:status
```

- [ ] `(prod)` Working tree clean (or only expected env files)
- [ ] `(prod)` Note current HEAD commit for rollback
- [ ] `(prod)` Record which of `2026_11_24_*` / `2026_11_27_*` migrations are Pending vs Ran

---

## 2. Backup database

```bash
# Example mysqldump (adjust host/user/db; prefer Hostinger backup UI if available)
mysqldump -h DB_HOST -u DB_USER -p DB_NAME > ~/backups/yuktione-db-$(date +%Y%m%d-%H%M%S).sql
```

- [ ] `(prod)` Dump completed and stored **outside** the web root
- [ ] `(prod)` Spot-check dump size / header non-empty

---

## 3. Backup application files and uploaded images

```bash
# Preserve storage uploads and current release
tar -czf ~/backups/yuktione-app-$(date +%Y%m%d-%H%M%S).tar.gz \
  --exclude=node_modules --exclude=vendor \
  /path/to/yuktione

# Explicit media (adjust if using public/storage)
tar -czf ~/backups/yuktione-storage-$(date +%Y%m%d-%H%M%S).tar.gz \
  /path/to/yuktione/storage/app/public \
  /path/to/yuktione/public/storage
```

- [ ] `(prod)` Code + `storage/app/public` backed up
- [ ] `(prod)` Do not overwrite backups

---

## 4. Safely update application code

```bash
cd /path/to/yuktione
git fetch origin
git checkout main          # or release branch
git pull origin main       # or: git merge --ff-only <approved-sha>
```

- [ ] `(prod)` Deployed SHA matches approved commit
- [ ] `(prod)` Ensure `public/hot` is **absent** on production (Vite HMR file)

---

## 5. Install dependencies

```bash
composer install --no-dev --optimize-autoloader --no-interaction

# Only if this release requires Vite-built assets under public/build:
# npm ci
# npm run build
```

- [ ] `(prod)` Composer succeeded without `--ignore-platform-reqs` on Hostinger PHP
- [ ] `(prod)` Skip npm unless `public/build` is required for this release (P1–P4 home assets are under `public/frontend/assets/`)

---

## 6. Environment configuration (no secrets in git)

Update Hostinger `.env` (panel or SSH). Required for this release:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-PRODUCTION-DOMAIN

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=465   # or 587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=ssl   # or tls
MAIL_FROM_ADDRESS=noreply@YOUR-YUKTIONE-DOMAIN
MAIL_FROM_NAME="YuktiOne"

# Keep until client approves B/C:
YUKTIONE_TOPIC_LAYOUT=single-row
```

- [ ] `(prod)` `APP_DEBUG=false`
- [ ] `(prod)` `APP_URL` matches public HTTPS site
- [ ] `(prod)` SMTP From is authorized (not legacy Tazen)
- [ ] `(prod)` `YUKTIONE_TOPIC_LAYOUT=single-row`

---

## 7. Run approved migrations

```bash
php artisan migrate --force
```

Expected new/pending for this bundle (if not already Ran):

1. `2026_11_24_120000_create_professional_password_reset_tokens_table` (may already be Ran)
2. `2026_11_27_120000_add_category_fields_to_services_table`
3. `2026_11_27_120100_add_service_fields_to_sub_services_table`
4. `2026_11_27_120200_create_category_tool_table`
5. `2026_11_27_120300_seed_canonical_categories`

- [ ] `(prod)` `migrate:status` shows these as Ran
- [ ] `(prod)` No unexpected migrations pending from unrelated WIP

**Forbidden:** `migrate:fresh`, `migrate:refresh`, `db:wipe`.

---

## 8. Category seeder (idempotent; optional re-run)

```bash
php artisan db:seed --class=CanonicalCategorySeeder --force
```

- [ ] `(prod)` Nine categories present / unchanged harmlessly
- [ ] `(prod)` Confirm uploaded category images still intact

---

## 9. Frontend assets

```bash
# P1–P4 homepage CSS/JS ship as static public files — verify timestamps after pull:
ls -la public/frontend/assets/css/yuktione-categories.css \
       public/frontend/assets/css/yuktione-tools.css \
       public/frontend/assets/js/yuktione-home.js

# Only if Vite assets are part of the release:
# npm run build
```

- [ ] `(prod)` Static YuktiOne assets present
- [ ] `(prod)` No `public/hot`

---

## 10. Storage symlink, caches

```bash
php artisan storage:link
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# After .env is correct:
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- [ ] `(prod)` `public/storage` links correctly
- [ ] `(prod)` Caches rebuilt with production config

---

## 11. Smoke-test (production)

Use `docs/PRIORITY_05_PRODUCTION_READINESS_REPORT.md` § smoke list and:

- [ ] Homepage loads; Explore by Need shows categories; Tools strip scrolls
- [ ] Customer forgot password → **real inbox** email with working link
- [ ] Professional forgot password → **real inbox** email with working link
- [ ] `/tools` shows 23 tools / nine chips
- [ ] Legacy `/categories/mental-health` redirects
- [ ] Admin Category + Service screens load
- [ ] `/?topic_layout=two-row` preview works and **does not** persist after visiting `/`
- [ ] `storage/logs/laravel.log` — no new critical errors

---

## 12. Post-deploy sign-off

- [ ] Deploy SHA recorded
- [ ] Backup paths recorded
- [ ] Smoke checklist signed
- [ ] If anything fails → follow `docs/PRODUCTION_ROLLBACK_PLAN.md`

---

*Do not treat this checklist as authorization to deploy. Wait for explicit approval.*
