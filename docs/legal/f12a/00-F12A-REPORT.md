# F12A — YuktiOne Legal Content Audit & Draft Preparation

**Status:** COMPLETE (read-only audit + separate drafts)  
**Scope:** Terms of Service + Privacy Policy preparation only  
**Live application files:** NOT CHANGED  
**F12B:** NOT STARTED  

**Disclaimer:** These materials are internal review drafts. They are **not** legally approved, not compliance-certified, and must not be published without business owner and legal reviewer approval.

---

## 1. Current legal document inventory

| # | Surface | Path / route | Brand / entity shown | What users actually accept |
|---|---------|--------------|----------------------|----------------------------|
| A | Public Terms page | `GET /terms` → `resources/views/frontend/sections/terms.blade.php` | **Tazen (Part Of Suntree Consulting LLP)**; Tazen.in marketplace language | Public legal page content (not the registration checkbox text) |
| B | Public Privacy page | `GET /privacy` → `resources/views/frontend/sections/privacy.blade.php` | **Tazen (Part of Suntree Consulting LLP)**; contacts `help@Tazen.com` | Public privacy content |
| C | Customer registration consent text | Modal on register → `@include('frontend.partials.terms_agreement')` | **Yuktione** User Agreement; placeholders for date, jurisdiction, email, phone, address | Checkbox “I agree to Yuktione's Terms and Conditions” binds UI to this partial |
| D | Customer login consent UI | `frontend/login/login.blade.php` includes customer **or** professional agreement by role | Mixed: customer partial or professional agreement | Scroll-to-agree + checkbox |
| E | Professional onboarding agreement | `@include('professional.terms_agreement')` on professional login | **YuktiOne** brand via `config('yuktione.name')`; contracting party = “operator of the platform” (no registered entity named) | “I Agree” after scroll |
| F | User PDF | `GET /user/terms-and-conditions` → `public/pdf/user-terms-and-conditions.pdf` | PDF metadata/title: **“Updated Customer Agreement Tazen”** (text largely non-extractable in this audit; treat as Tazen-era artifact) | Linked route exists; not primary registration modal content |
| G | Professional PDF | `GET /professional/terms-and-conditions` → `public/pdf/professional-terms-and-conditions.pdf` | Title strings include **“Consultant Online Agreement”** | Linked route exists; onboarding uses Blade agreement, not necessarily this PDF |

### Brand / contact evidence (verified in code/config)

| Item | Verified value | Source | Status |
|------|----------------|--------|--------|
| Product / brand name | YuktiOne (`Yuktione` also used in customer UI copy) | `config/yuktione.php`, page titles | Verified product brand |
| Public email | `info@yuktione.in` | `config/yuktione.php` | Verified config contact |
| Phone | `+91 96050 08040` | `config/yuktione.php` | Verified config contact |
| Hours | Mon–Fri 9am–6pm; Sat 9am–2pm | `config/yuktione.php` | Verified |
| Registered office / street address | `null` | `config/yuktione.php` | **BUSINESS CONFIRMATION REQUIRED** |
| Registered legal operator | Not in brand config; live Terms/Privacy name **Suntree Consulting LLP / Tazen** | Live blades | **BUSINESS CONFIRMATION REQUIRED** — do not assume YuktiOne is a registered entity |
| Grievance / DPDP officer | Not configured | — | **BUSINESS CONFIRMATION REQUIRED** |
| Jurisdiction | Placeholders `[Insert jurisdiction]` / `[Jurisdiction]` in consent agreements | Customer + professional partials | **BUSINESS CONFIRMATION REQUIRED** |
| Effective date | `[Insert Date]` in customer agreement; none on public Terms/Privacy | Partials / blades | **BUSINESS CONFIRMATION REQUIRED** |
| Refund / cancellation (product) | Customer partial: “generally non-refundable”; professional agreement §36 discretionary refunds; public Terms: Tazen Resolution Center / `help@tazen.in`; code: cancel appointment **not routed**; no Razorpay refund API found | Views + controllers | **BUSINESS CONFIRMATION REQUIRED** for publishable policy |

---

## 2. Verified platform capabilities (for legal relevance)

### Launched (code-backed)

- Customer accounts (register, OTP email verify, profile, dashboard)
- Professional accounts (register, OTP, admin approve/reject → accepted)
- Expert discovery / listing (accepted + active professionals)
- Rates, availability, consultation booking
- Razorpay payment, invoices/PDF
- Reschedule flows; booking chat + attachments
- Reviews (customer submit; admin moderation)
- Psychology tools live: Daily Mood Check-in, Digital Journal, Gratitude Journal
- Content/support: about, blog, contact, help/FAQ, careers apply
- IP-based currency detection (session country/currency)

### Coming soon / disabled / not launched

- Programmes / events: `programmes_enabled = false`
- Community / Shop: nav placeholders only
- Career & nutrition tools: registry `coming_soon` / not launchable via `canLaunch()`
- Customer-facing cancellation route: controller method exists, **not wired**
- Automated refunds: **not implemented** (status labels only in places)

**Do not invent** marketplace gig delivery, Resolution Center, seller levels, affiliate/influencer programs, social SSO, or Community Forum as live YuktiOne features unless later verified.

---

## 3. Clause-by-clause gap matrix

Full matrix: [`01-CLAUSE-GAP-MATRIX.md`](./01-CLAUSE-GAP-MATRIX.md)

**Headline gaps**

1. Public `/terms` and `/privacy` still describe a **Tazen digital-services marketplace** under **Suntree Consulting LLP**, while product UX and registration consent describe **YuktiOne / Yuktione consultation booking**.
2. Users accept **different documents** depending on surface (public page vs registration modal vs professional agreement vs PDF).
3. Marketplace Buyer/Seller, Gig, Order, Resolution Center, leveling, and affiliate clauses are **misaligned** with consultation booking.
4. Privacy still claims data categories and integrations (SSO, gigs, biometrics, Cookie Policy link, Google Workspace invites) that are **not verified** for YuktiOne.
5. Sensitive tool data (mood/journals) and professional KYC docs are **under-described** relative to implemented collection.
6. Identity, office, grievance contact, jurisdiction, effective date, and refund rules remain **unverified**.

---

## 4. Draft documents (separate; not live)

| Draft | File |
|-------|------|
| A. Terms of Service — REVIEW DRAFT | [`02-DRAFT-terms-of-service.md`](./02-DRAFT-terms-of-service.md) |
| B. Privacy Policy — REVIEW DRAFT | [`03-DRAFT-privacy-policy.md`](./03-DRAFT-privacy-policy.md) |

All proposed wording is marked **DRAFT**. Unverified identity/process items are flagged **BUSINESS CONFIRMATION REQUIRED**.

---

## 5. Business confirmation checklist

See [`04-APPROVAL-CHECKLIST.md`](./04-APPROVAL-CHECKLIST.md).

---

## 6. Unresolved legal risks (pre-publication)

| Risk | Severity | Notes |
|------|----------|-------|
| Brand/entity mismatch (YuktiOne UX vs Tazen/Suntree live legal pages) | **P0** | Misleading operator identity to public visitors |
| Multiple inconsistent agreements accepted at different moments | **P0** | Unclear which contract controls |
| Marketplace/gig terms do not match consultation product | **P0** | Unenforceable / unfair surprise risk |
| Refund/cancellation stated in text but product refunds not implemented | **P0** | Consumer-facing promise vs capability gap |
| Customer agreement “generally non-refundable” vs discretionary refund clauses elsewhere | High | Internal conflict |
| Professional non-compete / non-solicit placeholders | High | Enforceability + clarity; legal review needed |
| Sensitive mental-health-adjacent tool data without dedicated privacy treatment | High | Heightened expectation under privacy norms |
| Professional ID / qualification / GST document collection understated in public Privacy | High | Transparency gap |
| Contacts `help@Tazen.com` / `help@tazen.in` still live on public pages | High | Wrong support channel |
| No published registered office / grievance officer | High | Especially if India DPDP / consumer rules apply — confirm with counsel |
| PDF “Updated Customer Agreement Tazen” still served | Medium | Parallel outdated artifact |
| Cookie Policy referenced but not verified as a live YuktiOne page | Medium | Broken/misleading cross-reference |
| Drafts are **not** legal advice / approval | Process | Must pass counsel + owner before F12B |

---

## 7. Files reviewed (read-only)

- `resources/views/frontend/sections/terms.blade.php`
- `resources/views/frontend/sections/privacy.blade.php`
- `resources/views/frontend/partials/terms_agreement.blade.php`
- `resources/views/professional/terms_agreement.blade.php`
- `resources/views/frontend/login/register.blade.php`
- `resources/views/frontend/login/login.blade.php`
- `resources/views/professional/login/login.blade.php`
- `config/yuktione.php`
- `routes/web.php` (terms/privacy/PDF routes)
- `public/pdf/user-terms-and-conditions.pdf` (metadata/title inspection)
- `public/pdf/professional-terms-and-conditions.pdf` (metadata/title inspection)
- Feature evidence via routes/controllers/models/config (booking, Razorpay, tools, programmes flag)

---

## 8. Confirmation — live application not changed

**Confirmed:** F12A did **not** modify live Terms/Privacy Blade views, registration consent logic, authentication, booking/payment flows, Customer/Professional panels, programmes enablement, database migrations, or production deployment.

**Created only (review package):**

- `docs/legal/f12a/00-F12A-REPORT.md`
- `docs/legal/f12a/01-CLAUSE-GAP-MATRIX.md`
- `docs/legal/f12a/02-DRAFT-terms-of-service.md`
- `docs/legal/f12a/03-DRAFT-privacy-policy.md`
- `docs/legal/f12a/04-APPROVAL-CHECKLIST.md`

**Stop.** Do not proceed to F12B unless explicitly requested.
