# F12A — Clause-by-Clause Gap Matrix

**Legend — Action:** Retain | Modify | Remove | Legal Review  
**Entity column:** what the live text currently names as operator.  
**Proposed text:** see DRAFT files; short pointers only here.

---

## A. Public Terms (`frontend/sections/terms.blade.php`)

| # | Existing section / heading | Current legal entity ref | Relevance to YuktiOne | Action | Reason | Business confirmation needed | DRAFT direction |
|---|----------------------------|--------------------------|----------------------|--------|--------|------------------------------|-----------------|
| T0 | Title / meta “YuktiOne” vs body | Page chrome YuktiOne; body Tazen/Suntree | Brand inconsistency | Modify | Chrome and body conflict | Confirm operator vs brand naming | Align chrome + body after identity confirmed |
| T1 | Opening: Tazen website/app; Tazen.in | Tazen / Suntree Consulting LLP | Low as written | Modify | Wrong product & domain | Confirm registered operator; domains | YuktiOne consultation platform Terms |
| T2 | Acceptance by use / account / click | Tazen | High (mechanism) | Retain (adapted) | Standard acceptance | Confirm clickwrap surfaces | Keep click/use acceptance |
| T3 | Sellers/buyers must abide while logging in | Tazen marketplace | Partial | Modify | Roles are Customer / Professional | Confirm role labels | Customer & Professional |
| T4 | Only registered users may buy and sell | Tazen marketplace | Low as “buy/sell” | Modify | Booking/payments, not gig marketplace | Confirm guest browse vs paid booking rules | Registered users for paid bookings |
| T5 | Account accuracy / password / liability | Generic | High | Retain | Still applicable | — | Keep |
| T6 | General Terms — Sellers set pricing | Marketplace | Partial | Modify | Pros set rates; platform may take commission | Confirm commission model | Professional rates + platform fees |
| T7 | Buyers pay in advance to create Order | Marketplace “Order” | Partial | Modify | Razorpay prepay for consultations verified | Confirm prepaid rule exceptions | Advance payment for bookings |
| T8 | Sellers fulfill Orders; cancel affects level | Marketplace leveling | Low | Remove / Replace | No leveling system verified | Confirm cancel consequences | Booking fulfillment + account status |
| T9 | Marketplace; Sellers as independent contractors | Marketplace framing | Partial | Modify | Independent contractor model fits pros; “marketplace for digital services” misfit | Confirm contractor status language | Platform connects customers to independent professionals |
| T10 | Right to use delivered work in reviews | Gig delivery | Partial | Modify | Reviews exist; “delivered work” gig language outdated | Confirm review license | License for reviews / public feedback |
| T11 | Violations / Trust & Safety / leveling hold | Tazen | Partial | Modify | Warnings/disable exist conceptually; leveling does not | Confirm enforcement process | Warnings, hold, disable without leveling |
| T12 | Adult services & pornography | Tazen | High (conduct) | Retain (adapted) | Still relevant | — | Keep prohibition |
| T13 | Inappropriate behavior & language; Community Forum | Tazen | Partial | Modify | Conduct relevant; Community Forum not launched | Confirm community plans | Professional communication; drop forum unless launched |
| T14 | Phishing and spam | Tazen | High | Retain | Relevant | — | Keep |
| T15 | Privacy & Identity; Order Page; off-platform unprotected | Marketplace | Partial | Modify | Booking chat + PII filter exist; “Order Page” outdated | Confirm off-platform policy | On-platform communications; confidentiality |
| T16 | Authentic profile / verification | Tazen | High | Retain (adapted) | Pro verification/docs exist | Confirm ID/doc requirements published | Accurate profiles; verification |
| T17 | IP claims | Tazen | High | Retain | Relevant | Confirm notice channel | Keep with YuktiOne contact |
| T18 | Fraud / unlawful use | Tazen | High | Retain | Relevant | — | Keep |
| T19 | Selling accounts | Tazen | High | Retain | Relevant | — | Keep |
| T20 | Disputes and Cancellations; Resolution Center; help@tazen.in | Tazen | Low as written | Modify | No Resolution Center verified; refunds not automated | Confirm dispute + refund policy | Support-mediated disputes; honest refund capability |
| T21 | Content moderation, notices, appeals | Tazen | High | Retain (adapted) | Reviews/UGC/moderation exist | Confirm appeal process | Keep |
| T22 | Feedback rights | Tazen | High | Legal Review | Broad exclusive grant | Confirm commercial intent | Keep subject to counsel |
| T23 | Confidentiality (Sellers/Buyers) | Marketplace labels | High | Modify | Customer–Professional confidentiality fits | — | Relabel roles |
| T24 | User Generated Content | Tazen | High | Retain (adapted) | Chat, reviews, docs, blogs | — | Keep |
| T25 | Disclaimer of Warranties | Tazen | High | Retain (adapted) + Legal Review | Still needed; add consultation outcome disclaimer | Confirm medical/legal/health disclaimers | Keep + no-outcome guarantee |
| T26 | Limitation on Liability | Tazen | High | Legal Review | Cap/carve-outs for India consumer law | Confirm liability approach | Keep pending counsel |
| T27 | Deactivate and Disable Account | Tazen | High | Retain (adapted) | Admin/status flows exist | Confirm grounds | Keep |

---

## B. Public Privacy (`frontend/sections/privacy.blade.php`)

| # | Existing section / heading | Current legal entity ref | Relevance | Action | Reason | Business confirmation | DRAFT direction |
|---|----------------------------|--------------------------|-----------|--------|--------|------------------------|-----------------|
| P0 | Title YuktiOne vs body Tazen/Suntree | Mixed | Brand conflict | Modify | Same as Terms | Operator identity | Align after confirmation |
| P1 | Intro / Site includes Tazen.in, mobile app, affiliates, influencers | Tazen | Low as written | Modify | YuktiOne web platform verified; affiliate/influencer programs not verified | Confirm apps/programs | Scope to YuktiOne Site + services |
| P2 | Short version — collection summary | Tazen | Partial | Modify | Over-claims marketplace/forum; under-claims tools/KYC | Confirm data inventory | Match verified categories |
| P3 | Legal basis summary | Generic | High | Retain (adapted) / Legal Review | Useful; map to Indian law if applicable | Confirm governing privacy law | Keep structure |
| P4 | Use of information — marketplace integrity, Gig suggestions | Tazen marketplace | Low wording | Modify | Booking platform integrity yes; gigs no | — | Platform integrity; no gigs |
| P5 | Retention summary | Generic | High | Modify | No concrete periods | Confirm retention schedules | Purpose-based + confirmed periods |
| P6 | Sharing summary | Generic | High | Modify | List real processors (e.g. Razorpay, hosting, mail) | Confirm vendor list | Named categories of processors |
| P7 | Where processed | Generic | High | Legal Review | Cross-border unclear | Confirm hosting locations | Honest location statement |
| P8 | Cookies + Cookie Policy link | Tazen | Partial | Modify | Cookies/session likely; dedicated Cookie Policy page not verified | Confirm cookie notice | Cookies section; fix or remove dead link |
| P9 | External links | Tazen | High | Retain | Relevant | — | Keep |
| P10 | Security | Tazen | High | Retain | Relevant | — | Keep |
| P11 | Updating PI | Tazen | High | Retain | Profile settings exist | — | Keep |
| P12 | Contact help@Tazen.com | Tazen | Low | Modify | Wrong contact | Confirm privacy/grievance email | Use confirmed contact |
| P13 | Information You Provide — gigs, SSO social, biometrics, payment card numbers, skills tests | Tazen marketplace | Mixed | Modify | Over-collect vs YuktiOne; under-states mood/journal, KYC docs, MCQ, booking docs | Confirm card data handling (Razorpay vs stored) | Verified collection only |
| P14 | Automated collection / impression reporting / ads | Tazen | Partial | Modify | IP/currency middleware verified; ad stack not verified | Confirm analytics/ads | Verified telemetry only |
| P15 | Third parties — enrichment, Google/FB/LinkedIn login, Google Workspace invites, collaboration/referral | Tazen | Low unless launched | Remove / Modify | SSO/Workspace invites not verified for YuktiOne | Confirm any OAuth | Drop unverified sources |
| P16 | Legal basis detail + “TNC while logging in” | Mixed | Partial | Modify | Informal add-on | — | Clean legal-basis section |
| P17 | Uses — Gig suggestions, ranking, marketplace risk | Marketplace | Modify | Same as P4 | — | Consultation discovery / safety |
| P18 | Direct marketing | Tazen | Partial | Legal Review | Marketing may occur; consent rules | Confirm marketing consent practice | Opt-out / consent language |
| P19 | Retention detail + DOB note | Tazen | Partial | Modify | DOB note may be outdated relative to forms | Confirm DOB/age collection | Accurate retention + minors note |
| P20 | Sharing — public username/country/gigs/portfolio; business seller EU trader disclosure | Marketplace | Low | Modify | Public pro profiles/reviews exist; gig portfolio trader rules misfit | Confirm what is public on profiles | Public professional listings + reviews |
| P21 | Sharing with other users / first purchase tip to Seller | Marketplace | Partial | Modify | Booking shares needed customer/booking data with professional | Confirm fields shared | Booking-necessary sharing |
| P22 | Service providers list | Generic | High | Modify | Must reflect Razorpay etc. | Vendor confirmation | Update list |
| P23 | Reports/complaints; legal; payments/fraud | Generic | High | Retain (adapted) | Relevant | — | Keep |
| P24 | Social media partners / SSO | Tazen | Low unless verified | Remove / Modify | Not verified | Confirm | Drop if unused |
| P25 | Corporate affiliates | Tazen | Unknown | Legal Review | Depends on real group structure | Confirm affiliates | Only if true |
| P26 | Corporate transaction | Generic | High | Retain | Standard | — | Keep |
| P27 | Deleting / updating; sections 13–14 referenced but missing | Broken structure | High | Modify | Cross-ref error in live text | Confirm deletion process | Clear rights + contact |
| P28 | Contact Us | help@Tazen.com | Low | Modify | Wrong | Confirm | Verified contact |
| — | **Missing:** sensitive tool data (mood/journals) | — | High | Add | Implemented tools collect personal reflections | Confirm sensitivity handling | Dedicated subsection |
| — | **Missing:** professional KYC / education / GST docs | — | High | Add | Collected at pro registration | Confirm retention/access | Dedicated subsection |
| — | **Missing:** grievance officer (if required) | — | High | Add if applicable | India compliance question | Confirm | Placeholder until confirmed |

---

## C. Customer User Agreement (`frontend/partials/terms_agreement.blade.php`) — what registration checkbox opens

| # | Section | Entity | Relevance | Action | Reason | Business confirmation | DRAFT |
|---|---------|--------|-----------|--------|--------|------------------------|-------|
| C1 | Effective Date `[Insert Date]` | Yuktione | High | Modify | Placeholder | Effective date | Fill after approval |
| C2 | Definitions — Yuktione “the company” | Assumes company = brand | High | Legal Review | May conflate brand and operator | Registered operator name | Operator of YuktiOne platform |
| C3 | Services list (astrology, interior, etc.) | Yuktione | Partial | Modify | Categories may track catalogue; do not over-claim | Confirm service taxonomy | “Services listed on the platform” |
| C4 | Future workshops/events | Future | Partial | Modify | Programmes disabled | Confirm | Mark future / not currently offered |
| C5 | Registration / legal age | High | Retain | Needed | Confirm age gate | Keep |
| C6 | User conduct / no external pro contact | High | Retain / Legal Review | Off-platform rule exists | Confirm enforcement | Keep |
| C7 | Payment; generally non-refundable | High | Legal Review | Conflicts with discretionary refunds elsewhere; no automated refunds | Confirm refund policy | Align with confirmed policy only |
| C8 | Confidentiality & privacy pointer | High | Retain | Needed | — | Keep + link Privacy |
| C9 | No guarantee of results | High | Retain | Fits consultation model | — | Keep |
| C10 | Limitation of liability | High | Legal Review | Caps | Confirm | Keep pending counsel |
| C11 | Termination | High | Retain | Needed | Confirm written notice channel | Keep |
| C12 | IP | High | Retain | Needed | Confirm ownership of tool content | Keep |
| C13 | Modifications | High | Retain / Legal Review | Clickwrap notice practice | Confirm notice method | Keep |
| C14 | Governing law & arbitration placeholders | High | Modify | Empty placeholders | Jurisdiction + venue | Fill after confirmation |
| C15 | Contact placeholders | High | Modify | Empty | Email/phone/address | Use verified + BCR for address |
| C16 | Acknowledgment | High | Retain | Needed | — | Keep |
| — | **Gap vs public Terms** | — | P0 | Align | Users accept C, public reads A | Single coherent Terms | Unify in F12B after approval |

---

## D. Professional Consultant Agreement (`professional/terms_agreement.blade.php`)

| # | Theme / sections | Entity | Relevance | Action | Reason | Business confirmation | DRAFT |
|---|------------------|--------|-----------|--------|--------|------------------------|-------|
| R1 | Operator of YuktiOne; no registered name | “Company” = operator | High | Legal Review | Honest about unknown entity; incomplete for contracting | Registered operator | Insert confirmed legal name |
| R2 | Scope; independent contractor | High | Retain | Fits | — | Keep |
| R3 | Duties, confidentiality, compliance | High | Retain | Fits | Licensing claims | Keep |
| R4 | Fees/commission; bi-weekly/monthly pay | High | Legal Review | Commission/payout schedule must match ops | Confirm rates & schedule | Only confirmed economics |
| R5 | No off-platform solicitation | High | Legal Review | Enforceability | Confirm policy | Keep pending counsel |
| R6 | IP; client materials | High | Retain | Fits | — | Keep |
| R7 | Term 30-day notice; termination for cause | High | Retain | Fits | — | Keep |
| R8 | Indemnity; liability limits | High | Legal Review | — | Confirm | Keep pending counsel |
| R9 | Dispute / arbitration `[Jurisdiction]` | High | Modify | Placeholder | Jurisdiction | Fill |
| R10 | Non-compete `[6/12 months]` | High | Legal Review | Placeholder + restrictive | Confirm duration/need | Counsel |
| R11 | Data privacy duties | High | Retain | Critical with client chat/docs | — | Keep + align Privacy |
| R12 | Ratings; dispute mediation; refunds §36 | High | Modify / Legal Review | Refunds discretionary; product refunds not automated | Confirm refund ops | Align with capability |
| R13 | Cancel notice `[48 hours]`; penalties | High | Legal Review | Placeholder | Confirm SLA/penalties | Fill |
| R14 | Specialized consultants (health/job/astrology) | Partial | Legal Review | Extra duties vague | Confirm | Clarify or remove vagueness |
| R15 | Notices → `info@yuktione.in` | Verified email | High | Retain | Config-backed | Keep unless grievance email differs |
| R16 | Missing section numbers 16, 30 | Editorial | Low | Modify | Numbering gaps | — | Renumber on publish |
| R17 | Signature block | Form | Partial | Modify | Clickwrap “I Agree” is actual UX | Confirm wet-ink needed | Reflect click acceptance |

---

## E. PDF artifacts

| Doc | Observation | Action |
|-----|-------------|--------|
| `user-terms-and-conditions.pdf` | Titled “Updated Customer Agreement Tazen” | Legal Review; do not treat as current YuktiOne Terms without reissue |
| `professional-terms-and-conditions.pdf` | “Consultant Online Agreement” title strings | Legal Review; reconcile with Blade agreement |

---

## Outdated marketplace / gig clauses (do not auto-delete solely for age)

Flagged for **Modify/Remove after legal review** because they do not match verified consultation booking:

- Buyers/Sellers gig marketplace; Orders; Gig suggestions; seller levels
- Resolution Center
- Community Forum (as launched feature)
- Affiliate / influencer / collaborator programs
- Social SSO / Google Workspace directory invites
- EU “business seller (trader)” Gig disclosure block
- Portfolio/Gig public marketplace profile model

**Retain intent** where still useful (independent contractors, confidentiality, anti-fraud, UGC, account security) under YuktiOne wording.
