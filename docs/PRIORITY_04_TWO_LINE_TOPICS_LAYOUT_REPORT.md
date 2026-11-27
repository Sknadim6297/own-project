# Priority 04 — Two-Line Topics Layout Report

**Project:** YuktiOne Laravel Website  
**Date:** 2026-11-27  
**Scope:** Interpret and prepare reversible “divide topics in two lines” layouts without forcing an unconfirmed global change  
**Commit / push / deploy:** Not performed (awaiting approval)

---

## 1. Original client feedback

- “not able to view tools”
- “scroller not there…divide topics in two lines”

Scrolling (“not able to view tools” / “scroller not there”) was addressed in **Priority 3**.  
This priority isolates the remaining phrase: **“divide topics in two lines.”**

---

## 2. Previous Task 55 implementation

Task 55 intentionally **reduced** forced two-line wraps:

- Removed artificial `max-width: 12ch/14ch/16ch` caps that caused wrapping.
- Prefer `white-space: nowrap` on Solutions labels from tablet up.
- Phone already allows `white-space: normal`.
- Tools tiles already use `-webkit-line-clamp: 2` with wider single-line-friendly widths.

**Conflict:** Task 55’s “keep names on one line when possible” conflicts with a literal reading of “divide topics in two lines” if that means wrapping labels. It does **not** conflict with a two-**row** card layout.

---

## 3. Interpretation of “two-line” (ambiguous — needs client confirmation)

| Interpretation | Meaning | Fit with feedback |
|----------------|---------|-------------------|
| **A — Single-row (current)** | One horizontal rail; Task 55 single-line preference on tablet+ | Status quo after P3 |
| **B — Two-line labels** | Same single-row rail; topic names wrap up to 2 lines | Matches “two lines” as **text wrapping** |
| **C — Two-row cards (proposed)** | Two rows of cards; both rows scroll together horizontally | Matches “two lines” as **two rows of topics**; also helps “view more topics without only scrolling sideways” |

**Recommended proposed interpretation for engineering preview:** **C (two-row cards)**, because:

1. Priority 3 already fixed scroller discoverability.
2. Showing more topics at once (two rows) better addresses “not able to view tools/topics” alongside a scroller.
3. Task 55 already fought accidental label wrapping; flipping default to wrap alone may reintroduce the old “ch-cap” look.

**However:** the feedback is ambiguous. **Do not treat C as confirmed.** Default production mode remains **A (single-row)**.

---

## 4. Proposed layout alternatives (reversible)

All modes are opt-in via:

- Config: `YUKTIONE_TOPIC_LAYOUT` / `config('yuktione.topic_layout')`
- Preview query (no DB change): `/?topic_layout=single-row|label-wrap|two-row`

| Mode | Modifier class | Default? |
|------|----------------|----------|
| `single-row` | *(none)* | **Yes** |
| `label-wrap` | `yk-home-cats--label-wrap` / `yk-tools-strip--label-wrap` | No |
| `two-row` | `yk-home-cats--two-row` / `yk-tools-strip--two-row` | No |

Two-row uses `display: grid; grid-auto-flow: column; grid-template-rows: repeat(2, …)` so **both rows share one horizontal scroller**. Priority 3 `bindHomeCarousel` (arrows, keyboard, disabled states, hide when no overflow) remains intact.

---

## 5. Before / after screenshots (browser-emulated)

Stored in `docs/priority04-screenshots/`:

| File | Layout |
|------|--------|
| `priority04-mobile-A-single-row.png` | A — current single-row |
| `priority04-mobile-B-label-wrap.png` | B — two-line labels |
| `priority04-mobile-C-two-row.png` | C — two-row cards (mobile) |
| `priority04-desktop-C-two-row.png` | C — two-row cards (desktop) |

**CDP evidence (390×844, `two-row`):** track `display: grid`, two rows occupied — e.g. Mental Wellness (col0 row0) above Career Counselling (col0 row1); Nutrition/Fitness in next column. `is-carousel-scrollable: true`, no page-level X overflow.

**Note:** Screenshots are **browser-emulated**, not physical-device captures.

---

## 6. Dynamic content

- Categories remain DB-driven (`CategoryCatalog::forHomepage()`).
- No hardcoded category names/counts in the layout switch.
- Odd counts leave the last column with one card (grid places it on the first row).
- Long names: two-row + label clamp to 2 lines; label-wrap mode clamps to 2 lines.
- Missing images: existing Blade fallbacks unchanged.
- Inactive / order / new categories: unchanged Admin/P2 behaviour.

---

## 7. Scrolling behaviour (Priority 3 preserved)

Verified with two-row active:

- Horizontal overflow still present when content exceeds viewport
- Prev disabled at start; Next enabled when scrollable
- Keyboard handlers unchanged
- Controls hide via `:not(.is-carousel-scrollable)` rules from P3

---

## 8. Modified files

| File | Change |
|------|--------|
| `app/Support/Homepage/TopicLayout.php` | Resolver + class helpers |
| `config/yuktione.php` | `topic_layout` config (default `single-row`) |
| `.env.example` | Documented `YUKTIONE_TOPIC_LAYOUT` |
| `app/Http/Controllers/Frontend/HomeController.php` | Resolve layout; pass `$topicLayout` |
| `resources/views/frontend/home/partials/categories.blade.php` | Opt-in modifier classes |
| `resources/views/frontend/home/partials/tools.blade.php` | Opt-in modifier classes |
| `public/frontend/assets/css/yuktione-categories.css` | Scoped `--label-wrap` / `--two-row` |
| `public/frontend/assets/css/yuktione-tools.css` | Scoped `--label-wrap` / `--two-row` |
| `tests/Feature/Priority04TopicLayoutTest.php` | New contracts |
| `docs/PRIORITY_04_TWO_LINE_TOPICS_LAYOUT_REPORT.md` | This report |
| `docs/priority04-screenshots/*` | Emulated captures |

---

## 9. Responsive verification

Emulated widths checked with live CSS + CDP on 390 and 1280 for two-row:

| Width | A single-row | B label-wrap | C two-row |
|------:|:------------:|:------------:|:---------:|
| 360–430 | rail + arrows | clamp 2 | 2-row grid + scroll |
| 768 | same | same | same |
| 1024–1440 | 6-across sizing | same | ~4 columns × 2 rows |

Record counts (0/1/3/9+) remain dynamic from DB; strip tools still use spotlight (12), catalogue remains 23 on `/tools` (grid, not forced into homepage two-row).

---

## 10. Automated test results

```
php artisan test --filter="Priority04TopicLayoutTest|TopicLabelFormattingTest|Priority03ScrollerTest|SolutionsHorizontalScrollTest|ToolsScrollerTest|Priority02CategoryServiceCatalogTest|AccountRecoveryTest|NineToolsCategoriesTest|ToolsVisibilityTest"

Tests: 2 deprecated, 59 passed (531 assertions)
```

Task 55 default `nowrap` contracts remain for the non-opt-in rich rail.

---

## 11. Remaining client confirmation (blocker for global enable)

Please confirm **one** of:

1. Keep **A** (single-row) as permanent default  
2. Enable **B** (`label-wrap`) globally  
3. Enable **C** (`two-row`) globally — **engineering recommendation for preview**

Until then, production default stays **`single-row`**. Preview:  
`http://127.0.0.1:8001/?topic_layout=two-row`  
`http://127.0.0.1:8001/?topic_layout=label-wrap`

---

## 12. Production deployment requirements

1. Deploy CSS/JS with existing `filemtime` cache-busting.  
2. Leave `YUKTIONE_TOPIC_LAYOUT=single-row` (or unset) until client chooses.  
3. After client chooses B or C, set env and `php artisan config:clear` (or rebuild config cache).  
4. Smoke-check homepage Solutions + Tools arrows under the chosen mode.  
5. No Priority 4 migrations; no destructive DB operations.  
6. Do not enable globally without explicit client approval.

---

## 13. Summary

Priority 4 delivers **documented ambiguity**, **three reversible layouts**, **screenshots**, and **tests**, while **keeping the live default as Task 55 / Priority 3 single-row**. Two-row is implemented and previewable as the proposed reading of “divide topics in two lines,” pending client confirmation. Priority 5 was not started; no commit/push/deploy.
