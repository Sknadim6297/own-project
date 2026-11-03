<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminMenu;
use App\Models\NutritionFood;
use App\Models\NutritionFoodCategory;
use App\Models\NutritionImportBatch;
use App\Models\NutritionMealEntry;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Nutrition\NutritionFoodImportService;
use App\Services\Nutrition\NutritionFoodReviewService;
use App\Services\NutritionPortionCalculator;
use App\Support\Nutrition\NutritionCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class NutritionFoodImportTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
        $this->fixtureDir = storage_path('app/nutrition/imports');
        if (! is_dir($this->fixtureDir)) {
            mkdir($this->fixtureDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (NutritionCatalog::IMPORT_SOURCES as $meta) {
            $path = $this->fixtureDir.DIRECTORY_SEPARATOR.$meta['filename'];
            if (is_file($path) && str_contains(file_get_contents($path) ?: '', 'FIXTURE_PHASE8')) {
                @unlink($path);
            }
            // Also remove any test fixtures we wrote (marker in custom sheet title handled below)
            if (is_file($path)) {
                // Only delete if created during this test class via createFixtureWorkbook marker cell
                try {
                    $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                    if ($ss->getActiveSheet()->getCell('Z1')->getValue() === 'FIXTURE_PHASE8') {
                        @unlink($path);
                    }
                } catch (\Throwable) {
                }
            }
        }
        parent::tearDown();
    }

    public function test_missing_files_stop_import_command(): void
    {
        $this->schema();
        foreach (NutritionCatalog::IMPORT_SOURCES as $meta) {
            @unlink($this->fixtureDir.DIRECTORY_SEPARATOR.$meta['filename']);
        }

        $exit = Artisan::call('nutrition:import-foods', ['--dry-run' => true]);
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Missing workbook', Artisan::output());
        $this->assertSame(0, NutritionFood::query()->count());
    }

    public function test_dry_run_creates_no_foods_and_maps_columns(): void
    {
        $this->schema();
        $this->writeFixtures();

        $report = app(NutritionFoodImportService::class)->run('all', true, 'phpunit');
        $this->assertTrue($report['dry_run']);
        $this->assertSame(0, NutritionFood::query()->count());
        $this->assertSame(0, NutritionImportBatch::query()->where('is_dry_run', false)->count());
        $this->assertGreaterThan(0, $report['sources']['350']['stats']['valid_rows']);
        $this->assertGreaterThan(0, $report['sources']['700']['stats']['valid_rows']);
        $this->assertContains('name', $report['sources']['700']['mapped_fields']);
        $this->assertContains('calories_kcal', $report['sources']['700']['mapped_fields']);
    }

    public function test_import_idempotent_verified_protection_and_visibility(): void
    {
        $this->schema();
        $this->writeFixtures();

        $service = app(NutritionFoodImportService::class);
        $first = $service->run('all', false, 'phpunit');
        $foodsAfterFirst = NutritionFood::query()->count();
        $this->assertGreaterThan(0, $foodsAfterFirst);
        $this->assertSame(0, NutritionFood::query()->where('is_published', true)->count());
        $this->assertSame(0, NutritionFood::query()->where('verification_status', 'verified')->count());

        $master = NutritionFood::query()->whereNull('calories_kcal')->first();
        $this->assertNotNull($master);
        $this->assertSame('incomplete', $master->nutrient_completeness);

        $populated = NutritionFood::query()->whereNotNull('calories_kcal')->first();
        $this->assertNotNull($populated);
        $this->assertContains($populated->verification_status, ['unverified', 'needs_verification']);

        $second = $service->run('all', false, 'phpunit');
        $this->assertSame($foodsAfterFirst, NutritionFood::query()->count());
        $this->assertGreaterThan(0, $second['sources']['700']['stats']['skipped_existing']);

        $reviews = app(NutritionFoodReviewService::class);
        $reviews->transition($populated, 'verify', 1, 'Fixture label cross-check', 'Reviewed macros for test', null, true);
        $populated->refresh();
        $this->assertTrue($populated->is_published);
        $this->assertSame('verified', $populated->verification_status);

        $before = (string) $populated->calories_kcal;
        $service->run('700', false, 'phpunit');
        $this->assertSame($before, (string) $populated->fresh()->calories_kcal);
        $this->assertGreaterThan(0, NutritionImportBatch::query()->latest('id')->first()->conflicts);

        $this->assertTrue(
            NutritionFood::query()->catalogueVisible()->whereKey($populated->id)->exists()
        );
        $draft = NutritionFood::query()->where('is_published', false)->first();
        $this->assertFalse(
            NutritionFood::query()->catalogueVisible()->whereKey($draft->id)->exists()
        );
    }

    public function test_duplicate_raw_vs_cooked_not_auto_merged(): void
    {
        $this->schema();
        $this->createFixtureWorkbook('350', [
            ['Food Name', 'Category', 'Preparation'],
            ['Rice', 'Grains', 'Raw'],
            ['Rice', 'Grains', 'Cooked'],
        ], false);
        $this->createFixtureWorkbook('700', [
            ['Food Name', 'Brand', 'Calories', 'Protein', 'Carbohydrates', 'Fat', 'Basis'],
        ], true);

        app(NutritionFoodImportService::class)->run('350', false, 'phpunit');
        $this->assertSame(2, NutritionFood::query()->count());
        $states = NutritionFood::query()->pluck('preparation_state')->sort()->values()->all();
        $this->assertSame(['cooked', 'raw'], $states);
        $this->assertGreaterThan(0, \App\Models\NutritionDuplicateReview::query()->count());
    }

    public function test_invalid_and_missing_nutrients_and_portion_bases(): void
    {
        $this->schema();
        $this->createFixtureWorkbook('350', [['Food Name'], ['Idli']], false);
        $this->createFixtureWorkbook('700', [
            ['Food Name', 'Calories', 'Protein', 'Carbohydrates', 'Fat', 'Basis', 'Serving Unit'],
            ['Good Yogurt', 60, 3.5, 4, 2, 'per 100ml', 'ml'],
            ['Bad Negative', -5, 1, 1, 1, 'per 100g', 'g'],
            ['Missing Macros', '', '', '', '', 'per 100g', 'g'],
        ], true);

        $report = app(NutritionFoodImportService::class)->run('700', false, 'phpunit');
        $this->assertGreaterThan(0, $report['sources']['700']['stats']['rejected_rows']);

        $yogurt = NutritionFood::query()->where('name', 'Good Yogurt')->first();
        $this->assertNotNull($yogurt);
        $this->assertSame('per_100ml', $yogurt->reference_basis);

        $calc = app(NutritionPortionCalculator::class);
        $estimate = $calc->estimate($yogurt, '200', 'ml');
        $this->assertSame('120.0000', $estimate['calories_kcal']);

        $this->expectException(\InvalidArgumentException::class);
        $calc->estimate($yogurt, '200', 'g');
    }

    public function test_admin_review_authorization_and_meal_snapshot_preserved(): void
    {
        $this->schema();
        $this->adminSchema();
        $food = NutritionFood::query()->create([
            'name' => 'Dal',
            'normalized_name' => 'dal',
            'preparation_state' => 'cooked',
            'reference_basis' => 'per_100g',
            'calories_kcal' => '116',
            'protein_g' => '9',
            'carbs_g' => '16',
            'fat_g' => '2',
            'verification_status' => 'needs_verification',
            'nutrient_completeness' => 'macros_present',
            'is_published' => false,
        ]);

        $blocked = Admin::query()->create([
            'name' => 'Blocked',
            'email' => 'blocked-p8@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->actingAs($blocked, 'admin')
            ->post(route('admin.nutrition-foods.review', $food), [
                'action' => 'verify',
                'source_basis' => 'x',
                'notes' => 'y',
            ])->assertForbidden();

        $admin = Admin::query()->create([
            'name' => 'Super',
            'email' => 'super-p8@example.com',
            'password' => Hash::make('secret'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $this->actingAs($admin, 'admin')
            ->post(route('admin.nutrition-foods.review', $food), [
                'action' => 'verify',
                'source_basis' => 'Lab sheet A',
                'notes' => 'Checked against source table',
                'publish_after_verify' => 1,
            ])->assertRedirect();
        $this->assertSame('verified', $food->fresh()->verification_status);
        $this->assertTrue($food->fresh()->is_published);

        $user = User::query()->create(['name' => 'U', 'email' => 'p8u@example.com', 'password' => 'x']);
        $entry = new NutritionMealEntry([
            'nutrition_food_id' => $food->id,
            'meal_type' => 'lunch',
            'quantity' => 100,
            'unit' => 'g',
            'portion_grams' => 100,
            'consumed_at' => now(),
            'food_name_snapshot' => 'Dal',
            'calories_kcal_snapshot' => '116.0000',
            'protein_g_snapshot' => '9.0000',
            'carbs_g_snapshot' => '16.0000',
            'fat_g_snapshot' => '2.0000',
            'incomplete_nutrients' => false,
            'idempotency_key' => 'p8-snap',
        ]);
        $entry->user_id = $user->id;
        $entry->save();

        $food->update(['calories_kcal' => '999']);
        $this->assertSame('116.0000', (string) $entry->fresh()->calories_kcal_snapshot);
        $this->assertFalse(config('yuktione.programmes_enabled'));
        $this->assertSame('coming_soon', Tool::query()->where('slug', 'calorie-nutrition-tracker')->value('status'));
    }

    private function writeFixtures(): void
    {
        $this->createFixtureWorkbook('350', [
            ['Food Name', 'Category', 'Preparation'],
            ['Idli', 'Breakfast', 'Cooked'],
            ['Masala Dosa', 'Breakfast', 'Cooked'],
            ['Raw Mango', 'Fruits', 'Raw'],
        ], false);

        $this->createFixtureWorkbook('700', [
            ['Food Name', 'Brand', 'Variant', 'Category', 'Preparation', 'Basis', 'Calories', 'Protein', 'Carbohydrates', 'Fat', 'Fibre', 'Serving Label', 'Serving Quantity', 'Serving Unit', 'Serving Grams', 'Source', 'Source URL'],
            ['Paneer', 'Amul', 'Fresh', 'Dairy', 'Packaged', 'per 100g', 265, 18, 1.2, 20, 0, 'cube', 1, 'piece', 30, 'Label', 'https://example.test/paneer'],
            ['Cooked Rice', '', '', 'Grains', 'Cooked', 'per 100g', 130, 2.7, 28, 0.3, 0.4, '', '', 'g', '', 'IFCT', ''],
            ['Toned Milk', 'Mother Dairy', '', 'Dairy', 'Packaged', 'per 100ml', 58, 3.1, 4.7, 3, '', 'glass', 200, 'ml', '', 'Label', ''],
        ], true);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function createFixtureWorkbook(string $sourceKey, array $rows, bool $populated): void
    {
        $meta = NutritionCatalog::IMPORT_SOURCES[$sourceKey];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($populated ? 'Foods700' : 'Masterlist350');
        $sheet->setCellValue('Z1', 'FIXTURE_PHASE8');
        foreach ($rows as $r => $cols) {
            foreach ($cols as $c => $value) {
                $sheet->setCellValueByColumnAndRow($c + 1, $r + 1, $value);
            }
        }
        $path = $this->fixtureDir.DIRECTORY_SEPARATOR.$meta['filename'];
        (new Xlsx($spreadsheet))->save($path);
    }

    private function schema(): void
    {
        foreach ([
            'nutrition_meal_entries',
            'nutrition_duplicate_reviews',
            'nutrition_food_review_events',
            'nutrition_import_rows',
            'nutrition_food_servings',
            'nutrition_foods',
            'nutrition_import_batches',
            'nutrition_food_categories',
            'nutrition_user_goals',
            'tools',
            'tool_categories',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('tool_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_category_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module_key');
            $table->text('description')->nullable();
            $table->string('status')->default('coming_soon');
            $table->string('route_name')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        (require database_path('migrations/2026_11_03_220000_create_nutrition_tracker_tables.php'))->up();
        (require database_path('migrations/2026_11_04_000100_create_nutrition_import_review_tables.php'))->up();

        $category = ToolCategory::query()->create(['name' => 'Nutrition', 'slug' => 'nutrition']);
        Tool::query()->create([
            'tool_category_id' => $category->id,
            'name' => 'Calorie & Nutrition Tracker',
            'slug' => 'calorie-nutrition-tracker',
            'module_key' => 'nutrition',
            'status' => 'coming_soon',
            'route_name' => null,
            'is_implemented' => false,
            'display_order' => 4,
        ]);
    }

    private function adminSchema(): void
    {
        foreach (['admin_menu_permissions', 'admin_menus', 'admins', 'bookings', 'job_applications', 'notifications'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->string('route_name')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        Schema::create('admin_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('admin_menu_id');
            $table->timestamps();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('plan_type')->nullable();
        });
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        AdminMenu::query()->create([
            'name' => 'tools_management',
            'display_name' => 'Tools Management',
            'order' => 1,
        ]);
    }
}
