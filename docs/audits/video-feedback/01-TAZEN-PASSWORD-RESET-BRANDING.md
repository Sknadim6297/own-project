# Video Issue 01 — Tazen Branding in Password Reset

**Date:** 2026-11-07  
**Project root:** `C:\xampp\htdocs\own-project`  
**Scope:** Customer + Professional Forgot/Reset Password flows and related browser branding only  
**Auth / reset business logic:** unchanged  
**Live legal pages / unrelated emails / production deploy:** not modified

---

## Root cause

1. **Mail From defaults** in `config/mail.php` still fell back to legacy **`support@tazen.in`** / **`Support Tazen`** whenever `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` were unset (or when an old config cache baked those values in). That is the source of the reported sender `support@tazen.in`.
2. **Password-reset notifications** used Laravel’s default markdown mail (customer) or a plain `MailMessage` (professional) with **no YuktiOne logo / brand copy**, so the mailbox experience could still feel like the old product when the From name was Tazen or `APP_NAME` was unset/legacy.
3. **Browser favicon:** password pages already linked `Yuktione_logo.png`, but **`public/favicon.ico` was empty (0 bytes)**. Browsers that auto-request `/favicon.ico` could show a blank or cached old icon. `public/frontend/assets/img/favicon.jpg` is an unrelated chess-rook asset (legacy), not used by the reset Blades.

Password-reset **Blade titles/logos were already largely YuktiOne**; the video’s email/sender complaint mapped primarily to mail config defaults + notification presentation.

---

## Old branding locations found

| Location | Finding |
|----------|---------|
| `config/mail.php` `from.address` default | `support@tazen.in` |
| `config/mail.php` `from.name` default | `Support Tazen` |
| Customer reset mail | Default `Illuminate\Auth\Notifications\ResetPassword` (no YuktiOne logo template) |
| Professional reset mail | `ProfessionalResetPassword` text-only markdown; no YuktiOne logo |
| `.env.example` `APP_NAME` | `Laravel` (not YuktiOne) |
| Local `.env` at start of fix | `APP_NAME=Laravel`; `MAIL_FROM_ADDRESS=hello@example.com` (not Tazen locally) |
| Reset Blades (customer/professional) | Titles/logo already YuktiOne; professional alt text still “Yuktione”; favicon link incomplete vs `/favicon.ico` |
| `public/favicon.ico` | Empty file |
| Other app emails (`booking-summary`, OTP, etc.) | Still contain Tazen logos/copy — **out of Issue 01 scope** (not password reset) |

---

## Exact corrections

| Change | Detail |
|--------|--------|
| `config/mail.php` | Default From **name** → `YuktiOne`; default From **address** → `hello@example.com` (Laravel-style placeholder — **not** a fabricated YuktiOne production mailbox). Removed Tazen defaults. |
| `.env.example` | `APP_NAME=YuktiOne`; comments that production must use an authorized YuktiOne-domain sender (not a legacy Tazen mailbox). |
| Local `.env` | `APP_NAME=YuktiOne` (display name via `MAIL_FROM_NAME="${APP_NAME}"`). **Did not invent** a new `MAIL_FROM_ADDRESS` — left `hello@example.com`. |
| `app/Notifications/CustomerResetPassword.php` | **Added** — YuktiOne subject, From display name, HTML view with approved logo; customer reset URL preserved. |
| `app/Models/User.php` | `sendPasswordResetNotification()` → `CustomerResetPassword`. |
| `app/Notifications/ProfessionalResetPassword.php` | Same YuktiOne HTML branding; professional reset route/token/expiry preserved. |
| `resources/views/emails/password-reset.blade.php` | **Added** — shared reset email body using `frontend/assets/Yuktione_logo.png`. |
| Customer/Professional forgot + reset Blades | Shortcut/apple favicon links to YuktiOne logo; professional alt/aria → **YuktiOne**. |
| `public/favicon.ico` / `public/favicon.png` | Replaced empty favicon with YuktiOne logo asset bytes so `/favicon.ico` is not blank. |

**Not changed:** reset token generation, brokers (`users` / `professionals`), validation, throttling, redirects, guard isolation, payment/legal pages, unrelated Tazen email templates.

---

## Files modified

- `config/mail.php`
- `.env.example`
- `.env` (`APP_NAME` only)
- `app/Models/User.php`
- `app/Notifications/CustomerResetPassword.php` *(new)*
- `app/Notifications/ProfessionalResetPassword.php`
- `resources/views/emails/password-reset.blade.php` *(new)*
- `resources/views/frontend/login/forget-password.blade.php`
- `resources/views/frontend/login/reset-password.blade.php`
- `resources/views/professional/login/forgot-password.blade.php`
- `resources/views/professional/login/reset-password.blade.php`
- `public/favicon.ico`
- `public/favicon.png` *(new)*
- `tests/Feature/AccountRecoveryTest.php`
- `tests/Feature/PasswordResetBrandingTest.php` *(new)*

---

## Tests and results

Command:

```bash
php artisan test --filter="AccountRecoveryTest|PasswordResetBrandingTest"
```

**Result: 12 passed (86 assertions)**

Covered:

1. Customer forgot password (recovery + branding page)  
2. Customer reset password page branding  
3. Professional forgot password  
4. Professional reset password page branding  
5. Customer reset email YuktiOne branding (no Tazen)  
6. Professional reset email YuktiOne branding + `/professional/reset-password` URL  
7. Mail config defaults no longer Tazen  
8. Existing recovery isolation / rate-limit / non-disclosure behavior unchanged  

No real reset emails were sent to external recipients (`Notification::fake()` / rendered locally).

---

## Remaining email sender / domain blockers

| Item | Status |
|------|--------|
| From **display name** default | **Fixed** → YuktiOne |
| Local From address | `hello@example.com` (dev placeholder) |
| Production / shared env still using `support@tazen.in` | **Operational blocker** if that value remains in the server `.env` or cached config — must be replaced with an **authorized YuktiOne-domain** sending address and verified SPF/DKIM/DMARC. **Not invented or silently substituted in code.** |
| After env change on a server | Run `php artisan config:clear` (or rebuild `config:cache` with safe values). |
| Unrelated transactional emails still saying Tazen | Still present; **outside Issue 01**. |

---

## Verdict

Issue 01 (password-reset Tazen branding) is **fixed in code** for Customer and Professional pages + reset emails.  

**Legal P0** and production mail-domain authorization remain separate. Stop after Issue 01.
