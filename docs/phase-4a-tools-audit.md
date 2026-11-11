# Phase 4A — Tools Audit & Premium Design System Foundation

Status: audit + design foundation only. No tool rebuilds. Reward engine remains disabled.

Inventory confirmed by [Tools audit](5ff4005b-628c-45db-82a4-9674870961f2).

## Cross-cutting

| Surface | Role |
|---|---|
| Catalogue | `tools.index` → `/tools` → `HomeController::tools` → `ToolsCatalog::browse` → `resources/views/frontend/sections/tools.blade.php` + `yuktione-tools-page.css` |
| Shared chrome | `yuktione-tool-chrome.css` on all `tools.*` — Back to Tools, expert-connect |
| Design system (4A) | `yuktione-tools-ds.css` on all `tools.*` — **opt-in** via `.yk-tools-ds` / `data-yk-tools-ds` (inert until a redesign adopts classes) |
| Rewards eligibility | 7 slugs in `config/yuktione_rewards.php` (mood, journal, gratitude, career, skills, bmi, tarot). **Not** water / weight / nutrition |
| Dedicated tool JS | Only `yuktione-tarot.js`. Others use inline Blade scripts |

**Registry rule:** status from publish migrations that `UPDATE tools`, not `ToolRegistryRegistrar` `firstOrCreate` defaults. All 10 publish migrations set `active` + `is_implemented` + `route_name`.

## Quick matrix

| # | Tool | Status | Primary route | Path | Auth save | Reward hook |
|---|---|---|---|---|---|---|
| 1 | Daily Mood Check-in | active | `tools.psychology.mood` | `/tools/psychology/mood` | Guest draft → login on save | Yes (`MoodCheckinRecorder`) |
| 2 | Digital Journal | active | `tools.psychology.journal` | `/tools/psychology/journal` | Guest draft → login on save | Yes (`JournalRecorder`) |
| 3 | Gratitude Journal | active | `tools.psychology.gratitude` | `/tools/psychology/gratitude` | Guest draft → login on save | Yes (`GratitudeRecorder`) |
| 4 | Career Interest Test | active | `tools.career-interest` | `/tools/career-interest` | Guest take → login on save | Yes (`CareerInterestRecorder`) |
| 5 | Skills & Strengths | active | `tools.skills-strengths` | `/tools/skills-strengths` | Guest take → login on save | Yes (`SkillsStrengthsRecorder`) |
| 6 | BMI & BMR | active | `tools.bmi-bmr` | `/tools/bmi-bmr` | Guest calc → login on save | Yes (`BmiBmrRecorder`) |
| 7 | Tarot Reflection | active | `tools.tarot.intro` | `/tools/tarot` | Entire group `auth:user` | Yes (`TarotReadingRecorder`) |
| 8 | Water Intake | active | `tools.water-intake` | `/tools/water-intake` | Guest welcome; save **403** | No |
| 9 | Weight Journey | active | `tools.weight-journey` | `/tools/weight-journey` | Guest welcome; save **403** | No |
| 10 | Calorie & Nutrition | active | `tools.nutrition` | `/tools/nutrition` | Guest browse; save **403** | No |

## Per-tool notes

### 1. Daily Mood Check-in
- Publish: `2026_10_31_000001_publish_daily_mood_check_in.php`
- Views: `mood-checkin`, `mood-emotions`, `mood-reflection`, `mood-complete`, `mood-history`
- Screens: Mood → Emotions (≤6) → Reflection (optional) → Finish → History
- Tests: `MoodCheckinTest`, `MoodEmotionTest`, `MoodReflectionTest`, `MoodCompleteTest`, `MoodHistoryTest`
- UX debt: disabled Continue easy to miss; emotion grid crowds on narrow phones

### 2. Digital Journal
- Publish: `2026_11_05_000003_publish_digital_journal.php`
- Views: `journal-write`, `journal-entry`, `journal-history`
- Tests: `DigitalJournalWriteTest`, `DigitalJournalHistoryTest`, `DigitalJournalManageTest`
- UX debt: large prompt fieldset; soft keyboard can hide save; JS title sync

### 3. Gratitude Journal
- Publish: `2026_11_05_000004_publish_gratitude_journal.php`
- Views: `gratitude-write`, `gratitude-complete`, `gratitude-entry`, `gratitude-history`
- Screens: 3-card carousel (min 1) → Finish → Show/Edit → History
- Tests: `GratitudeJournalWriteTest`, `GratitudeJournalCompleteTest`, `GratitudeJournalManageTest`
- UX debt: complex carousel; icon-only progress dots; Prev/Next hidden until JS

### 4. Career Interest Test
- Publish: `2026_11_03_130000_publish_career_interest_test.php`
- Views: `career-interest/*`
- Screens: Welcome → About → 6 RIASEC categories → Profile → Suggestions → Save → Result/History
- Tests: `CareerInterestTest` (+ unit scorer)
- UX debt: 5-col Likert cramped; tiny scale labels

### 5. Skills & Strengths
- Publish: `2026_11_03_150000_publish_skills_strengths_assessment.php`
- Views: `skills-strengths/*`
- Screens: Welcome → Intro → 8 categories → Profile → Save → Result/History
- Tests: `SkillsStrengthsTest` (+ unit scorer)
- UX debt: same Likert squeeze; long session-draft flow

### 6. BMI & BMR Calculator
- Publish: `2026_11_03_170000_publish_bmi_bmr_calculator.php`
- Views: `bmi-bmr/*`
- Screens: Form → Results → Understand → Guidance → optional Save → History
- Tests: `BmiBmrTest` (+ unit calculator)
- UX debt: dual unit fields remain in DOM (SR noise)

### 7. Tarot Reflection
- Publish: `2026_11_11_120000_publish_tarot_reflection.php`
- Views: `tarot/*`; JS: `yuktione-tarot.js`
- Screens: Intro → Category → Question → Spread → Draw → Reveal → Reading → Save → History
- Tests: Phase2A–D, Phase2H
- UX debt: dense facedown fan ≤480px; some `outline: none`; preserve draw security in redesigns

### 8. Water Intake Tracker
- Publish: `2026_11_03_190000_publish_water_intake_tracker.php`
- Views: `water-intake/*`
- Screens: Welcome/Dashboard → Today → Week → Goal → entry CRUD
- Tests: `WaterIntakeTest` (+ unit service)
- UX debt: guest CTA lacks soft login redirect; hard 403 on save

### 9. Weight Journey
- Publish: `2026_11_03_210000_publish_weight_journey.php`
- Views: `weight-journey/*` (+ SVG chart partial)
- Screens: Hub → Goal → Log → Progress
- Tests: `WeightJourneyTest` (+ unit service)
- UX debt: maintain-goal % semantics; tiny SVG labels; guest 403 path

### 10. Calorie & Nutrition Tracker
- Publish: `2026_11_05_140000_publish_calorie_nutrition_tracker.php`
- Views: `nutrition/*`
- Screens: Welcome/Dashboard → Search → Portion → Meal → Insights → Goals
- Tests: `NutritionTrackerTest`, `NutritionFoodImportTest` (+ unit portion calculator)
- UX debt: catalogue-pending gate; multi-hop mobile log; guest hard-fail on entry

## Auth pattern split

| Pattern | Tools |
|---|---|
| Guest draft + login redirect on save | Mood, Journal, Gratitude, Career, Skills, BMI |
| Full `auth:user` middleware | Tarot |
| Guest welcome/browse + hard 403 on save | Water, Weight, Nutrition |

## Design system adoption

Existing tool CSS stays authoritative until a later phase wraps a tool root with `data-yk-tools-ds` and migrates classes.

## Proposed later phases

1. Mood → 2. Journals → 3. BMI → 4. Career/Skills → 5. Tarot presentation → 6. Water → 7. Weight → 8. Nutrition
