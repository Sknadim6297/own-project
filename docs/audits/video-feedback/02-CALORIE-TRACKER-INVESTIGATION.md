# Video Issue 02 — Calorie / Nutrition Tracker Investigation

**Date:** 2026-11-07  
**Project root:** `C:\xampp\htdocs\own-project`  
**Mode:** **READ-ONLY** (no code, DB, migration, seeder, or design changes)  
**QA database:** `yuktione_fresh_qa`  
**App URL:** `http://127.0.0.1:8000`

**Label:** Investigation only — **no fix implemented**.

---

## Executive finding

Stakeholder line *“Calorie wala theek nahi hua”* is consistent with the **current QA product state**, not with a unique calculation or console bug found in this pass.

In QA today the Calorie & Nutrition Tracker is:

1. **Registry-gated as `coming_soon`** → `Tool::canLaunch()` is false.  
2. **Absent from the public Tools catalogue** (search `calorie` → 0 tools).  
3. **Direct URL** `/tools/nutrition` shows the **Coming soon / unavailable** closed page for guests (and would for customers too while closed).  
4. **No published food catalogue** (0 foods, 0 categories) and **no import workbooks** under `storage/app/nutrition/imports/`.  
5. Sibling nutrition tools (**BMI, Water, Weight**) are **`active`** on `/tools`, which can make the calorie tool feel “broken” or unfairly left behind.

The codebase already contains a substantial tracker + admin food pipeline. What is missing in QA is **launch status + verified food data**, not a blank feature folder.

Video feedback alone does **not** identify a single unique UI defect (wrong button, bad math, JS crash) beyond this blocked / unpublished / empty-catalogue condition.

---

## 1. Current feature status

| Item | QA evidence |
|------|-------------|
| Tool slug | `calorie-nutrition-tracker` |
| `status` | **`coming_soon`** |
| `is_implemented` | `1` (true) |
| `route_name` | `tools.nutrition` |
| `canLaunch()` | **false** (requires `status === 'active'`) |
| Publish migration `2026_11_05_140000_publish_calorie_nutrition_tracker` | **Present in `migrations` table** (intended to set `active`) |
| Actual row after that migration | Still **`coming_soon`** → **registry drift / incomplete publish** relative to migration intent |
| `nutrition_foods` | **0** total / **0** catalogue-visible |
| `nutrition_food_categories` | **0** |
| `nutrition_import_batches` | **0** |
| `nutrition_meal_entries` | **0** |
| Import workbooks on disk | Directory `storage/app/nutrition/imports/` **exists and is empty** (expected files: `YuktiOne_350_Indian_Food_Masterlist.xlsx`, `YuktiOne_700_Food_Database_Populated.xlsx`) |
| BMI / Water / Weight tools | **`active`** and listed on `/tools` |

**Classification of feature posture:** **Disabled / Coming Soon** at the registry gate, with **partial implementation** in code (routes, services, admin, tests) and **blocked by verified data**.

---

## 2. What users currently see

### Tools catalogue (`GET /tools`)

- BMI, Water Intake, Weight Journey appear (nutrition-adjacent).  
- **No** “Calorie & Nutrition Tracker” card.  
- **No** “Food Catalogue Pending” / “View status” CTA (those only appear when the tool is `active` but catalogue is empty — see `ToolsCatalog`).  
- Search `?q=calorie` → **“Showing 0 tools”** / “No tools match these filters”.

### Direct tracker (`GET /tools/nutrition` and `/tools/nutrition/search`)

- HTTP **200**.  
- Title: **“Calorie & Nutrition Tracker unavailable | YuktiOne”**.  
- Badge/copy: **Coming soon**.  
- Body: meal logging opens after a reviewed food catalogue; tool remains unpublished.  
- CTA: **Back to Tools**.  
- Guest and authenticated customers are treated the same while `isOpen()` is false (closed view before login branching).

### Intended UX when tool is `active` but catalogue empty (code path, not current QA)

- Catalogue shows card with availability **Food Catalogue Pending**, CTA **View status**.  
- Tracker welcome/dashboard shows **Food Catalogue Pending** gate; no invented foods.

---

## 3. Reproduction results

| Check | Result |
|-------|--------|
| Tools catalogue nutrition/calorie card | **Not listed** (coming_soon) |
| Search tools for “calorie” | **0 results** |
| Direct `/tools/nutrition` | **Coming soon / unavailable** page |
| Direct `/tools/nutrition/search` | Same closed page |
| Guest behavior | Closed page; no logging |
| Logged-in Customer | **Not re-authenticated in browser this pass**; code path still hits `closed` while `canLaunch()` false — same UX expected |
| Mobile 375px closed page | Title/unavailable copy present; **no horizontal overflow** observed on tools sample viewport |
| Desktop 1440px | Same closed content via HTTP (no unique desktop defect found) |
| Console / network failures | No reproduction of a JS/network failure on the closed page; issue is product/registry/data state |
| Incorrect buttons/links | Closed page correctly links Back to Tools; no broken calorie CTA on catalogue because card is hidden |
| Advertised vs actual | Sibling nutrition tools live; calorie tool unpublished — **mismatch with stakeholder expectation** |

**Credentials:** No fake accounts created. No fake foods seeded.

---

## 4. Confirmed problems

1. **QA registry:** calorie tool remains **`coming_soon`** despite `is_implemented=1`, `route_name` set, and publish migration recorded.  
2. **Catalogue invisibility:** users cannot discover the tool on `/tools`; searching “calorie” fails.  
3. **Empty verified catalogue:** 0 published foods; logging cannot operate honestly.  
4. **Missing import workbooks** on disk → admin import pipeline cannot run without files.  
5. **Expectation gap:** BMI/Water/Weight are active while calorie is not → “calorie wala theek nahi hua” is a reasonable stakeholder reaction.  
6. **Migration vs row drift:** publish migration ran historically but status is not `active` now — needs ops/product explanation before any “just flip status” fix.

**Not confirmed in this audit:** broken portion math, wrong totals, ownership bugs, or JS crashes on a live logging UI (logging UI is not reachable while closed). Unit/Feature tests for calculator and tracker **pass** when foods are present in test DB.

---

## 5. Missing functionality (gap analysis)

| Component | Classification | Notes |
|-----------|----------------|-------|
| Tools registry row + routes | **Implemented**; QA **gated** | `coming_soon` blocks launch |
| Guest welcome / closed pages | **Implemented and verified** (closed path live) | |
| Food search UI + filters | **Implemented**; **blocked** in QA by closed gate + empty data | |
| Portion size / units calculator | **Implemented and verified** (unit tests) | Rejects invented unit equivalents |
| Daily meal logging (add) | **Implemented**; **blocked** by gate + data | Auth + ownership required |
| Daily calorie/macro totals | **Implemented** in service; **blocked** in QA | Snapshots on entries |
| History / edit / delete | **Implemented**; ownership **403** for other users (tested) | |
| Goals (optional macros) | **Implemented** | Defaults track-only |
| Insights / weekly trends | **Implemented** in code | Behind auth + open gate |
| Admin food CRUD / review | **Implemented** | Routes under admin nutrition-foods |
| Import workbooks + review batches | **Implemented**; **blocked** by missing files / zero batches in QA | |
| Verified food catalogue content | **Not present in QA** | **Blocked by verified data / business approval** |
| Fake/sample calorie data | **Correctly not invented** | Product rule preserved |

---

## 6. Data dependencies

Before a real public calorie tracker can work:

1. Approved Excel workbooks placed under `storage/app/nutrition/imports/` (names in `NutritionCatalog::IMPORT_SOURCES`).  
2. Import + admin review (verification_status, publish).  
3. At least one **catalogue-visible** food (`is_published` and not rejected).  
4. Explicit product decision to set tool **`status=active`** (and keep `route_name` / `is_implemented`).  
5. Clear config cache after env/ops changes if applicable.

Until (1)–(3) exist, even an `active` tool should show **Food Catalogue Pending**, not pretend logging works.

---

## 7. Security concerns (observed from code/tests; not newly exploited)

| Topic | Assessment |
|-------|------------|
| Guest POST meal entry | **403** when open+guest (Feature test) |
| Entry ownership | Other user’s entry update/delete **403** |
| Closed tool mutating routes | `requireOpenAuth()` → **403** if not open or not customer |
| Professional guard | Tracker uses **`auth:user`** only — professionals do not get customer meal history by design |
| Invented nutrition values | Calculator/tests reject unsupported units; UI states no invented values |

No new security defect required to explain the video complaint.

---

## 8. Recommended minimal next fix (for a future approved Issue — **not done now**)

**Minimal, honest path:**

1. **Product decision:** Keep calorie tool **Coming Soon** publicly **or** move to **`active` + Food Catalogue Pending** until foods exist.  
2. If activating: reconcile why publish migration left / reverted `coming_soon`; set status deliberately (admin/ops), do **not** invent foods.  
3. Obtain approved workbooks → import → review → publish a minimal verified set.  
4. Re-check `/tools` card (Pending → Available) and guest/customer logging smoke.  
5. Do **not** build a full new nutrition module; reuse existing tracker.

Anything larger (redesign, fake demo foods, enabling without catalogue) should be rejected under current rules.

---

## 9. Decisions required before implementation

| # | Decision owner | Question |
|---|----------------|----------|
| D1 | Product | Should calorie tracker stay **Coming Soon** until catalogue is ready, or show **active + Food Catalogue Pending**? |
| D2 | Product / ops | Why is status still `coming_soon` after publish migration was recorded? Accept Coming Soon or re-apply active intentionally? |
| D3 | Nutrition / legal content | Which workbook versions are approved for import? |
| D4 | Nutrition | What is the minimum verified food set for “real” logging? |
| D5 | Product | Confirm BMI/Water/Weight remaining active while calorie waits is intentional. |
| D6 | Eng (only after D1–D4) | Authorize a scoped Issue 02b fix ticket — no premature activation without data. |

---

## 10. Files inspected and tests executed

### Inspected (non-exhaustive but primary)

- `routes/web.php` (nutrition routes)  
- `routes/admin.php` (nutrition-foods / imports)  
- `app/Http/Controllers/Frontend/NutritionTrackerController.php`  
- `app/Services/NutritionTrackerService.php`  
- `app/Services/NutritionPortionCalculator.php`  
- `app/Services/ToolsCatalog.php`  
- `app/Services/ToolRegistryRegistrar.php`  
- `app/Support/Nutrition/NutritionCatalog.php`  
- `app/Models/Tool.php`, `NutritionFood.php` (+ related nutrition models)  
- `resources/views/frontend/sections/nutrition/*` (closed, welcome, dashboard, search, portion, meal, insights, goals, catalogue-gate)  
- `database/migrations/2026_11_03_220000_create_nutrition_tracker_tables.php`  
- `database/migrations/2026_11_05_140000_publish_calorie_nutrition_tracker.php`  
- `tests/Feature/NutritionTrackerTest.php`, `NutritionFoodImportTest.php`, `Phase12ToolsConsistencyTest.php`  
- `tests/Unit/NutritionPortionCalculatorTest.php`  
- QA DB queries on `yuktione_fresh_qa.tools` / `nutrition_*`  
- HTTP + browser probes: `/tools`, `/tools?q=calorie`, `/tools/nutrition`, `/tools/nutrition/search`

### Tests executed (read-only)

```text
php artisan test --filter="NutritionTrackerTest|NutritionPortionCalculatorTest|NutritionFoodImportTest|Phase12ToolsConsistencyTest::test_nutrition_catalogue_pending"
```

**Result: 18 passed (133 assertions).**

---

## Root cause statement (Task 4)

The complaint is best explained as:

1. **Feature still marked Coming Soon** in QA (primary user-visible failure), **and**  
2. **Missing verified nutrition data** (even the “active + pending catalogue” path cannot deliver real logging yet), **and**  
3. **Sibling nutrition tools already live**, amplifying the perception that calorie “wasn’t fixed.”

It is **not** evidenced (in this pass) as primarily:

- Incorrect calorie calculations on live entries,  
- A broken unique button on an open tracker UI, or  
- A console/network failure unique to calorie logging.

**Video feedback is insufficient to pin a single UI defect beyond this blocked / unpublished / empty-catalogue state.** Any future fix must start with product decisions D1–D5, not silent activation or fake foods.

---

## Stop

Investigation complete. **No implementation.** Await approval before any Issue 02 fix work.
