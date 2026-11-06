# 01 — Operator Verification

**DRAFT — NOT APPROVED — NOT FOR PUBLICATION**  
**Phase:** F12B closure (reconciled with prior F12B `01` content)  
**Proposed operator (business-provided, unverified):** Suntree Consulting LLP  
**Platform / brand (config-verified):** YuktiOne  

**Rule:** Do not treat this document as proof of incorporation, authority to operate YuktiOne, or legal approval.  
**MCA:** Independent verification via MCA primary records was **not** completed in this engagement → mark pending.

---

## Classification legend

| Label | Meaning |
|-------|---------|
| **VERIFIED** | Supported by identified authoritative evidence in this project (config/live file/repo artifact) |
| **PROPOSED** | Supplied for F12B review but not independently verified |
| **MISSING** | No evidence available in workspace |
| **APPROVAL REQUIRED** | Requires business-owner and/or counsel confirmation before publication |

---

## 1. Field-by-field record

| Field | Classification | Current value / note | Authoritative evidence identified |
|-------|----------------|----------------------|-----------------------------------|
| Brand / product name | **VERIFIED** | YuktiOne | `config/yuktione.php` → `name` |
| Brand spelling variant in UI | **VERIFIED** | “Yuktione” also used (Customer agreement title) | Customer partial; UI copy |
| Public support email (config) | **VERIFIED** | `info@yuktione.in` | `config/yuktione.php` |
| Phone | **VERIFIED** | `+91 96050 08040` | `config/yuktione.php` |
| Hours | **VERIFIED** | Mon–Fri 9–6; Sat 9–2 | `config/yuktione.php` |
| Registered office in brand config | **VERIFIED** (absent) | `null` | `config/yuktione.php` |
| YuktiOne as registered legal entity | **MISSING** / not claimed | No incorporation evidence that the brand is itself an entity | None |
| Proposed registered operator name | **PROPOSED** | Suntree Consulting LLP | Business-provided for F12B only |
| Live Terms/Privacy entity string | **VERIFIED** as live text; **not** verification of operator | “Tazen (Part Of Suntree Consulting LLP)” | `resources/views/frontend/sections/terms.blade.php`, `privacy.blade.php` |
| LLPIN | **MISSING** / **APPROVAL REQUIRED** | No LLPIN filed in workspace | None. Third-party aggregator claims exist on the public web but **conflict on partners** and are **not** MCA-primary evidence — **do not treat as VERIFIED** |
| MCA registration status | **MISSING** / **APPROVAL REQUIRED** | Independent MCA.gov.in check **pending** | None filed |
| Registered office address (legal) | **MISSING** / **APPROVAL REQUIRED** | Not configured; not owner-approved for publish | None |
| Authorized representative | **MISSING** / **APPROVAL REQUIRED** | — | None |
| Proof LLP operates YuktiOne | **MISSING** / **APPROVAL REQUIRED** | — | None |
| Legal notice email | **APPROVAL REQUIRED** | Candidates: config `info@yuktione.in`; live Privacy `help@Tazen.com` (legacy — do not reuse without confirmation) | Config vs live conflict |
| Privacy / grievance contact | **MISSING** / **APPROVAL REQUIRED** | No officer name/title | None |
| Invoice supplier / GSTIN | **MISSING** / **APPROVAL REQUIRED** | GST fields exist in booking/profile schemas; operator GSTIN not in brand config | Schema ≠ identity proof |
| Governing law / venue | **MISSING** / **APPROVAL REQUIRED** | Placeholders in Customer & Professional agreements | Agreement Blades |
| Documents effective date | **MISSING** / **APPROVAL REQUIRED** | `[Insert Date]` / `[Date]` | Agreement Blades |

---

## 2. What third-party web results do **not** establish

Public company-data aggregators may list an LLP named “Suntree Consulting LLP” with various attributes. Those listings:

- Are **not** MCA Master Data extracts filed in this repository  
- Show **inconsistent partner listings** across sources  
- Do **not** prove the LLP operates the YuktiOne product  
- Must **not** be copied into published Terms as verified facts  

**Independent MCA verification status: PENDING.**

---

## 3. Identity record template (do not invent values)

```
PROPOSED OPERATOR IDENTITY RECORD — PENDING VERIFICATION
-------------------------------------------------------
Brand / product:                 YuktiOne
Proposed operator legal name:    Suntree Consulting LLP   [PROPOSED]
LLPIN:                           [MISSING — APPROVAL REQUIRED]
MCA status (date of extract):    [MISSING — MCA VERIFICATION PENDING]
Registered office:               [MISSING — APPROVAL REQUIRED]
Authorized representative:       [MISSING — APPROVAL REQUIRED]
Proof LLP operates YuktiOne:     [MISSING — APPROVAL REQUIRED]
Legal notice email:              [APPROVAL REQUIRED]
Privacy / grievance contact:     [MISSING — APPROVAL REQUIRED]
Invoice supplier identity:       [MISSING — APPROVAL REQUIRED]
GSTIN (if applicable):           [MISSING — APPROVAL REQUIRED]
Governing law / venue:           [MISSING — APPROVAL REQUIRED]
Documents effective date:        [MISSING — APPROVAL REQUIRED]
Verification completed by:       ______________________
Verification date:               ______________________
Counsel acknowledgment:          ______________________
MCA primary extract attached:    [ ] Yes  [ ] No
```

---

## 4. Gate A linkage

Until V1–V6 (name, LLPIN, MCA status, office, authorized representative, proof of YuktiOne operation) plus legal/grievance contacts are evidenced and counsel-accepted:

- **Gate A = OPEN**  
- **Legal P0 = OPEN**  
- **F12C = NOT AUTHORIZED**

---

## 5. Reconciliation note

This file replaces the prior F12B `01-OPERATOR-VERIFICATION.md` classification labels with the required VERIFIED / PROPOSED / MISSING / APPROVAL REQUIRED scheme while preserving the same substance: proposed operator only; no fabricated MCA closure.
