# 02 — Gates A–H Approval Matrix

**DRAFT — NOT APPROVED — NOT FOR PUBLICATION**  
**Canonical gate definitions:** preserved from `06-BUSINESS-LEGAL-APPROVAL-CHECKLIST.md` (unchanged checklist text).  
**Rule:** Do **not** mark any gate APPROVED without explicit authorization **and** filed evidence.

---

## Gate definitions (exact — from `06`)

### Gate A — Operator identity verification

- [ ] Full registered legal name confirmed (proposed: Suntree Consulting LLP)  
- [ ] LLPIN verified against MCA  
- [ ] Current MCA status confirmed  
- [ ] Registered office address confirmed  
- [ ] Authorized representative identified  
- [ ] Evidence LLP operates YuktiOne accepted by counsel  
- [ ] Official legal notice email confirmed  
- [ ] Privacy/grievance contact confirmed  
- [ ] Invoice supplier identity / address confirmed  
- [ ] GST details confirmed or marked N/A with reason  

### Gate B — Authorized business representative approval

- [ ] Named authorized representative approves publication package for the operator  
- [ ] Brand spelling (YuktiOne / Yuktione) standardized for legal docs  
- [ ] Config contacts (`info@yuktione.in`, phone) confirmed for public use where intended  

### Gate C — Qualified legal review of final documents

- [ ] Final Public Terms reviewed by qualified counsel  
- [ ] Final Public Privacy reviewed by qualified counsel  
- [ ] Counsel confirms drafts are suitable for publication in the chosen jurisdiction  
- [ ] Counsel memo / email approval filed  

### Gate D — Customer agreement approval

- [ ] Customer User Agreement text approved  
- [ ] Role-specific customer obligations retained  
- [ ] Placeholders removed (date, jurisdiction, contacts, address)  
- [ ] Alignment with Public Terms/Privacy confirmed  

### Gate E — Professional agreement approval

- [ ] Professional Consultant Agreement text approved  
- [ ] Role-specific professional obligations retained  
- [ ] Commission/payout/tax language matches operations  
- [ ] Non-compete / non-solicit disposition decided  
- [ ] Refund §36 aligned to Gate G  
- [ ] Placeholders removed  

### Gate F — Privacy / data-handling approval

- [ ] Data inventory approved (accounts, KYC, bookings, payments, chat, tools)  
- [ ] Sensitive tool-data handling approved  
- [ ] Vendor/processor list approved (incl. Razorpay)  
- [ ] Retention and deletion process approved (no invented auto-delete claims)  
- [ ] Security statements limited to evidenced controls  
- [ ] Rights + grievance process approved  
- [ ] Cookie/analytics disclosure decision made  

### Gate G — Cancellation / refund policy approval

- [ ] Written cancellation policy approved  
- [ ] Written refund policy approved  
- [ ] Policies match **actual** product capability (or product work scheduled before publish)  
- [ ] Conflicts removed among Customer “non-refundable”, Professional discretionary refunds, and public dispute text  
- [ ] No promise of automatic refunds unless implemented and approved  

### Gate H — Effective date and publication authorization

- [ ] Effective date set  
- [ ] Document version IDs assigned  
- [ ] Fresh-acceptance / re-consent decision recorded (required / not required + method)  
- [ ] Explicit written authorization to begin **F12C** implementation  
- [ ] Explicit written authorization for production deployment **after** F12C QA (separate from this gate if desired)  

---

## Status matrix

| Gate | Required decision / evidence | Available evidence | Missing information | Responsible approver | Current status | Next action |
|------|------------------------------|--------------------|---------------------|----------------------|----------------|-------------|
| **A** Operator identity | MCA-backed name, LLPIN, status, office; authorized rep; proof LLP operates YuktiOne; legal + grievance contacts; invoice/GST | Brand config contacts; live Tazen/Suntree **wording only** (not proof); proposed name “Suntree Consulting LLP” | Primary MCA extract; LLPIN filed; office; rep; operation proof; grievance officer; GSTIN | Business owner + counsel | **OPEN** | File MCA primary extract + operation proof; complete `01-OPERATOR-VERIFICATION.md` identity record |
| **B** Business representative | Named person with authority to approve publication; brand spelling standard; confirm public contacts | Config email/phone | Named authorized representative; spelling standard decision; contact-role mapping | Authorized business representative | **OPEN** | Name approver; issue written package approval |
| **C** Counsel review | Counsel review of final Terms + Privacy; fitness for jurisdiction; memo filed | F12A drafts + F12B review packs (`03`/`04` + `drafts/`) | Final counsel-approved text; counsel memo | Qualified counsel | **OPEN** | Counsel review after Gate A facts filled |
| **D** Customer agreement | Approved Customer Agreement; placeholders removed; aligned to Public Terms/Privacy | Live Customer partial (placeholders); gap analysis `05`; draft shell in `drafts/` | Owner/counsel-approved final Customer text | Business + counsel | **OPEN** | Resolve refunds (Gate G) + identity; approve Customer draft |
| **E** Professional agreement | Approved Professional Agreement; economics; non-compete disposition; refunds aligned | Live Professional partial (operator unnamed; placeholders); gap analysis `05` | Commission/payout confirmed; non-compete decision; final text | Business + counsel | **OPEN** | Decide economics + non-compete; approve Professional draft |
| **F** Privacy / data | Inventory, KYC, tools, vendors, retention, security honesty, rights/grievance, cookies | F12A Privacy draft; live Tazen Privacy (wrong); product capabilities known at high level | Approved inventory; retention schedules; vendor list; cookie decision; grievance process | Business + counsel (+ DPO if appointed) | **OPEN** | Complete data inventory workshop; approve Privacy draft |
| **G** Cancel / refund | Single written cancel + refund policy matching ops | Conflicting texts across S1/S3/S4; no automated refund API verified historically | Unified policy; product capability match | Business ops + counsel | **OPEN** | Write and approve policy; schedule product work if promising self-serve cancel/auto-refund |
| **H** Effective date / publish auth | Effective date; versions; re-consent decision; **explicit F12C authorization** | None | All of the above | Business owner (+ counsel for re-consent) | **OPEN** | Only after A–G; issue written F12C go-ahead |

---

## Aggregate

| Item | Status |
|------|--------|
| Any gate APPROVED? | **No** |
| Legal P0 | **OPEN** |
| F12C authorized by Gates? | **No** |
