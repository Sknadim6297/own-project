# Issue 08 — Design Approval Checklist

**Project:** YuktiOne (`C:\xampp\htdocs\own-project`)  
**Related report:** `08-DESIGN-REVIEW.md`  
**Status:** Awaiting stakeholder decisions — **no implementation authorized by this document**

Use this checklist in the design discussion. Check items only after an explicit decision.

---

## A. Decisions required

### A1. Canonical primary teal

- [ ] Use **`#36877F`** everywhere (align auth/tools/home to panels / `tazen.css`)
- [ ] Use **`#316D61`** everywhere (align panels / `tazen.css` to auth/tools)
- [ ] Keep both intentionally (document roles: e.g. marketing vs product) — *not recommended*

**Decision / date / owner:** _________________________________

### A2. Lavender (`#9D81DB`) in marketing headlines

- [ ] Keep as secondary headline accent
- [ ] Demote to soft backgrounds / illustrations only
- [ ] Remove from H1/H2 split headlines

**Decision / date / owner:** _________________________________

### A3. Design direction

- [ ] **Option A — Minimal Polish** (recommended starting point)
- [ ] **Option B — Modern Professional**
- [ ] **Option C — Calm Wellness**
- [ ] **A then B** (phased)
- [ ] Other: _________________________________

**Decision / date / owner:** _________________________________

### A4. Home empty modules (testimonials / blogs / FAQs)

- [ ] Design-only (spacing/empty-state polish)
- [ ] Content fill required before visual redesign
- [ ] Hide empty sections until content exists

**Decision / date / owner:** _________________________________

### A5. Scope of unification

- [ ] Marketing + tools + auth only
- [ ] Include Customer dashboard
- [ ] Include Professional dashboard
- [ ] Full site including booking widgets

**Decision / date / owner:** _________________________________

---

## B. Non-negotiable protections (must remain checked)

Confirm future implementers will **not** break:

- [ ] Customer authentication & password recovery (Issue 01 branding preserved)
- [ ] Professional authentication & recovery
- [ ] Intended URL redirects after login
- [ ] Customer / Professional dashboard navigation
- [ ] Booking and payment flows
- [ ] Assessment scoring algorithms
- [ ] Assessment history / View History (Issue 03)
- [ ] Removal of Explore Career Insights CTA (Issue 04)
- [ ] Career button layout fixes (Issue 05)
- [ ] Account recovery behaviour pending Issue 06 business rules
- [ ] Mobile card compaction (Issue 07)
- [ ] Tool availability statuses (Nutrition / Programs stay not re-enabled)
- [ ] Professional approval restrictions
- [ ] Legal pages content

**Acknowledged by:** _________________ **Date:** _________

---

## C. Pre-implementation gates

- [ ] Issue 08 review discussed with stakeholder
- [ ] Section A decisions recorded above
- [ ] Written approval to start Phase 1 (Option A or chosen option)
- [ ] QA viewports agreed: 375 / 768 / 1024 / 1440
- [ ] Rollback plan: revert CSS/Blade only; no DB migrations for design

**Approval to implement:**  
- [ ] **Not approved** (default)  
- [ ] **Approved for Phase ______ only**

**Approver name / role:** _________________________________  
**Date:** _________  
**Notes:** _________________________________

---

## D. Phase 1 acceptance criteria (if Option A approved)

- [ ] Single documented primary teal applied to agreed surfaces
- [ ] No dual-teal drift on home primary CTAs vs auth primary buttons (per A1)
- [ ] `YuktiOne` spelling consistent on reviewed chrome titles
- [ ] No horizontal overflow @ 375
- [ ] Auth login/register/forgot still functional
- [ ] Tools catalogue statuses unchanged
- [ ] Automated smoke: auth branding + tools page tests pass

---

## E. Explicit reminders

1. This checklist **does not** approve a redesign by itself.  
2. Issue 08 produced **audit documents only** — no CSS/Blade theme rewrite was done.  
3. Do not start Option B/C work until A3 is checked and Phase 1 (if any) is accepted.
