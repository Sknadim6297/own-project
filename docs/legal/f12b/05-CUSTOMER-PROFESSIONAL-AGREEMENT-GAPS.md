# 05 — Customer & Professional Agreement Gaps

**DRAFT — NOT APPROVED — NOT FOR PUBLICATION**

**Phase:** F12B  
**Sources preserved:**  
- `resources/views/frontend/partials/terms_agreement.blade.php` (Customer)  
- `resources/views/professional/terms_agreement.blade.php` (Professional)  
- F12A matrix `01-CLAUSE-GAP-MATRIX.md` sections C & D  

Role-specific obligations must be **retained** after alignment — not flattened into a single generic Terms page.

---

## 1. Shared gaps (both agreements)

| Gap | Customer | Professional | Required decision |
|-----|----------|--------------|-------------------|
| Operator legal name | “Yuktione” as “the company” | “operator of YuktiOne” unnamed | Insert verified operator **after Gate A** (proposed: Suntree Consulting LLP) |
| Brand spelling | Yuktione | YuktiOne via config | Normalize approved spelling |
| Effective date | `[Insert Date]` | `[Date]` in preamble | **BUSINESS VERIFICATION REQUIRED** |
| Jurisdiction / venue | `[Insert jurisdiction]` / arbitration location | `[Jurisdiction]` | **BUSINESS VERIFICATION REQUIRED** |
| Registered office | `[Insert physical address]` | Not stated | **BUSINESS VERIFICATION REQUIRED** |
| Contact email/phone | Placeholders | Notices → `info@yuktione.in` | Confirm legal vs support contacts |
| Alignment with Public Terms/Privacy | Points to Privacy; differs from `/terms` | Parallel corpus | Sync architecture in `02` |
| Refund policy conflict | “Generally non-refundable” | §36 discretionary refunds | **Gate G** single policy |
| Tazen PDF siblings | PDF titled Tazen customer agreement | Separate consultant PDF | Reissue/retire |

---

## 2. Customer Agreement — retain / modify / counsel

| Clause theme | Action | Notes |
|--------------|--------|-------|
| Definitions of Services/Products | Modify | Tie to services listed on Platform; mark programmes/events as not currently offered |
| Registration & account security | Retain | Still accurate |
| Legal age / guardian | Retain + confirm age | **BUSINESS VERIFICATION REQUIRED** |
| User conduct / no external pro contact | Retain + Legal Review | Enforceability/exceptions |
| Payment terms | Modify after Gate G | Must match real refund capability |
| Confidentiality & Privacy pointer | Retain | Ensure Privacy is YuktiOne-accurate |
| No guarantee of results | Retain | Fits consultations |
| Limitation of liability | Legal Review | Caps vs law |
| Termination | Retain | Confirm notice channel |
| IP | Retain | Clarify tool-entry ownership |
| Modifications | Legal Review | Notice method |
| Governing law / arbitration | Modify | Fill after confirmation |
| Contact | Modify | Verified contacts only |
| Acknowledgment | Retain | Clickwrap reality |

**Customer-only obligations to preserve:** customer payment duties; customer conduct; customer-side off-platform rules; customer acknowledgment of no outcome guarantee.

---

## 3. Professional Agreement — retain / modify / counsel

| Clause theme | Action | Notes |
|--------------|--------|-------|
| Independent contractor | Retain | Core |
| Scope of consulting fields | Modify | “As listed on Platform”; avoid over-claim |
| Duties, confidentiality, compliance | Retain | Critical |
| Company platform/marketing support | Legal Review | Don’t over-promise marketing |
| Fees / commission / payout schedule | Legal Review | “As agreed onboarding” / bi-weekly-monthly — **BUSINESS VERIFICATION REQUIRED** exact economics |
| Taxes on Professional | Retain + GST clarity | **BUSINESS VERIFICATION REQUIRED** |
| No off-platform solicitation | Legal Review | |
| IP (client materials vs platform) | Retain | |
| Term / 30-day notice / termination for cause | Retain | |
| Indemnity / liability | Legal Review | |
| Dispute / arbitration placeholders | Modify | Fill jurisdiction |
| Non-compete `[6/12 months]` | Legal Review | Placeholder duration; enforceability |
| Non-solicit (incl. 12 months post-term) | Legal Review | Overlap with §17/§54 |
| Data privacy duties | Retain | Align with Privacy |
| Ratings / no coerced reviews | Retain | Matches product |
| Client dispute mediation; Company final say | Legal Review | Fairness |
| Refunds §36 | Modify after Gate G | Match ops |
| Cancel notice `[48 hours]` / penalties | Legal Review | Placeholder |
| Monitoring / audits / training / promo content | Legal Review | Scope |
| Insurance requirement | Legal Review | Whether actually required |
| Health/job/astrology special rules §45 | Clarify or remove vagueness | |
| Numbering gaps (16, 30 missing) | Editorial | Fix on publish |
| Signature block vs clickwrap | Modify | Reflect “I Agree” UX |
| Notices email | Retain config email unless replaced | |

**Professional-only obligations to preserve:** KYC accuracy; licensing; confidentiality of client data; commission/payout; availability/scheduling; quality; non-solicit/non-compete (if retained by counsel); marketing/profile license; indemnity for services.

---

## 4. Cross-document conflict register

| Conflict ID | Documents | Issue | Resolution owner |
|-------------|-----------|-------|------------------|
| X1 | Customer §5 vs Professional §36 vs Public Terms disputes | Refund rules contradict | Business + counsel (Gate G) |
| X2 | Customer “Yuktione company” vs Professional “operator” vs Public Tazen/Suntree | Operator identity | Gate A |
| X3 | Public Terms marketplace vs both agreements consultation model | Product model | Counsel + product |
| X4 | Customer Privacy pointer vs live Tazen Privacy | Privacy accuracy | Gate F |
| X5 | Blade agreements vs PDFs | Parallel outdated PDFs | F12C versioning |
| X6 | Register accepts Customer partial; footer links Public Terms | User confusion | Sync + optional UI copy fix (approved) |

---

## 5. Recommended post-approval document set

1. **Public Terms** — general  
2. **Public Privacy** — general  
3. **Customer User Agreement** — role-specific addendum/companion (what register modal shows)  
4. **Professional Consultant Agreement** — role-specific (what professional agree shows)  
5. **Cancellation & Refund Policy** — single source referenced by 1–4 (may be section or separate page — **design decision**)  
6. **Versioned PDFs** generated from 3 and 4  

---

## 6. Fresh acceptance note

Updating S3/S4 content in F12C does **not** automatically bind users who previously accepted older text. Re-consent design is a **separate approval** (see `02` §5 and `07`).
