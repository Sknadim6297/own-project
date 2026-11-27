# Production Rollback Plan — YuktiOne

**Important:** Rolling back Git code does **not** automatically reverse database migrations. Treat DB and code as separate rollback tracks.

Companion: `docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md`, `docs/PRIORITY_05_PRODUCTION_READINESS_REPORT.md`.

---

## When to rollback

- Site down / 500s after deploy
- Password reset emails broken or wrong domain links
- Homepage categories missing / wrong after seeder
- Bookings or professional flows broken
- Unexpected data loss (should not happen with additive migrations — investigate immediately)

---

## A. Immediate mitigation (fast)

1. Enable Hostinger maintenance page if available, or:
   ```bash
   php artisan down
   ```
2. Capture current error log:
   ```bash
   tail -n 200 storage/logs/laravel.log
   ```
3. Do **not** run destructive DB commands while diagnosing.

---

## B. Code rollback (Git)

Record the pre-deploy SHA during deploy. Then:

```bash
cd /path/to/yuktione
git status
git log -3 --oneline

# Fast-forward reverse to known good commit
git checkout main
git reset --hard <PRE_DEPLOY_SHA>

composer install --no-dev --optimize-autoloader --no-interaction

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
```

- [ ] Site responds on previous templates/assets
- [ ] Note: if migrations already ran, DB schema may still be “new” while code is “old” — usually **forward-compatible** for additive columns, but verify

---

## C. Database rollback (only if required)

### C.1 Prefer restore from backup

If data was corrupted or seeder behaviour is unacceptable:

```bash
# STOP writes / maintenance mode first
mysql -h DB_HOST -u DB_USER -p DB_NAME < ~/backups/yuktione-db-YYYYMMDD-HHMMSS.sql
```

Then restore code to matching SHA and clear caches.

### C.2 Targeted migration rollback (additive P2 bundle)

Only if you understand impact and backups exist:

```bash
php artisan migrate:status

# Roll back the latest batch that contained 2026_11_27_* (example)
php artisan migrate:rollback --step=1
# Repeat carefully until 2026_11_27_* are Pending, if that was the last batch
```

Per-migration implications:

| Migration | Rollback effect |
|-----------|-----------------|
| `2026_11_27_120300_seed_canonical_categories` | **No-op down** — seeded rows remain |
| `2026_11_27_120200_create_category_tool` | Drops `category_tool` (mappings lost) |
| `2026_11_27_120100_…_sub_services` | Drops slug/meta/display_order |
| `2026_11_27_120000_…_services` | Drops Category metadata columns |
| `2026_11_24_…professional_password_reset_tokens` | Drops professional token table; outstanding professional resets invalidated |

**Seeder note:** Because `120300` down is non-destructive, removing nine Category rows requires a **manual, reviewed** SQL/admin action — do not delete blindly if bookings/professionals reference `services.id`.

### C.3 Professional password table

If rolling back past `2026_11_24_120000` while old code shared `password_reset_tokens`, professional resets may collide with customers again. Prefer keeping that migration Ran once applied.

---

## D. Media / uploads rollback

```bash
# Restore storage backup if images were damaged
tar -xzf ~/backups/yuktione-storage-YYYYMMDD-HHMMSS.tar.gz -C /
php artisan storage:link
```

---

## E. Configuration rollback

1. Restore previous `.env` from Hostinger backup / secure notes (never from git).
2. `php artisan config:clear && php artisan config:cache`
3. Confirm `APP_URL`, mailer, `APP_DEBUG=false`.

---

## F. Verification after rollback

- [ ] Homepage 200
- [ ] Customer + professional login
- [ ] One category page + `/tools`
- [ ] One booking read path (existing booking still visible)
- [ ] `laravel.log` stable
- [ ] `php artisan up` if maintenance was on

---

## G. What this plan does **not** do

- Does not undo email already sent to users
- Does not recreate deleted reset tokens
- Does not automatically re-link payments
- Does not replace a missing DB backup — **take backups before migrate**

---

*Use only with explicit approval. Prefer fix-forward when additive schema is already live and code is the only problem.*
