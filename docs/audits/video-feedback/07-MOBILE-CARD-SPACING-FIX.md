# Video Issue 07 — Mobile Card Size & Excessive Blank Space

**Date:** 2026-11-07  
**Project root:** `C:\xampp\htdocs\own-project`  
**Laravel:** 10.49.1  
**Scope:** Mobile-only compaction of Tools catalogue cards, home Tools strip, and Career Interest page chrome  
**Out of scope:** Issue 08 layout/colour redesign, desktop redesign, auth, scoring, booking/payment/legal

---

## 1. Root causes

| Cause | Where | Effect on phones |
|-------|--------|------------------|
| `.yk-tool-card__body { min-height: 11.5rem }` | `yuktione-tools-page.css` | Forced ~184px body even when copy is short → tall empty card |
| `.yk-tool-card__desc { min-height: 2.25em }` | same | Extra reserved description space |
| Media band `aspect-ratio: 16/9` + `max-height: 7.5rem` | same | Tall icon header on single-column cards |
| Home `.yk-tools__art { height: 128px }` / 112px | `yuktione-tools.css` | Oversized illustration area in home tool cards |
| Career `.yk-cit { min-height: 70vh }` + generous card padding | `yuktione-career-interest.css` | Large empty vertical field under short welcome content |

Primary stakeholder pain was **Tools cards looking “too blank”** on mobile; Career and home Tools shared the same spacing pattern.

---

## 2. Affected pages and components

| Page | Component |
|------|-----------|
| `/tools` (`tools.index`) | `.yk-tool-card` media + body + intro/chip spacing |
| Home Tools strip | `.yk-tools__card` / `.yk-tools__art` |
| `/tools/career-interest` (+ other CIT pages using same CSS) | `.yk-cit` / `.yk-cit__card` mobile padding & min-height |

Daily Reflection / Mood tool **internals** not redesigned (only catalogue card for Mood on `/tools`).

---

## 3. Files modified

- `public/frontend/assets/css/yuktione-tools-page.css`
- `public/frontend/assets/css/yuktione-tools.css`
- `public/frontend/assets/css/yuktione-career-interest.css`

No Blade, PHP, migrations, or theme token changes.

---

## 4. CSS changes (summary)

**Tools catalogue (`max-width: 639px`):**

- Remove body/desc forced min-heights (`min-height: 0`).
- Shorter media (`max-height: 4.5rem`, wider aspect).
- Tighter intro, chips, toolbar, card padding; CTA `min-height: 40px` (still tappable).

**Home Tools (`max-width: 767px`):**

- Art ~64–72px (was 112–128px).
- Reduced card padding and section padding; 2-column grid kept from 360px.

**Career Interest (`max-width: 720px`):**

- `min-height: 0` instead of `70vh`.
- Slightly tighter wrap width, card padding, hero icon, stats gaps.
- Issue 05 stacked actions preserved.

Tablet/desktop (≥640 / ≥768) keep original catalogue card min-heights.

---

## 5. Before / after (browser metrics)

App: `http://127.0.0.1:8000/tools` via live browser automation.

| Viewport | Metric | Before | After |
|----------|--------|--------|-------|
| **375 × 812** | Tool card height | **306px** | **235px** (~23% shorter) |
| 375 | Media height | 120px | 72px |
| 375 | Body `min-height` | 184px (11.5rem) | **0px** |
| 375 | Horizontal overflow | none | none |
| **320 × 700** | Card height | — | 235px, no overflow |
| **390 × 844** | Card height | — | 235px, no overflow |
| **768 × 1024** | Body min-height | — | **184px preserved** (desktop rules intact) |
| Career 375 | `.yk-cit` min-height | 70vh | **0**; Start + View History present |

Screenshots captured in-session (header + scrolled card). Absolute image files are not checked into the repo; metrics above are the verification record.

---

## 6. Viewport test results

| Check | 320 | 375 | 390 | 768 | 1440* |
|-------|-----|-----|-----|-----|-------|
| Cards more compact (mobile) | ✓ | ✓ | ✓ | N/A (unchanged) | N/A |
| No text clipping | ✓ | ✓ | ✓ | ✓ | — |
| No overlap / overflow | ✓ | ✓ | ✓ | ✓ | — |
| Buttons present | ✓ | ✓ | ✓ | ✓ | — |
| Career actions readable | ✓ @375 | ✓ | — | — | — |

\*Desktop CDP width override remains flaky on this host; 768 confirmed catalogue desktop rules unchanged.

---

## 7. Automated test results

```bash
php artisan test --filter="ToolsPageTest|ToolsCatalogueTest|CareerInterestTest|Phase12ToolsConsistencyTest"
```

**20 passed** (212 assertions).

Confirmed indirectly:

- Tool catalogue / search / categories still render.
- Career assessment flows + history access unchanged.
- Phase12 tools consistency (incl. nutrition catalogue pending behaviour) still green.
- Calorie tracker not listed among the 8 live tools on `/tools` in this QA snapshot (Coming Soon / unpublished remains out of the active grid).

---

## 8. Remaining issues

- Site-wide header/footer whitespace not in scope.
- Mood/Journal/Gratitude **in-tool** screens may still feel airy; only catalogue + CIT chrome were targeted.
- Issue 08 (overall layout/colour) not started.

---

## Verdict

**Issue 07 complete.** Mobile Tools cards are ~23% shorter by removing forced body min-height and tightening media/padding; home Tools art and Career Interest mobile chrome compacted. Desktop/tablet catalogue layout preserved.
