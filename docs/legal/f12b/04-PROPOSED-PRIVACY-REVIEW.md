# 04 — Proposed Privacy Policy — Review Preparation

**DRAFT — NOT APPROVED — NOT FOR PUBLICATION**

**Phase:** F12B  
**Base:** F12A `03-DRAFT-privacy-policy.md` (preserved)  
**Proposed controller/operator:** Suntree Consulting LLP — **proposed only** (**BUSINESS VERIFICATION REQUIRED**)  
**Brand:** YuktiOne  

Distinguish **implemented behavior** (code/config evidence) from **proposed policy** (aspirational until evidenced and approved).

---

## 1. Proposed controller statement (for counsel)

**DRAFT — NOT APPROVED:**

> Personal information on the YuktiOne Platform is proposed to be controlled/operated by **Suntree Consulting LLP**, subject to verification of legal identity, registered office, and authority to operate the brand.  
> Contact placeholders must not publish `help@Tazen.com` unless freshly verified.

---

## 2. Data map — implemented vs policy claim

| Category | Implemented evidence (F12A/F12B) | Safe policy claim today? | Gap / decision |
|----------|----------------------------------|--------------------------|----------------|
| Customer account profile (name, email, phone, password, address fields, image) | Registration + profile CRUD | Yes, if listed accurately | Confirm optional vs required fields |
| Email OTP | Registration verify flows | Yes | Retention of OTP codes |
| Professional profile + rates/availability | Pro registration + resources | Yes | Public vs private field split |
| Professional KYC / ID / qualification / GST docs | Upload fields in pro onboarding | Yes — **must add**; live Privacy under-describes | Access who/how long (**BUSINESS VERIFICATION REQUIRED**) |
| Booking records | Booking models/controllers | Yes | Retention schedule TBD |
| Payment metadata (order/payment/signature IDs, amounts) | Razorpay verify flows | Yes — metadata | **Do not claim** Platform stores full card numbers without evidence |
| Full card / UPI secrets | Processed by Razorpay | Say processor handles payment credentials | Confirm PCI posture with counsel |
| Chat / messages / attachments | Booking + admin chats; PII filter noted in prior audit | Yes | Staff access rules TBD |
| Reviews | Customer submit; admin moderation | Yes | Public display rules |
| Mood / journal / gratitude entries | Live tools | Yes — **must add**; live Privacy silent | Sensitivity, access, retention |
| Career/nutrition tool data | Coming soon / gated | **Do not claim as active** | Update when launched |
| Cookies / session (currency/country) | `DetectCurrency` / session | Yes — essential/session | Analytics/marketing **unverified** |
| Analytics / ad pixels / impression reporting | Live Tazen Privacy claims; YuktiOne stack not verified | **Do not claim** | **BUSINESS VERIFICATION REQUIRED** |
| Social SSO / Google Workspace invites | Not verified for YuktiOne | **Do not claim** | Remove from live Privacy at F12C |
| Affiliates / influencers / gig portfolios | Not verified | **Do not claim** | Remove |
| Biometric processing | Not verified | **Do not claim** | Remove unless evidenced |
| IP / device logs | Request IP on some auth; location for currency | Limited yes | Security logging retention TBD |
| Contact / careers / blog comments | Forms exist | Yes | Retention TBD |
| Encryption at rest | Not verified as a documented product guarantee | **Do not claim** “we encrypt all data at rest” without evidence | Counsel/security review |
| Automatic deletion / retention timers | No verified automated retention job for legal periods | **Do not claim** fixed auto-delete periods | Manual/process (**BUSINESS VERIFICATION REQUIRED**) |
| Self-serve account deletion workflow | Not verified as customer self-serve product | Policy may offer request-based deletion | Define process + SLA |
| Third-party safeguards (DPA/SCCs) | Contracts not in workspace | **Do not claim** specific contractual safeguards without evidence | Vendor list + counsel |
| Grievance officer | Not configured | Placeholder only | **BUSINESS VERIFICATION REQUIRED** |

---

## 3. Proposed Privacy sections for counsel (capability-honest)

### 3.1 Collection
Account, KYC (professionals), bookings, payment metadata, communications, reviews, tool entries, support forms, limited technical data.

### 3.2 Purposes
Operate Platform; verify Professionals; process bookings/payments; fraud/security; moderation; provide tools; support; legal compliance; marketing only if consent/lawful basis confirmed.

### 3.3 Sharing
- Booking counterparties (necessary fields)  
- Public professional listings / approved reviews  
- Processors: hosting, email, **Razorpay**, others **BUSINESS VERIFICATION REQUIRED**  
- Admin/support  
- Legal/safety  
- Corporate transaction  
- Affiliates: only if Gate A confirms group structure  

### 3.4 Sensitive / tool data
Mood and journal content may be sensitive. Default DRAFT position for counsel debate: not used for marketing; not shared with Professionals unless a future explicit feature + consent. Staff access: **BUSINESS VERIFICATION REQUIRED**.

### 3.5 Retention
Purpose-based only until schedules approved. **Do not invent** day counts.

### 3.6 Security
Describe measures only if accurate (e.g. HTTPS if deployed; access controls as implemented). Avoid absolute security promises.

### 3.7 Rights & grievance
Access/correction via profile where available; other rights via contact. Grievance channel: **BUSINESS VERIFICATION REQUIRED**.

### 3.8 Cookies
Essential/session verified at high level. Separate Cookie Policy: create later or remove dead link.

### 3.9 Children
Age gate: **BUSINESS VERIFICATION REQUIRED**.

### 3.10 International transfers
Hosting locations: **BUSINESS VERIFICATION REQUIRED**.

---

## 4. Live Privacy defects to retire at F12C (after approval)

- Tazen marketplace / Gig / Seller trader EU block  
- help@Tazen.com  
- Broken “sections 13 and 14” cross-reference  
- Unverified SSO, Workspace invites, biometrics, affiliate programs  
- Cookie Policy link without a YuktiOne Cookie Policy  
- Under-description of KYC and tool data  

---

## 5. Clause-level unresolved privacy issues

1. Legal name of data fiduciary/controller after Gate A.  
2. DPDP applicability and grievance officer requirement.  
3. Lawful bases mapping per processing activity.  
4. KYC retention and access control.  
5. Tool-data classification (sensitive personal data?).  
6. Vendor inventory + cross-border transfers.  
7. Marketing consent vs transactional email.  
8. Deletion workflow and backup residual data.  
9. Invoice/GST personal data on PDFs.  
10. Whether Professional can see Customer tool history (evidence: not a launched share feature — confirm).  

---

## 6. Publication prerequisites

Gates A, C, F, H (and related privacy items on checklist `06`).  
Until then: **Legal P0 OPEN**; no live Privacy implementation.
