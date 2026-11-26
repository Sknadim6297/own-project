# Task 53 — Tools Visibility Report

**Status:** Implemented and verified locally  
**Scope:** Restore correct Tools visibility on Explore / All Categories hub pages. No Task 54/55/56. No calculator changes. No commit/push/deploy.

---

## 1. Implementation status

**Done.** Category hub Tools panels now show the tools mapped to each Explore category via `TOOL_PLACEMENT` hub keys. Previously, hub slugs were incorrectly remapped through tools-catalogue aliases, which hid tools (Astrology & Tarot) or flooded categories with the wrong catalogue (Parenting, Mental Wellbeing, Nutrition & Fitness).

## 2. Affected page and component

| Surface | Path / component |
|---|---|
| Primary | `/categories/{slug}` Tools tab — `category-show.blade.php` |
| Service | `CategoryHub::show()` → `ToolsCatalog::forExploreCategory()` |
| Related | All Categories hub counts (`hubCategoryCounts`) — already correct; detail panel now matches |

Homepage “Try Our Tools” strip and `/tools` nine-category catalogue were **not** the broken surface (preview limit of 12 + View All Tools preserved).

## 3. Existing architecture

- **23** launchable catalogue tools; **nine** tools-catalogue categories (Task 11).
- Each tool has `tools` + `hub` placement in `ToolsCatalog::TOOL_PLACEMENT`.
- Explore / All Categories uses **11** hub slugs (`ExploreCatalog::CATEGORIES`).
- `/tools?category=` still uses `CATEGORY_ALIASES` / `CATEGORY_GROUP_ALIASES` for bookmarks.
- Hub detail Blade renders the same `yk-tool-tile` cards as `/tools` and the homepage strip.

## 4. Root cause

`ToolsCatalog::forExploreCategory()` called `normalizeCategoryKey()` **before** checking Explore hub keys.

That remapped:

| Hub slug | Alias remapped to | Before (wrong) | Intended hub tools |
|---|---|---|---|
| `astrology-tarot` | first of group → `astrology` | **0 tools** (empty “No tools yet”) | 1 — Tarot Reflection |
| `parenting` | `mental-wellness` | **8** Mental Wellness tools | 3 parenting tools |
| `mental-health` | `mental-wellness` | **8** tools | 3 mental-health hub tools |
| `nutrition-fitness` | first of group → `nutrition` | **2** nutrition-only | 4 nutrition+fitness hub tools |
| `spirituality` | `mental-wellness` | **8** wrong tools | 2 spirituality tools |

Tools were **missing from rendered HTML** on Astrology & Tarot (not merely CSS-hidden). Other hubs rendered HTML for the **wrong** tool set, so intended tools were undiscoverable in context.

## 5. Tools visibility fix

Updated `forExploreCategory()` to:

1. Match exact tools-catalogue keys by `category`.
2. Match Explore hub keys by `hub_category` **without** alias remapping.
3. Apply group/legacy aliases only for non-hub leftover keys.

No CSS redesign, no new tools, no URL/auth/placement changes.

## 6. Intended / rendered / accessible card counts

### Category hubs (before → after)

| Hub | Intended | Before rendered | After rendered / accessible |
|---|---:|---:|---:|
| Astrology & Tarot | 1 | 0 | 1 |
| Parenting | 3 | 8 (wrong set) | 3 |
| Mental Wellbeing | 3 | 8 (wrong set) | 3 |
| Spirituality | 2 | 8 (wrong set) | 2 |
| Nutrition & Fitness | 4 | 2 | 4 |
| Career | 2 | 2 | 2 |
| Finance | 8 | 8 | 8 |
| Relationships / Lifestyle / Creativity / Health & Wellness | 0 | 0 or wrong | 0 (truthful empty) |

### Catalogue / homepage (unchanged intent)

| Surface | Intended | Rendered | Notes |
|---|---:|---:|---|
| `/tools` catalogue | 23 | 12/page + pager | Preview pagination preserved |
| Homepage strip | ≤12 spotlight | 12 | View All Tools → `/tools` |

## 7. Catalogue compatibility

- Nine client categories unchanged.
- 23 `TOOL_PLACEMENT` entries unchanged.
- Empty tools-catalogue categories (Astrology, Business Consulting, Tax Matters) remain empty on `/tools`.
- Calorie tracker remains in placement; unfinished exposure rules unchanged.

## 8. Route compatibility

- Tool tile hrefs unchanged (e.g. `/tools/tarot`, `/tools/ps/stress-check`).
- Hub routes `/categories/{explore-slug}` unchanged.
- `/tools?category=parenting` alias behaviour for the **tools index** unchanged (still expands to Mental Wellness catalogue filter).

## 9. Authentication compatibility

No auth gates changed. Guest and authenticated users see the same public hub tool lists.

## 10. Modified files

| File | Change |
|---|---|
| `app/Services/ToolsCatalog.php` | Fix `forExploreCategory()` hub vs alias resolution |
| `tests/Feature/ToolsVisibilityTest.php` | **New** Task 53 coverage |
| `docs/TASK_53_TOOLS_VISIBILITY_REPORT.md` | This report |
| `docs/audits/task-53-tools-visibility/*` | Before/after screenshots |

## 11. Database changes

None.

## 12. Automated test results

```
php artisan test --filter=ToolsVisibilityTest
→ 6 passed (84 assertions)

php artisan test --filter=SolutionsHorizontalScrollTest
→ 5 passed (66 assertions)

php artisan test --filter=ToolsLayoutConsistencyTest
→ passed
```

**Pre-existing unrelated failures (not weakened):**

| Suite | Issue |
|---|---|
| `ToolsCompactHeaderTest` | Icon assertion `data-yk-icon="calculator"` (previously reported) |
| `ToolsCatalogueTest` | Expects homepage “Try Tools” copy on `/tools` |
| `NineToolsCategoriesTest` | Expects legacy `yk-tools__cats` / `(0)` chip markup |

## 13. Browser verification (local)

Emulation host: Cursor browser → `http://127.0.0.1:8001`.

| Check | Result |
|---|---|
| Astrology & Tarot before | Tools **0**, “No tools yet” |
| Astrology & Tarot after | Tools **1**, Tarot Reflection tile + `/tools/tarot` |
| Parenting before | Tools **8** including Mood/Journal |
| Parenting after | Tools **3** parenting-only; routes `/tools/ps/...` |
| Nutrition & Fitness after | Tools **4** (BMI, Water, Weight, Calorie) |
| Tool navigation | Parenting Stress Check opens correctly |
| `/tools` | Still shows 23 with pagination |
| Page overflow-x @ 1440 Parenting | None |

## 14. Responsive verification

Browser **emulation** (not physical devices):

| Width | Check |
|---:|---|
| 390 | Nutrition & Fitness — Tools 4, tiles present (mobile chrome) |
| 1440 | Parenting — 3 tiles, height 148px each, no horizontal page overflow |
| 360 / 430 / 768 / 1024 / 1280 | Same Blade/CSS grid; no responsive rules hide hub tiles |

Dedicated homepage strip scroller UI remains **Task 54**.

## 15. Accessibility

- Tools tab count matches visible tiles.
- Tool names exposed as link names.
- Empty hubs keep “No tools yet” + Browse all tools.
- Focusable `yk-tool-tile` links unchanged.

## 16. Task 52 regression

- `SolutionsHorizontalScrollTest` — **5 passed**.
- Homepage still has `data-yk-solutions-rail`, 11 category cards, shared `bindHomeCarousel` untouched for this task.

## 17. Screenshots

Saved under `docs/audits/task-53-tools-visibility/`:

1. `01-before-astrology-tarot-empty.png` — Tools 0 / empty state  
2. `02-before-parenting-wrong-tools.png` — Tools 8 wrong set  
3. `03-after-nutrition-fitness-4-tools-mobile.png` — Tools 4 @ ~390  
4. `04-after-astrology-tarot-desktop.png` — Tools 1 restored  
5. `05-after-parenting-3-tools-desktop.png` — Tools 3 correct  
6. `06-after-tool-navigation-stress-check.png` — destination tool page  

## 18. Remaining limitations

- Homepage Tools strip still hides native scrollbar / mobile arrows — **Task 54**.
- `/tools` remains paginated at 12/page (intentional).
- Empty hub categories stay empty (no invented tools).
- Screenshot description tooling sometimes crops above tile labels; CDP + a11y tree confirm cards and names.

## 19. Unrelated components

Not modified: financial calculators, Task 52 Solutions CSS/JS behaviour beyond shared helper already shipped, tool workflows, DB records, Task 54–56 scope.
