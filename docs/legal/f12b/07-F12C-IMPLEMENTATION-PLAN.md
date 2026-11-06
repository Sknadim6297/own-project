# 07 — F12C Implementation Plan (Post-Approval Only)

**DRAFT — NOT APPROVED — NOT FOR PUBLICATION**

**Phase:** Planning for a future F12C  
**Trigger:** All gates in `06-BUSINESS-LEGAL-APPROVAL-CHECKLIST.md` closed + explicit F12C authorization  
**This file does not authorize work.** Do not begin F12C automatically after F12B.

---

## 1. Preconditions

1. Gates A–H complete with filed evidence.  
2. Final approved text delivered for: Public Terms, Public Privacy, Customer Agreement, Professional Agreement, Cancel/Refund policy (section or page).  
3. Re-consent decision documented.  
4. Version IDs + effective date assigned (example scheme: `YO-TERMS-2026-__-v1` — final IDs **BUSINESS VERIFICATION REQUIRED**).  
5. Programmes remain disabled unless separately approved.

---

## 2. File-by-file change plan (after approval)

| # | File / asset | Planned change | Notes |
|---|--------------|----------------|-------|
| 1 | `resources/views/frontend/sections/terms.blade.php` | Replace live Tazen marketplace body with **approved** Public Terms; YuktiOne brand; verified operator block | No inventing identity |
| 2 | `resources/views/frontend/sections/privacy.blade.php` | Replace live Tazen Privacy with **approved** Privacy; remove unverified claims; add KYC + tool data | Contacts only verified |
| 3 | `resources/views/frontend/partials/terms_agreement.blade.php` | Replace with **approved** Customer Agreement; keep role-specific duties | Register modal source |
| 4 | `resources/views/professional/terms_agreement.blade.php` | Replace with **approved** Professional Agreement; keep role-specific duties; fix numbering/placeholders | Clickwrap source |
| 5 | `public/pdf/user-terms-and-conditions.pdf` | Regenerate from approved Customer Agreement **or** retire route | Do not leave Tazen-titled PDF |
| 6 | `public/pdf/professional-terms-and-conditions.pdf` | Regenerate from approved Professional Agreement **or** retire route | Match Blade |
| 7 | `resources/views/frontend/sections/footer.blade.php` | Confirm `/terms` `/privacy` links; optional version note only if approved | Avoid redesign |
| 8 | `resources/views/frontend/login/register.blade.php` | Copy/label tweaks only if approved (e.g. link Privacy); **do not** change acceptance mechanism unless re-consent design requires | Consent logic change = separate decision |
| 9 | `resources/views/frontend/login/login.blade.php` | Same as above for shared login terms UI | |
| 10 | `resources/views/professional/login/login.blade.php` | Same for professional agree UI | |
| 11 | Booking/payment views (e.g. summary/booking) | Optional approved link to Terms/Refund policy; **no** payment logic change | |
| 12 | Tool UIs (mood/journal/gratitude) | Optional approved short privacy notice + Privacy link | No tool logic change |
| 13 | `config/yuktione.php` | Only if approved: address, legal email, grievance fields — **no fabricated values** | Prefer config-driven identity block |
| 14 | Invoice templates | If Gate A/V9 requires operator/GST on invoices | Separate content approval |
| 15 | Optional new page | Cancellation & Refund Policy route/view **if** approved as standalone | |
| 16 | Optional Cookie Policy | Only if Gate F requires | |
| 17 | Version archive | Store approved Markdown/PDF masters under e.g. `docs/legal/approved/vX/` | Not live-serving unless intended |

**Explicit non-goals for F12C legal content swap:**

- No auth redesign  
- No booking/payment logic changes  
- No DB migrations unless re-consent storage is separately approved  
- No programmes enablement  
- No silent rewrite of historical consent records to new version IDs without re-accept flow  

---

## 3. Tests to add/validate (F12C)

| Test focus | Intent |
|------------|--------|
| `GET /terms` returns 200; contains approved operator/brand markers; does **not** contain Tazen marketplace Gig language | Public Terms publish check |
| `GET /privacy` returns 200; no `help@Tazen.com`; contains approved contact; mentions tool/KYC categories if approved | Public Privacy publish check |
| Footer links resolve to `/terms` and `/privacy` | Link integrity |
| Register page includes `terms_accepted` and opens Customer Agreement content markers | Acceptance surface intact |
| Professional login still requires agree flow (mechanism unchanged unless redesign approved) | Acceptance surface intact |
| `user.terms` / `professional.terms` PDF routes return approved PDFs or intentional 410/redirect | PDF sync |
| Optional: snapshot/assert version string / effective date present | Versioning |

Prefer Feature tests under `tests/Feature/Legal/` (name TBD in F12C). Do not weaken existing auth tests.

---

## 4. Versioning & release process (proposed)

1. Freeze approved text in `docs/legal/approved/<version>/`.  
2. PR limited to legal content surfaces + tests.  
3. QA on staging: read-through S1–S6; register/login agree still works.  
4. If re-consent required: ship notice/blocking modal **in same or prerequisite PR** per approved design.  
5. Production deploy only after QA sign-off.  
6. Record publication timestamp and version in ops log.

---

## 5. Re-consent (must decide before F12C coding)

| Decision | Implication |
|----------|-------------|
| No re-consent (counsel-approved) | Content swap only; document risk acceptance |
| Soft notice | Banner/email; continued use = accept |
| Hard re-accept | Block booking/tools/login until new agree; may need storage of version accepted |

**Do not** implement any option without Gate H decision.

---

## 6. Rollback

Keep pre-F12C Blade/PDF copies in git history. Rollback = revert PR. If re-consent already collected for new version, rollback needs counsel advice (do not invent procedure here).

---

## 7. Stop condition for this phase

F12B ends when this plan and sibling docs are delivered.  
**Do not start F12C** until gates close and a human explicitly requests F12C.
