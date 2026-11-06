# 03 — Legal Document Consistency

**DRAFT — NOT APPROVED — NOT FOR PUBLICATION**  
**Live files inspected:** Terms, Privacy, Customer agreement, Professional agreement, PDF routes, `config/yuktione.php`  
**Surfaces:** see also `02-LEGAL-SURFACE-MAP.md`

---

## 1. Document corpus (as live today)

| ID | Document | Path / route | Brand / operator today |
|----|----------|--------------|------------------------|
| S1 | Public Terms | `/terms` → `frontend/sections/terms.blade.php` | Chrome YuktiOne; body **Tazen / Suntree** marketplace |
| S2 | Public Privacy | `/privacy` → `frontend/sections/privacy.blade.php` | Chrome YuktiOne; body **Tazen / Suntree**; contacts `help@Tazen.com` |
| S3 | Customer Agreement | `frontend/partials/terms_agreement.blade.php` | **Yuktione** as “the company”; placeholders |
| S4 | Professional Agreement | `professional/terms_agreement.blade.php` | **YuktiOne** brand; “operator of the platform” unnamed |
| S5 | Customer PDF | `/user/terms-and-conditions` → `public/pdf/user-terms-and-conditions.pdf` | Tazen-era artifact (title historically “Updated Customer Agreement Tazen”) |
| S6 | Professional PDF | `/professional/terms-and-conditions` → `public/pdf/professional-terms-and-conditions.pdf` | Consultant Online Agreement PDF |

---

## 2. Inconsistency register

### 2.1 Identity & branding

| ID | Topic | YuktiOne | Tazen | Suntree Consulting LLP | Impact |
|----|-------|----------|-------|------------------------|--------|
| I1 | Public Terms heading/body | Page title/chrome | Marketplace Site, Tazen.in, Buyers/Sellers/Gigs | Named as parent of Tazen in live copy | Misleading operator + product |
| I2 | Public Privacy | Page title/chrome | Same Tazen marketplace framing | Named in live copy | Wrong privacy controller story |
| I3 | Customer Agreement | Spelled **Yuktione**; treats brand as “the company” | Absent | Absent | Unverified entity; spelling drift |
| I4 | Professional Agreement | YuktiOne via config | Absent | Absent | Operator unnamed |
| I5 | Customer PDF | Route under YuktiOne app | Tazen-titled PDF artifact | May appear in PDF metadata/history | Parallel outdated corpus |
| I6 | Proposed F12B operator | Brand only | Must not remain as marketplace framing | **PROPOSED** operator pending Gate A | Cannot publish “operated by Suntree” until verified |

### 2.2 Marketplace vs consultation operator description

| ID | Live claim (S1/S2) | YuktiOne product reality | Action before publish |
|----|--------------------|--------------------------|-----------------------|
| M1 | Digital services marketplace; Buyers/Sellers; Orders; seller levels | Expert consultation booking + tools | Replace with consultation platform Terms |
| M2 | Resolution Center / leveling | Not verified as YuktiOne features | Remove / replace with support-mediated disputes |
| M3 | Community Forum / affiliate / influencer programs | Community/Shop Soon; programmes disabled | Do not claim as live |
| M4 | Customer Agreement lists consultations (astrology, health, etc.) | Closer to product, but incomplete vs tools/KYC | Align to approved service list |

### 2.3 Refund & cancellation

| ID | Source | Statement | Conflict |
|----|--------|-----------|----------|
| R1 | S3 Customer §5 | Payments “generally non-refundable” except CS policy if service not provided | Conflicts with R2/R3 |
| R2 | S4 Professional §36 | Company may issue discretionary full/partial refunds; Consultant may reimburse | Conflicts with R1 |
| R3 | S1 Public Terms | Disputes/cancellations via Tazen Resolution Center / `help@tazen.in` style marketplace process | Wrong product + channel |
| R4 | Product capability (F12A/B prior) | Automated Razorpay refunds not verified; customer cancel route historically unwired | Cannot promise auto-refund / self-serve cancel without product work |

**Gate G** must produce one policy referenced by S1–S4 and PDFs.

### 2.4 Privacy & data processing

| ID | Issue | Live S2 | YuktiOne need |
|----|-------|---------|---------------|
| P1 | Contact | `help@Tazen.com` | Approved privacy/grievance contact |
| P2 | Over-claim | Gigs, social SSO, biometrics/audio sensory, affiliates/influencers | Limit to evidenced processing |
| P3 | Under-claim | Little/no clear Professional KYC / qualification / GST docs disclosure | Disclose KYC purposes/retention |
| P4 | Under-claim | Mood/journal/gratitude tool entries | Disclose sensitive self-entry data |
| P5 | Cookie Policy link | Referenced | No verified YuktiOne Cookie Policy page |
| P6 | Customer S3 Privacy pointer | Points to “Privacy Policy” conceptually | Currently resolves to Tazen S2 — broken alignment |

### 2.5 Customer vs Professional responsibilities

| ID | Theme | Customer (S3) | Professional (S4) | Consistency need |
|----|-------|---------------|-------------------|------------------|
| C1 | Contracting party | Yuktione “company” | Unnamed operator | Single verified operator block |
| C2 | Off-platform contact | Customer must not contact Pros externally | Non-solicit / off-platform rules | Harmonize with Public Terms |
| C3 | Refunds | Non-refundable default | Discretionary refunds + clawback | Gate G |
| C4 | Placeholders | Date, jurisdiction, email, phone, address | Date, jurisdiction, non-compete `[6/12 months]` | Fill only after approval |
| C5 | KYC / licensing | Light | Duties, docs, quality, indemnity | Privacy + Professional Agreement |
| C6 | PDFs vs Blades | PDF may diverge from S3 | PDF may diverge from S4 | Reissue from same approved source in F12C |

---

## 3. Review-ready draft alignment targets

Post-approval masters must share:

1. **YuktiOne** branding (approved spelling).  
2. **Proposed operator** Suntree Consulting LLP **only after Gate A** — until then use explicit placeholders: `[PROPOSED OPERATOR — APPROVAL REQUIRED]`.  
3. Consultation booking + tools service description (not Tazen gig marketplace).  
4. Booking/payment responsibilities consistent with Razorpay reality.  
5. Single cancellation/refund policy (Gate G).  
6. Customer data + Professional KYC disclosures (Gate F).  
7. Grievance / privacy contact process (Gate A/F).  

Draft shells: `docs/legal/f12b/drafts/`.  
Base Terms/Privacy prose: F12A `02-DRAFT-terms-of-service.md`, `03-DRAFT-privacy-policy.md` (preserved).

---

## 4. Consistency verdict

| Question | Answer |
|----------|--------|
| Are live legal surfaces consistent? | **No** |
| Is proposed operator publishable today? | **No** |
| Are review drafts available for alignment work? | **Yes** (F12A + F12B drafts/review packs) |
| May live Blades/PDFs be updated now? | **No** — F12C only after Gates A–H |
