# Video Issue 06 — Mobile Number & Multiple Email Account Recovery Audit

**Date:** 2026-11-07  
**Project root:** `C:\xampp\htdocs\own-project`  
**Laravel:** 10.49.1  
**Mode:** **READ-ONLY** (no code, schema, SMS, or behaviour changes)  
**Stakeholder line:** *“Mobile multiple email to mobile issue — discuss before implementing.”*

---

## Executive summary

YuktiOne **login is email + password** for both Customer (`user` guard) and Professional (`professional` guard).  
**Forgot Password is mobile-first:** the user enters a phone number; the system looks up an account and emails a Laravel password-reset link to that account’s **registered email**.

**Email is unique within each table** (`users`, `professionals`).  
**Mobile is not unique** in schema or registration validation for either role.  
If several completed Customer accounts share the same national 10-digit mobile, recovery uses **`Collection::first()`** — effectively an arbitrary (typically oldest-ID) match — and sends the reset to **that** email only. There is **no account-selection UI** and **no SMS ownership proof** of the phone.

**Recommended posture until business approves otherwise:** treat Option A (email-identified recovery, or keep mobile lookup only after enforcing verified unique phones) as the safe baseline; do **not** ship Option B/C without product + security approval.

---

## 1. Existing authentication behaviour

### Customer

| Step | Behaviour |
|------|-----------|
| Registration | Email + phone + password; **email OTP** via `OtpService` (email channel, not SMS) |
| Login | `email` + `password` on guard `user` (`LoginController`) |
| Incomplete registration | May exist with phone/email before password; recovery **excludes** `registration_completed = false` |
| Forgot password | Form asks for **mobile only** → `AccountRecovery::registeredCustomer()` → `Password::broker('users')->sendResetLink(['email' => …])` |
| Reset password | Token + **email** + new password on `/reset-password` |

### Professional

| Step | Behaviour |
|------|-----------|
| Registration | Email OTP in session, then create `professionals` row with phone |
| Login | `email` + `password` on guard `professional` |
| Forgot password | Separate `/professional/forgot-password`; mobile → `registeredProfessional()` → broker `professionals` |
| Reset | `/professional/reset-password`; branded `ProfessionalResetPassword` notification |

### Cross-guard

Professional recovery **does not** fall back to Customer accounts (covered by `AccountRecoveryTest`). Customer and Professional flows stay on separate routes/guards.

### Duplicate customer route registration (technical note)

`routes/web.php` registers customer forgot/reset **twice**: first `Frontend\AuthController`, then `Auth\ForgotPasswordController` (guest group). `route:list` names resolve to `ForgotPasswordController`, but **dispatch order prefers the first matching URI**. Logic for *send* is largely the same (`AccountRecovery`); **reset validation differs** (see bugs): legacy controller allows **password min:4** and uses default `Password::reset()`, while `ForgotPasswordController` requires **min:8** and `broker('users')`.

---

## 2. Mobile / email uniqueness rules

| Rule | Customer (`users`) | Professional (`professionals`) |
|------|--------------------|--------------------------------|
| Email unique (DB) | **Yes** (`email` unique) | **Yes** (`email` unique) |
| Phone unique (DB) | **No** (nullable string, no unique index) | **No** (nullable string, no unique index) |
| Phone unique (app validation on register) | **No** — only required length/format checks | **No** — required/nullable format only; email uniqueness checked |
| Phone normalized at write | **No** — stored as submitted string | **No** |
| Phone normalized at recovery lookup | **Yes** — strip non-digits, use **last 10 digits** (`AccountRecovery::nationalNumber`) | Same |
| Phone ownership verified (SMS) | **No** | **No** |
| Email ownership verified | **Yes** at registration (email OTP) | **Yes** at registration (email OTP) |

Same person can theoretically hold Customer + Professional accounts with the **same** mobile (separate tables). Same mobile can also appear on **multiple Customer emails** if registration never blocked it.

---

## 3. Confirmed findings / bugs

### C1 — Multiple Customer emails sharing one mobile → ambiguous recovery (**core stakeholder issue**)

`AccountRecovery::registeredCustomer()`:

1. Loads candidates with `phone LIKE '%{last4}'`, then filters to matching national 10 digits.  
2. Returns **`->first()`** among those with completed registration.

**No disambiguation**, no multi-match error, no “choose account” step.  
Reset email goes only to the **first** completed match. Other accounts on that mobile get **nothing** and receive no signal that another account was selected.

There is **no automated test** for multi-account same-phone.

### C2 — Mobile not unique at registration

Nothing prevents two completed Customers (or two Professionals) from storing the same national number in different formats (`9876543210`, `+91 98765 43210`, etc.). Recovery normalizes them to the same key → C1.

### C3 — Phone is not proven owned

Recovery assumes “knowing the number” ≈ account control. Numbers are **not SMS-verified**. A party who knows (or guesses) a registered mobile can trigger a reset email to whatever account `first()` selects — classic **reset spam / wrong-mailbox** risk when phones are shared or recycled.

### C4 — Account enumeration via mobile

Unknown mobile → `recovery_unregistered` + “Mobile Number Not Registered” + Register CTA.  
Known mobile → generic “sent to the email on this account” (email **not** shown — good).  
This is **intentional UX** (tests assert against Laravel’s opaque copy) but **confirms registration** of a mobile to attackers.

### C5 — Shared `password_reset_tokens` table for both brokers

`config/auth.php`: both `users` and `professionals` password brokers use table `password_reset_tokens` keyed by **email**.  
If the same email string exists on both a Customer and a Professional (allowed across tables), token rows can **collide / overwrite**.

### C6 — Duplicate customer reset controllers / weaker min password on one path

`Frontend\AuthController::resetPassword` validates `min:4`; `ForgotPasswordController` uses `min:8`. Depending on which route entry is matched first, customer reset strength may be weaker than the guest-group controller implies. Worth cleaning up when implementing (out of scope for this audit).

### C7 — `RESET_THROTTLED` treated as success

`sendCustomerReset` / `sendProfessionalReset` return true for `Password::RESET_THROTTLED` as well as `RESET_LINK_SENT`. User sees the success banner even when Laravel throttled a new link. Reduces enumeration but can mislead about a fresh email.

---

## 4. Security risks (summary)

| Risk | Severity | Notes |
|------|----------|-------|
| Reset sent to wrong email when phone shared | **High** (product + security) | C1 |
| Unverified phone as recovery factor | **High** | C3 |
| Mobile registration oracle | **Medium** | C4 — accepted today for UX |
| Cross-role token table collision | **Medium** | C5 — if same email on both roles |
| Customer/Professional confusion | **Low** (mitigated) | Separate routes; test for no cross-send |
| Rate limit abuse | **Low–Medium** | `throttle:5,1,recovery` + `throttle:20,60,recovery-hour` on send endpoints |
| Token expiry / one-time use | **OK baseline** | Expire 60 minutes; Laravel broker invalidate-on-use |
| Email enumeration on reset form | **Low** | Reset still requires email+token; failed status via validation errors |

**Not found:** SMS OTP recovery, mobile login, or phone unique indexes.

---

## 5. Multiple-account scenarios

| Scenario | Current behaviour |
|----------|-------------------|
| One email, one mobile (happy path) | Mobile → that email reset link |
| Two Customer emails, same mobile | Reset to **first** completed match only |
| Customer + Professional, same mobile | Customer forgot affects Customer only; Professional forgot affects Professional only |
| Same email on Customer and Professional | Possible across tables; shared token table risk (C5) |
| Incomplete Customer registration, same mobile as completed | Incomplete ignored; completed can recover |
| Unknown mobile | Unregistered alert; no email sent |
| Login with mobile | **Not supported** — email required |

---

## 6. Option comparison (no selection implemented)

### Option A — Email-based recovery only

**Idea:** Forgot Password collects **email** (optionally still show mobile as non-authoritative help text). Send reset only to that mailbox.

| | |
|--|--|
| Benefits | Matches unique email identity; eliminates multi-email/mobile ambiguity; aligns with login; no SMS vendor |
| Risks | Users who only remember phone need support process; enumeration if responses differ (use generic copy) |
| Fit today | High — brokers already email-keyed; Issue 01 branding already on reset mail |
| Requirements | UI + validation change; keep throttles; optional deprecate mobile lookup |

### Option B — Mobile lookup with account selection

**Idea:** If multiple accounts match a verified phone, show masked emails / last-used and let user pick, then send.

| | |
|--|--|
| Benefits | Keeps mobile-first UX stakeholders may want |
| Risks | Without **verified** phone ownership, selection UI **leaks** which emails are tied to a number; picker still abuseable |
| Fit today | Poor until phone unique + verified |
| Requirements | Enforce unique verified phone; secure masking; audit logging; tests for multi-match |

### Option C — Mobile OTP recovery

**Idea:** SMS OTP proves phone ownership, then reset or set password.

| | |
|--|--|
| Benefits | Strong recovery if phone is the primary identifier |
| Risks | Cost, delivery failures, SIM-swap, regulatory/consent, new attack surface |
| Fit today | Not present; email OTP only |
| Requirements | SMS provider, verified unique phones, rate limits, fraud controls, **explicit business approval** |

---

## 7. Recommended safe approach (discussion — not implemented)

1. **Short term (safest, lowest change):** Prefer **Option A** (email-based forgot password), **or** keep mobile lookup **only if** product immediately commits to blocking duplicate phones for completed accounts and treating multi-match as a hard error (“contact support”) instead of `first()`.  
2. **Do not** ship Option B UI until phones are unique + ownership-verified.  
3. **Do not** ship Option C without approved SMS vendor, budget, and security review.  
4. When implementing later: split password-reset token storage per guard (or namespace keys); collapse duplicate customer Auth controllers; keep Customer/Professional separation; preserve Issue 01 YuktiOne branding.

**Business decisions required before any code change:**

1. Is **email** or **mobile** the canonical account recovery identifier?  
2. May multiple Customer accounts share one mobile? If no → unique constraint + registration rules.  
3. May Customer and Professional share one mobile?  
4. Is mobile registration disclosure (`recovery_unregistered`) acceptable long-term?  
5. Approve / reject SMS OTP (Option C) explicitly.

---

## 8. Files inspected

- `app/Services/AccountRecovery.php`
- `app/Http/Controllers/Auth/ForgotPasswordController.php`
- `app/Http/Controllers/Frontend/AuthController.php`
- `app/Http/Controllers/Frontend/LoginController.php` (register/login/OTP)
- `app/Http/Controllers/Professional/AuthController.php`
- `app/Http/Controllers/Professional/ProfessionalController.php` (register/login)
- `app/Models/User.php`, `app/Models/Professional.php`
- `app/Notifications/CustomerResetPassword.php`, `ProfessionalResetPassword.php`
- `app/Services/OtpService.php`
- `config/auth.php`
- `routes/web.php` (forgot/reset groups)
- `resources/views/frontend/login/forget-password.blade.php` (+ professional forgot blade via tests)
- Migrations: `2014_10_12_000000_create_users_table.php`, `2025_10_06_000001_add_phone_and_verification_to_users_table.php`, `2025_04_24_053825_create_professionals_table.php`, `2025_05_06_112836_add_phone_to_professionals_table.php`, password reset token migrations
- Tests: `tests/Feature/AccountRecoveryTest.php`, `PasswordResetBrandingTest.php`, `AuthenticationFeedbackTest.php`

---

## 9. Tests executed and results

```bash
php artisan test --filter="AccountRecoveryTest|PasswordResetBrandingTest|AuthenticationFeedbackTest"
```

**18 passed** (117 assertions).  

Coverage includes: unknown mobile alert, incomplete registration blocked, registered customer/professional send without exposing email, no customer→professional cross-send, recovery throttles, Issue 01 branding.  

**Gap:** no test for **multiple completed accounts on one mobile**.

Synthetic fixtures only inside PHPUnit; **no real SMS**; Notification faked (no live reset mail).

---

## 10. Implementation readiness

| Item | Status |
|------|--------|
| Problem understood | **Yes** — mobile lookup + non-unique phone + `first()` selection |
| Safe to implement without business decision | **No** |
| Blocking decisions | Canonical identifier; phone uniqueness; Option A/B/C |
| Code changes in this issue | **None** (READ-ONLY) |
| Issue 01 branding | Untouched |
| OTP/SMS work | Not started; not recommended without approval |

---

## Verdict

**Issue 06 complete (audit only).**  
Current recovery is **mobile → email link**, with **email unique** and **phone not unique**. Multiple emails on one mobile are a real ambiguity that can send resets to the wrong mailbox. Discuss Options A–C with stakeholders before any implementation.
