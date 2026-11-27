<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CategoryTool;
use App\Models\Service;
use App\Models\SubService;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\CategoryCatalog;
use Database\Seeders\CanonicalCategorySeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class Priority02CategoryServiceCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
    }

    public function test_canonical_seeder_is_idempotent_and_creates_nine_categories(): void
    {
        $this->seedTools();
        (new CanonicalCategorySeeder)->run();
        (new CanonicalCategorySeeder)->run();

        $this->assertSame(9, Service::query()->count());
        $this->assertSame(
            CanonicalCategorySeeder::CANONICAL[0]['slug'],
            Service::query()->orderBy('display_order')->value('slug')
        );
        $this->assertGreaterThan(0, CategoryTool::query()->count());
    }

    public function test_admin_can_create_and_update_category_with_visibility_flags(): void
    {
        $admin = $this->actingAsAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.service.store'), [
                'name' => 'Temporary Category',
                'slug' => 'temporary-category',
                'status' => 'active',
                'display_order' => 20,
                'show_on_homepage' => '1',
                'show_in_explore_tools' => '1',
                'short_description' => 'Temp blurb',
            ])
            ->assertRedirect(route('admin.service.index'));

        $category = Service::query()->where('slug', 'temporary-category')->first();
        $this->assertNotNull($category);
        $this->assertTrue($category->show_on_homepage);
        $this->assertTrue($category->show_in_explore_tools);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.service.update', $category->id), [
                'name' => 'Temporary Category Updated',
                'slug' => 'temporary-category',
                'status' => 'active',
                'display_order' => 21,
                'show_on_homepage' => '0',
                'show_in_explore_tools' => '1',
                'tools_sync' => '1',
            ])
            ->assertRedirect(route('admin.service.index'));

        $category->refresh();
        $this->assertSame('Temporary Category Updated', $category->name);
        $this->assertFalse($category->show_on_homepage);
        $this->assertTrue($category->show_in_explore_tools);
    }

    public function test_category_with_dependents_is_deactivated_not_deleted(): void
    {
        $admin = $this->actingAsAdmin();
        $category = Service::query()->create([
            'name' => 'Guarded Category',
            'slug' => 'guarded-category',
            'status' => 'active',
            'display_order' => 1,
        ]);
        SubService::query()->create([
            'service_id' => $category->id,
            'name' => 'Child Service',
            'slug' => 'child-service',
            'status' => 1,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.service.destroy', $category->id))
            ->assertRedirect();

        $this->assertDatabaseHas('services', [
            'id' => $category->id,
            'status' => 'inactive',
        ]);
    }

    public function test_service_must_belong_to_valid_category(): void
    {
        $admin = $this->actingAsAdmin();
        $category = Service::query()->create([
            'name' => 'Parent Category',
            'slug' => 'parent-category',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.sub-service.store'), [
                'service_id' => $category->id,
                'name' => 'Consultation A',
                'slug' => 'consultation-a',
                'status' => 'active',
                'display_order' => 1,
            ])
            ->assertRedirect(route('admin.sub-service.index'));

        $this->assertDatabaseHas('sub_services', [
            'service_id' => $category->id,
            'slug' => 'consultation-a',
        ]);
    }

    public function test_homepage_and_explore_tools_visibility_are_independent(): void
    {
        Service::query()->create([
            'name' => 'Home Only',
            'slug' => 'home-only',
            'status' => 'active',
            'show_on_homepage' => true,
            'show_in_explore_tools' => false,
            'display_order' => 1,
        ]);
        Service::query()->create([
            'name' => 'Tools Only',
            'slug' => 'tools-only',
            'status' => 'active',
            'show_on_homepage' => false,
            'show_in_explore_tools' => true,
            'display_order' => 2,
        ]);

        $catalog = app(CategoryCatalog::class);
        Service::clearCatalogCaches();

        $this->assertTrue($catalog->forHomepage()->pluck('slug')->contains('home-only'));
        $this->assertFalse($catalog->forHomepage()->pluck('slug')->contains('tools-only'));
        $this->assertTrue($catalog->forExploreTools()->pluck('slug')->contains('tools-only'));
        $this->assertFalse($catalog->forExploreTools()->pluck('slug')->contains('home-only'));
    }

    public function test_tool_assignment_drives_tools_catalog_category(): void
    {
        $this->seedTools();
        $category = Service::query()->create([
            'name' => 'Investments',
            'slug' => 'investments',
            'status' => 'active',
            'show_in_explore_tools' => true,
            'display_order' => 1,
        ]);
        $tool = Tool::query()->where('slug', 'net-worth-calculator')->first();
        $this->assertNotNull($tool);

        CategoryTool::query()->create([
            'service_id' => $category->id,
            'tool_id' => $tool->id,
            'display_order' => 1,
            'is_visible' => true,
        ]);
        Service::clearCatalogCaches();

        $map = app(CategoryCatalog::class)->toolCategoryPlacement();
        $this->assertSame('investments', $map['net-worth-calculator']);
    }

    public function test_legacy_category_slug_redirects_to_canonical(): void
    {
        Service::query()->create([
            'name' => 'Mental Wellness',
            'slug' => 'mental-wellness',
            'status' => 'active',
            'show_on_homepage' => true,
            'show_in_explore_tools' => true,
            'display_order' => 1,
            'discovery_needles' => ['mental'],
        ]);
        Service::clearCatalogCaches();

        $this->get('/categories/mental-health')
            ->assertRedirect(route('categories.show', ['category' => 'mental-wellness', 'tab' => 'tools']));
    }

    public function test_categories_index_renders_database_categories(): void
    {
        Service::query()->create([
            'name' => 'Nutrition',
            'slug' => 'nutrition',
            'status' => 'active',
            'display_order' => 1,
            'discovery_needles' => ['nutrition'],
        ]);
        Service::clearCatalogCaches();

        $rows = app(\App\Services\CategoryHub::class)->list();
        $this->assertTrue(collect($rows)->contains(fn ($row) => ($row['label'] ?? '') === 'Nutrition'));

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Nutrition', false);
    }

    private function actingAsAdmin(): Admin
    {
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        return Admin::query()->create([
            'name' => 'Catalog Admin',
            'email' => 'catalog-admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);
    }

    private function seedTools(): void
    {
        $toolCategory = ToolCategory::query()->create([
            'name' => 'Money',
            'slug' => 'money-finance',
            'is_active' => true,
            'display_order' => 1,
        ]);

        foreach (['net-worth-calculator', 'debt-emi-calculator'] as $index => $slug) {
            Tool::query()->create([
                'tool_category_id' => $toolCategory->id,
                'name' => str_replace('-', ' ', $slug),
                'slug' => $slug,
                'module_key' => 'money-finance',
                'status' => 'active',
                'is_implemented' => true,
                'display_order' => $index + 1,
                'route_name' => 'tools.pfm.net-worth',
            ]);
        }
    }

    private function buildSchema(): void
    {
        Schema::dropIfExists('category_tool');
        Schema::dropIfExists('tools');
        Schema::dropIfExists('tool_categories');
        Schema::dropIfExists('sub_services');
        Schema::dropIfExists('services');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('professional_services');

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('short_description')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('image')->nullable();
            $table->string('icon_name')->nullable();
            $table->string('icon_path')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('show_on_homepage')->default(false);
            $table->boolean('show_in_explore_tools')->default(true);
            $table->json('discovery_needles')->nullable();
            $table->string('tone')->nullable();
            $table->timestamps();
        });

        Schema::create('sub_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tool_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
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
            $table->string('icon')->nullable();
            $table->string('access_type')->default('free');
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        Schema::create('category_tool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['service_id', 'tool_id']);
        });
    }
}
