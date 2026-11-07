# Video Issue 08 — Overall Layout & Colour Design Review

**Date:** 2026-11-07  
**Project root:** `C:\xampp\htdocs\own-project`  
**Laravel:** 10.49.1  
**Mode:** **READ-ONLY** (no CSS, Blade, JS, or theme implementation)  
**Stakeholder line:** *“The overall layout and colours are not looking good. Need to discuss.”*

**Brand reference (guidance, not a mandate to recolour everything):**

- Primary teal: `#36877F`
- Navy: `#142B4D`
- Typography: DM Sans

---

## 1. Current design assessment

YuktiOne already has a recognizable identity: **DM Sans**, **navy headings**, **teal actions**, soft mint/lilac surfaces, and rounded cards. The public site (home, tools, explore) and the authenticated panels (customer/professional) share the same family but **not one token source**.

### Design token sources (as found)

| Source | Primary teal | Navy | Notes |
|--------|--------------|------|-------|
| `resources/css/tazen.css` (`@theme`) | **`#36877F`** | `#142B4D` | Canonical Tailwind brand scale |
| `resources/css/customer-panel.css` | **`#36877F`** | `#142B4D` | Customer dashboard |
| `resources/css/professional-panel.css` | **`#36877F`** | `#142B4D` | Professional panel |
| `resources/css/auth-theme.css` | **`#316D61`** | `#142B4D` | Login / register / recovery |
| Tool CSS (`yuktione-tools*.css`, career/skills CSS) | **`#316d61`** | `#142B4D` | Tools catalogue & assessments |
| Home hero CTAs (hardcoded in Blade) | **`#316D61`** | mixed | Join / search accents |

**Evidence (live browser, 2026-11-07):**

- Homepage Join CTA computed background: `rgb(49, 109, 97)` = **`#316D61`**.
- Customer dashboard CSS vars: `--yk-primary` / `--color-brand-500` = **`#36877f`**; active nav accent `rgb(54, 135, 127)`.
- Body/UI font on home + dashboard: **`"DM Sans", sans-serif`** (consistent).
- Homepage hero second line uses lavender **`#9D81DB`** (`hero.blade.php`), not teal/navy.

### Visual systems in parallel

1. **Marketing / home** — Tailwind `yk:` utilities + section CSS; pastel multi-tone cards (lavender/mint/blush/cream).  
2. **Tools discovery** — dedicated `yuktione-tools-page.css` cards (Issue 07 compacted mobile spacing).  
3. **Auth** — `auth-theme.css` + Vite; pill/control radii; teal `#316D61`.  
4. **Customer / Professional panels** — panel CSS with `#36877F`; denser dashboard cards.

This split explains why the product can feel “off” even when each screen is locally coherent: **two teals + multi-pastel marketing accents** compete with the stated brand primary.

---

## 2. Specific visual problems (evidence-based)

| ID | Issue | Evidence | Type |
|----|--------|----------|------|
| P1 | **Dual primary teal** (`#36877F` vs `#316D61`) | `tazen.css` / panels vs `auth-theme.css` / tools / home Blade | Consistency (measurable) |
| P2 | **Lavender headline accent** competes with teal brand | Home hero `text-[#9D81DB]`; tools/talk headings use lavender spans | Harmony (subjective + repeated pattern) |
| P3 | **Button language fragmented** | Full pills (home/tools), `rounded-control` (auth), bordered icon buttons (dashboard header) | Consistency |
| P4 | **Card languages fragmented** | Home pastel blocks vs white tool cards vs panel soft cards; radii 12–18px+ | Consistency |
| P5 | **Empty marketing modules** look sparse | Home testimonials / blogs / FAQs empty states in live QA | Layout / content density (content + design) |
| P6 | **Mobile hero asymmetry** | 375px: left-stacked copy, large right whitespace before media | Balance (mobile) |
| P7 | **Brand spelling inconsistency** | Dashboard title `Yuktione` vs public `YuktiOne` | Polish / trust |
| P8 | **Contrast caveat on home CTA text** | Join link computed `color: rgb(0,0,0)` on teal bg in one sample (may be cascade quirk) — worth verifying WCAG on CTAs | Accessibility (investigate before paint changes) |

**Not treated as bugs without evidence:** “I don’t like teal,” pure taste preferences, or demands to match an unapproved external mock.

Issue 07 already reduced mobile tool-card blank space; remaining “too blank” comments on home are partly **empty content modules** and **hero/section spacing**, not only card CSS.

---

## 3. Affected pages (audit coverage)

| Surface | Reviewed | Notes |
|---------|----------|-------|
| Homepage | Yes (browser 375) | Dual teal + lavender headlines + empty modules |
| Header / mobile nav | Yes | Shared layout; hamburger + bell |
| Footer | Yes (structure) | Dense link columns; consistent across public |
| Explore / experts | Partial (routes + home experts strip) | Same marketing system |
| Tools catalogue | Yes | Tokens `#316d61`; Issue 07 mobile compaction |
| Career Assessment result/history | Via prior Issues 03–05 + CIT CSS | Teal `#316d61` tool family |
| Customer login/register | Token file + prior Issue 01 | Auth teal `#316D61` |
| Professional login/register | Token file | Aligns with auth / `#316D61` theme-color meta |
| Customer dashboard | Yes (browser) | `#36877F` panel system |
| Professional dashboard | Token file | `#36877F` |

---

## 4. Screenshots and evidence

Captured via Cursor browser automation (not committed as binary assets):

1. **Home @ 375×812** — hero “Clarity today / A brighter tomorrow” with lavender second line; DM Sans; large right-side blank before media.  
2. **Customer dashboard @ ~mobile** — `#36877F` welcome eyebrow; navy name; white rounded welcome card.  
3. **Tools @ 375** (Issue 07 session) — catalogue cards compacted; still on `#316d61` tool tokens.

**CSS / Blade anchors:**

- `resources/css/tazen.css` — `#36877F` brand scale  
- `resources/css/auth-theme.css` — `#316D61`  
- `resources/views/frontend/home/partials/hero.blade.php` — `#316D61` CTAs, `#9D81DB` headline  
- `public/frontend/assets/css/yuktione-tools-page.css` — `--yk-teal: #316d61`  
- `resources/css/customer-panel.css` — `--yk-primary: #36877F`

---

## 5. Three proposed design options (not implemented)

### Option A — Minimal Polish

**Intent:** Keep the current look; fix consistency and spacing only.

| Area | Proposal |
|------|----------|
| Colour | Pick **one** primary teal for product UI (recommend aligning auth/tools/home CTAs to **`#36877F`** *or* document `#316D61` as the sole primary — stakeholder chooses). Keep navy `#142B4D`. Limit lavender to soft backgrounds, not H1 accents, **if** stakeholders want stronger teal identity. |
| Typography | Keep DM Sans; normalize H1/H2 sizes across home sections; fix `Yuktione` → `YuktiOne` strings. |
| Cards | No new card style; only align padding/radius where already similar. |
| Buttons | Shared primary/secondary recipes (fill teal / outline navy) without new shapes. |
| Mobile | Continue Issue 07-style compaction; tighten hero vertical rhythm only. |
| Pages | Token/CSS variable consolidation; light Blade class cleanup on home CTAs. |
| A11y | Verify CTA text/icon contrast; focus rings already teal-based. |
| Scope | **Small** (1–2 sprints light). |
| Risk | **Low** if limited to tokens + spacing; still needs visual QA on auth + tools. |

### Option B — Modern Professional

**Intent:** Same brand, cleaner hierarchy and components.

| Area | Proposal |
|------|----------|
| Colour | Single teal + navy; reduce pastel card rainbow to **1–2 soft surfaces** (mint + white); lavender optional for rare highlights. |
| Typography | Stronger weight steps (700 titles / 400 body); clearer section eyebrows. |
| Cards | Unified white card + subtle border/shadow; fewer competing gradients. |
| Buttons | One primary pill/rect system site-wide; ghost secondary. |
| Mobile | Single-column marketing with aligned CTA groups; less decorative SVG noise. |
| Pages | Home, tools, explore, auth chrome, light panel header alignment. |
| A11y | Improved contrast on muted text (`#718096` on `#F7FAFB` check). |
| Scope | **Medium**. |
| Risk | **Medium** — touches many Blades/CSS files; regression on booking CTAs if selectors too broad. |

### Option C — Calm Wellness

**Intent:** Softer wellness mood while keeping YuktiOne teal/navy.

| Area | Proposal |
|------|----------|
| Colour | Softer mint washes, lower-contrast borders, restrained teal; careful not to wash out navy text. |
| Typography | Slightly more open line-height; quieter eyebrows. |
| Cards | Soft filled surfaces, larger radius, less shadow. |
| Buttons | Soft-fill primary; more secondary text links. |
| Mobile | More whitespace *by design* (may conflict with “too blank” unless content fills empty modules). |
| Pages | Home + tools + assessment shells. |
| A11y | Soft palettes often fail contrast — needs explicit WCAG checks. |
| Scope | **Medium–large** visually. |
| Risk | **Higher** perception risk vs stakeholder “not looking good” if more whitespace returns; also higher CSS surface area. |

---

## 6. Recommended option (for discussion — **not approved**)

**Recommend starting with Option A (Minimal Polish),** optionally followed by a bounded Option B pass on **home + tools only**.

**Reasons:**

1. Stakeholder asked to **discuss**, not ship a redesign.  
2. The strongest *technical* issue is **token drift (P1)**, which Option A fixes with lowest blast radius.  
3. Issues 01–07 already improved branding, recovery, assessment UX, and mobile card density — a large Option C could reintroduce blankness.  
4. Option B remains available once primary teal and lavender usage are decided.

**This document does not claim stakeholder approval.**

---

## 7. Scope and risk assessment (future work)

| Workstream | Risk to protected behaviour |
|------------|------------------------------|
| CSS variables / theme tokens | Low if class names unchanged |
| Home Blade colour classes | Low–medium (CTA visibility) |
| Global `button` / `a` restyles | **High** — can break booking/payment/dashboards |
| Panel CSS (`customer-panel` / `professional-panel`) | Medium — navigation & forms |
| Assessment tool CSS | Medium — must not alter form logic |
| Auth pages | Low if layout-only; do not touch Issue 01 copy/mail |

**Must preserve:** Customer/Professional auth & intended URLs, dashboards, booking/payment, assessment scoring/history, tool statuses, professional approval gates, Nutrition/Programs **not** re-enabled, Issues 01–07 fixes.

---

## 8. Accessibility considerations

- Confirm primary CTA contrast for both candidate teals (`#36877F` and `#316D61`) on white and on mint chips.  
- Re-check muted body `#718096` / `#5b6b7c` on `#F7FAFB` / `#f7f9fc`.  
- Keep visible focus rings (already brand-coloured).  
- Soften Option C only with contrast budgets.  
- Do not rely on colour alone for tool “Coming Soon” vs “Available” (badges already textual — keep).

---

## 9. Stakeholder decisions required

1. **Canonical primary teal:** `#36877F` (tazen/panels) **or** `#316D61` (auth/tools/home)?  
2. **Lavender in headlines:** keep as accent, demote to backgrounds only, or remove from H1?  
3. **Which option:** A, B, C, or A then B?  
4. **Home empty modules:** design polish only vs content fill (testimonials/blogs/FAQs)?  
5. **Panel vs marketing unification:** same buttons/cards everywhere, or accept two densities?  
6. Explicit **approval before any CSS/Blade implementation**.

---

## 10. Suggested phased implementation plan (after approval)

| Phase | After approval of… | Work | Exit criteria |
|-------|--------------------|------|---------------|
| 0 | Discussion | No code (this audit) | Decisions logged in checklist |
| 1 | Option A + teal choice | Single primary token wiring; spelling `YuktiOne`; light spacing | Visual QA 375/768/1440; auth/tools/home smoke |
| 2 | Optional Option B (home+tools) | Card/button unification on marketing surfaces only | No booking/auth regressions |
| 3 | Optional panels | Align panel chrome to same primary only | Dashboard + professional smoke |
| 4 | Option C | Only if explicitly chosen | Full contrast audit |

---

## Tests executed during this audit (read-only)

```bash
php artisan test --filter="ToolsPageTest|PasswordResetBrandingTest"
```

**7 passed** — confirms no code changes were required for this issue; Issue 01 branding and tools page still green.

---

## Verdict

**Issue 08 complete as a design review only.**  
Main concrete finding: **two primary teals and competing lavender accents** fragment the brand across home/tools/auth vs dashboards. Propose **Option A** first after stakeholder colour decisions; **do not implement** until the approval checklist is signed.
