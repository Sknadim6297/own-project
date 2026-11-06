# 02 — Legal Surface Map & Synchronization Architecture

**DRAFT — NOT APPROVED — NOT FOR PUBLICATION**

**Phase:** F12B preparation only  
**Acceptance mechanisms:** Documented only — **do not change** in F12B/F12C without separate approval.

---

## 1. Active legal surfaces (inventory)

| ID | Surface | Route / trigger | Source file(s) | Brand / entity today | Role-specific? | Sync target after approval |
|----|---------|-----------------|----------------|----------------------|----------------|----------------------------|
| S1 | Public Terms | `GET /terms` | `resources/views/frontend/sections/terms.blade.php` | Chrome: YuktiOne; body: Tazen / Suntree marketplace | No (general) | Approved Public Terms |
| S2 | Public Privacy | `GET /privacy` | `resources/views/frontend/sections/privacy.blade.php` | Chrome: YuktiOne; body: Tazen / Suntree | No (general) | Approved Public Privacy |
| S3 | Customer registration agreement | Register modal + `terms_accepted` | `resources/views/frontend/partials/terms_agreement.blade.php` via `frontend/login/register.blade.php` | Yuktione User Agreement; placeholders | **Customer** | Approved Customer Agreement (role-specific) aligned to Public Terms |
| S4 | Professional registration / login agreement | Professional login “I Agree”; also role branch on shared login | `resources/views/professional/terms_agreement.blade.php` via `professional/login/login.blade.php` (+ customer login role switch) | YuktiOne brand; “operator of the platform” unnamed | **Professional** | Approved Professional / Consultant Agreement (role-specific) |
| S5 | Customer Terms PDF | `GET /user/terms-and-conditions` → `user.terms` | `public/pdf/user-terms-and-conditions.pdf` | PDF title artifact: “Updated Customer Agreement Tazen” | Customer-oriented | Reissue/retire; must match approved Customer Agreement |
| S6 | Professional Terms PDF | `GET /professional/terms-and-conditions` → `professional.terms` | `public/pdf/professional-terms-and-conditions.pdf` | “Consultant Online Agreement” title strings | Professional-oriented | Reissue/retire; must match approved Professional Agreement |
| S7 | Booking / payment consent references | Booking + Razorpay flows | Booking/summary blades; payment controllers | No dedicated Terms re-acceptance found in tool scan; payment proceeds under account/session | Indirect | Cross-link to Public Terms + refund policy; **no new checkbox unless approved** |
| S8 | Tools privacy / consent | Mood / journal / gratitude tool UIs | Tool section blades under `frontend/sections/` | **No in-tool Privacy/Terms consent copy found** in F12B scan | Customer tools | Optional short sensitive-data notice + Privacy link — **design decision**; not implemented now |
| S9 | Footer legal links | Site footer | `resources/views/frontend/sections/footer.blade.php` | Links to `/terms` and `/privacy` | N/A | Keep URLs; content behind them updates post-approval |
| S10 | Customer login terms UI | Shared login | `frontend/login/login.blade.php` includes S3 or S4 by role | Mixed | Role-dependent | Same partials as S3/S4 |

---

## 2. What users actually accept today (mechanism unchanged)

| Moment | Mechanism | Document shown | Notes |
|--------|-----------|----------------|-------|
| Customer register | Checkbox `terms_accepted` + modal | S3 Customer partial | Does **not** show public `/terms` Tazen text |
| Customer/professional login (where enforced) | Scroll + “I Agree” / checkbox | S3 or S4 by role | Role switch on shared login |
| Professional login onboarding | Scroll + “I Agree” | S4 | Clickwrap |
| Public browse of `/terms` `/privacy` | Passive read | S1 / S2 | Not the registration modal text |
| PDF routes | File download | S5 / S6 | Parallel artifacts; not primary modal content |
| Booking pay | Razorpay checkout | No separate legal re-consent verified | Relies on prior account acceptance — **counsel to confirm sufficiency** |
| Tools | Create mood/journal entries | No separate privacy consent verified | Relies on account Terms/Privacy — **gap for sensitive data notice** |

**F12B/F12C rule:** Do not change these acceptance mechanisms unless a separate approved design requires fresh acceptance (see §5).

---

## 3. Duplication & inconsistency map

| Issue | Surfaces | Risk |
|-------|----------|------|
| Three different “operator” presentations | S1/S2 (Tazen/Suntree), S3 (Yuktione “the company”), S4 (“operator of YuktiOne”) | Contractual ambiguity |
| Marketplace gig Terms vs consultation product | S1 vs actual product / S3 | Unfair surprise / unenforceability |
| Privacy contacts `help@Tazen.com` vs config `info@yuktione.in` | S2 vs config / S4 notices | Wrong channel |
| Customer “generally non-refundable” vs Professional discretionary refunds vs Tazen Resolution Center | S3 / S4 / S1 | Internal conflict |
| PDF vs Blade divergence | S5/S6 vs S3/S4 | Users may download outdated PDF |
| Footer links to S1/S2 while register opens S3 | S9 vs S3 | User thinks they accepted public Terms; they accepted partial |
| Privacy Policy named in S3 without matching YuktiOne Privacy | S3 → S2 | Broken conceptual link |
| No Cookie Policy page verified | S2 references Cookie Policy | Dead/misleading cross-reference |
| Tool sensitive data undescribed | S8 missing | Transparency gap |

---

## 4. Proposed synchronization architecture (post-approval only)

```
                    ┌─────────────────────────────────────┐
                    │  Approved master versions (versioned)│
                    │  - PUBLIC-TERMS-vX                   │
                    │  - PUBLIC-PRIVACY-vX                 │
                    │  - CUSTOMER-AGREEMENT-vX             │
                    │  - PROFESSIONAL-AGREEMENT-vX         │
                    └──────────────┬──────────────────────┘
                                   │
          ┌────────────────────────┼────────────────────────┐
          ▼                        ▼                        ▼
     S1 /terms                 S2 /privacy            Footer S9
          │                        │
          │    ┌───────────────────┴────────────────────┐
          ▼    ▼                                        ▼
   S3 Customer Agreement          S4 Professional Agreement
   (role-specific obligations     (role-specific obligations
    retained; must not contradict  retained; fees, KYC, non-
    Public Terms core)             solicit, etc.)
          │                                        │
          ▼                                        ▼
   S5 Customer PDF (generated                 S6 Professional PDF
      from same approved source)                 (same source)
          │
          ▼
   S7 Booking UX: link to Public Terms + refund section
   S8 Tools UX: optional short notice + Privacy link (if approved)
```

### Sync rules

1. **Single operator identity block** (once verified): brand YuktiOne; proposed operator Suntree Consulting LLP only after Gate A.  
2. **Public Terms** = general platform rules for all users.  
3. **Customer Agreement** retains customer-only duties (conduct, payments as customer, off-platform rules as customer) and **must not contradict** Public Terms on refunds/identity.  
4. **Professional Agreement** retains professional-only duties (KYC, commission, quality, non-solicit, client confidentiality) and **must not contradict** Public Terms/Privacy.  
5. **PDFs** are exports/snapshots of the same approved Blade/source version — not independent legal documents.  
6. **Version stamp** on every surface: `Document ID + Effective Date + Version`.  
7. **Links:** footer, register modal title, and `/terms` `/privacy` must resolve to the same approved public corpus.

---

## 5. Fresh acceptance (separate design decision)

**Do not** retroactively treat historical `terms_accepted` / “I Agree” clicks as acceptance of revised YuktiOne/Suntree documents.

If counsel requires re-consent:

| Option | Description | Needs separate approval |
|--------|-------------|-------------------------|
| A | Soft notice + continued use | Counsel + product |
| B | Blocking re-accept modal on next login | Counsel + product + eng |
| C | Email notice + in-app accept before booking/tools | Counsel + product + eng |

Document outcome in F12C kickoff; **out of scope for silent F12C content swap**.

---

## 6. Out of F12B scope

- No Blade/PDF/content edits  
- No consent record schema changes  
- No acceptance UX changes  
- Programmes remain disabled  
