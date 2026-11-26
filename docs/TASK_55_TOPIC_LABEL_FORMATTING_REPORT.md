# Task 55 — Topics Text Wrapping and Alignment Fix Report

**Status:** Implemented and verified locally (2026-11-26)  
**Scope:** Topic label formatting on homepage Solutions (Explore by Need) and supporting Tools strip label width. Preserve Tasks 52–54 and `directoryStats`. No Task 56. No commit/push/deploy.

---

## 1. Implementation status

**Done.** Unnecessary two-line topic wrapping and uneven Solutions card heights are corrected. Long topic names stay full and readable. Tasks 52–54 contracts remain green. `/professionals` returns HTTP 200.

---

## 2. Exact affected page and component

| Item | Value |
|---|---|
| Primary page | Homepage `/` |
| Primary component | Explore by Need Solutions rail |
| Blade | `resources/views/frontend/home/partials/categories.blade.php` |
| Marker | `data-yk-solutions-rail` |
| Label class | `.yk-home-cats__label` |
| Blurb class | `.yk-home-cats__blurb` |
| CSS | `public/frontend/assets/css/yuktione-categories.css` |
| Secondary | Homepage Tools strip labels (`.yk-tool-tile__label`) in `yuktione-tools.css` |

Topic chips / catalogue pills already used `white-space: nowrap` and were not the defect.

---

## 3. Identified root cause

1. **Artificial `max-width: 12ch` / `14ch` on topic labels** — forced wraps for long names (especially under zoom / tighter cards).
2. **Artificial `max-width: 16ch` on blurbs** — forced two-line taglines on several cards.
3. **No equal-height stretch** — cards with 2-line blurbs grew taller than neighbours (desktop heights ~269px vs ~254px).
4. **Tools strip (secondary)** — desktop override kept tiles at **142px**, so longer tool names wrapped even when space could be given.

---

## 4. Previous topic formatting behaviour

**Desktop 1440 (before):**

| Metric | Value |
|---|---|
| Label lines | 1 at 100% zoom; **5 labels → 2 lines at 150% zoom** (Mental Wellbeing, Astrology & Tarot, Health & Wellness, Lifestyle & More, Nutrition & Fitness) |
| Blurb lines | 2 for Calm mind… / Clarity for work… / Grow money… |
| Card heights | Uneven: **269.2** and **253.7** |
| Label max-width | `14ch` (~148px) |

**Tools (before):** 6 of 12 names on 2 lines; `min-height: 2.5em` reserved two-line space for every tile.

---

## 5. Implemented formatting fix

**Solutions (`yuktione-categories.css`)**

- Removed `12ch` / `14ch` / `16ch` caps; labels/blurbs use `width/max-width: 100%`.
- Prefer single-line topic names from **768px+** via scoped `white-space: nowrap` on `.yk-home-cats--rich …__label` (not global).
- Desktop blurbs (`≥1024px`) single-line; tablet keeps reserved 2-line min-height and may wrap gracefully.
- Phone (`≤1023.98px`): `white-space: normal` so long names / enlarged text can wrap instead of clipping.
- Track items `align-items: stretch` + flex cards for equal heights.
- Kept Task 52 reachable cards (no `nth-child` hide).

**Tools (`yuktione-tools.css`)**

- Desktop tiles **168px** (was 142px override).
- Slightly reduced inline padding.
- Label `min-height` reduced to one line; `-webkit-line-clamp: 2` remains as overflow safety.

Topic names, URLs, mappings, and branding tokens unchanged.

---

## 6. Long topic name handling

Full catalogue names preserved (`CategoryHub::DISPLAY_LABELS`), including:

- Astrology & Tarot  
- Nutrition & Fitness  
- Health & Wellness  
- Lifestyle & More  
- Mental Wellbeing  

No renaming, abbreviation, or ellipsis. Phone may wrap; tablet/desktop prefer one line.

---

## 7. Before / after card dimensions (1440)

| State | Card height(s) | Card width |
|---|---|---|
| Before | 269.2 / 253.7 (uneven) | ~196.9 |
| After | **254.4** (uniform) | ~196.9 |

Tools tile width: **142 → 168** (desktop).

---

## 8. Before / after text line counts (1440)

| Surface | Before | After |
|---|---|---|
| Solutions labels @100% | 0 two-line | 0 two-line |
| Solutions labels @150% zoom | **5** two-line | **0** two-line |
| Solutions blurbs | 3 two-line | **0** two-line |
| Tools labels | 6 two-line | **0** two-line |

DOM proof after fix: `white-space: nowrap`, `rectCount: 1`, label height ≈ 18.8px.

---

## 9. Content and destination compatibility

- 11 Solutions cards; same `categories.show` destinations.
- 12 Tools spotlight cards; View All Tools → `/tools`.
- Display labels unchanged.

---

## 10. Modified files

| File | Change |
|---|---|
| `public/frontend/assets/css/yuktione-categories.css` | Label/blurb width, scoped nowrap, equal heights |
| `public/frontend/assets/css/yuktione-tools.css` | Wider desktop tiles, label min-height |
| `tests/Feature/TopicLabelFormattingTest.php` | **New** |
| `docs/TASK_55_TOPIC_LABEL_FORMATTING_REPORT.md` | This report |
| `docs/audits/task-55-topic-label-formatting/*` | Screenshots |

---

## 11. Database changes

None.

---

## 12. Automated test results

```
php artisan test --filter="TopicLabelFormattingTest|SolutionsHorizontalScrollTest|ToolsVisibilityTest|ToolsScrollerTest|ToolsLayoutConsistencyTest"
→ 26 passed (352 assertions)
```

| Suite | Result |
|---|---|
| TopicLabelFormattingTest | 5/5 |
| SolutionsHorizontalScrollTest | 5/5 |
| ToolsVisibilityTest | 6/6 |
| ToolsScrollerTest | 5/5 |
| ToolsLayoutConsistencyTest | 5/5 |

Documented pre-existing unrelated failures (not run/weakened here): `ToolsCompactHeaderTest` icon assertion; legacy catalogue chip markup tests.

---

## 13. Actual browser verification (`http://127.0.0.1:8001/`)

Verified:

1. Topic labels single-line on desktop Solutions rail  
2. Unnecessary two-line wrapping removed (including 150% zoom)  
3. Long names fully visible  
4. Equal card heights  
5. Icons/media unchanged  
6. Category links intact  
7. No clipping / overlap  
8. No page `overflow-x`  
9. Solutions end card reachable (`solutionsEndOk`)  
10. Tools end reachable; all 12 tool names single-line at 168px  
11. `/professionals` → **200**  
12. `directoryStats` + `browse(..., $withFacets)` signature intact  

---

## 14. Responsive verification (browser emulation)

Checked via metrics / CSS contracts at 390 and 1440; CSS breakpoints cover 360–1440. Physical-device confirmation still recommended.

| Viewport | Solutions labels | Card heights |
|---|---|---|
| 390 | 1 line (`white-space: normal`) | uniform 159.4 |
| 1440 | 1 line (`nowrap`) | uniform 254.4 |

---

## 15. Accessibility

- Zoom 150%: Solutions labels remain one line after fix.  
- Phone allows graceful wrap instead of clipping.  
- Focus/hover card styles unchanged.  
- No global `nowrap` on body text.

---

## 16. Task 52 compatibility

`data-yk-solutions-rail`, 11 cards, prev/next, native scroll, last card reachable — preserved. `SolutionsHorizontalScrollTest` passed.

---

## 17. Task 53 compatibility

`forExploreCategory` / hub counts unchanged. `ToolsVisibilityTest` passed.

---

## 18. Task 54 compatibility

`data-yk-tools-rail`, thin scrollbar, mobile nav, proximity snap, `bindHomeCarousel` preserved. `ToolsScrollerTest` passed.

---

## 19. Experts page regression

`/professionals` HTTP **200** on local app. `directoryStats()` and optional `$withFacets` still present.

---

## 20. Screenshots

Under `docs/audits/task-55-topic-label-formatting/`:

1. `01-before-zoom150-wrapping.png` — before capture at enlarged zoom  
2. `02-after-desktop-solutions-labels.png` — desktop Solutions labels  
3. `03-after-desktop-tools.png` — desktop tools / solutions strip region  
4. `04-after-mobile-solutions.png` — mobile Solutions labels  

---

## 21. Remaining limitations

- Extremely narrow phones + very large accessibility text may still wrap topic names (intentional).  
- Category hub grids reuse shared label classes; desktop hub cards already had more width.  
- Browser emulation ≠ physical device.  
- Task 56 (BMI banner) not started.

---

## 22. Final completion status — Tasks 52–55

| Task | Status |
|---|---|
| 52 Solutions horizontal scroll | Complete |
| 53 Tools visibility mappings | Complete |
| 54 Tools scroller | Complete |
| 55 Topic label formatting | **Complete** |
| 56 BMI banner | Not started |
