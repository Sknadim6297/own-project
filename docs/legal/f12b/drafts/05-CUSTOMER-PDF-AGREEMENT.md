# Customer PDF Agreement — REVIEW DRAFT (F12B)

> **NOT APPROVED — NOT FOR PUBLICATION**  
> Live asset today: `public/pdf/user-terms-and-conditions.pdf` via `GET /user/terms-and-conditions`.  
> Historical audit: PDF title artifact **“Updated Customer Agreement Tazen”** — treat as Tazen-era; **do not** republish as YuktiOne law.

---

## Publication rule

| Rule | Requirement |
|------|-------------|
| Single source of truth | Approved **Customer User Agreement** (`03-CUSTOMER-AGREEMENT.md` → Blade) |
| PDF content | Must be an export/snapshot of that **same** approved text |
| Branding | YuktiOne + verified operator block only |
| Version stamp | Document ID + Effective Date + Version on cover/footer |
| Placeholders | None remaining at PDF generation time |
| Tazen references | Must be absent |

---

## F12C options (choose at Gate H)

1. **Regenerate** PDF from approved Customer Agreement and replace `public/pdf/user-terms-and-conditions.pdf`, **or**  
2. **Retire** the route / return intentional redirect to `/terms` + Customer modal text, if counsel prefers no separate PDF.

Until Gate H: **do not** overwrite the live PDF.

---

## Checklist before generating PDF

- [ ] Gate A operator block filled  
- [ ] Gate G refund language inserted  
- [ ] Gate D Customer Agreement approved  
- [ ] Jurisdiction / contacts / effective date filled  
- [ ] Counsel Gate C / D sign-off filed  
