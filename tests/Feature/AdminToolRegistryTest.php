<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminMenu;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\ToolRegistryRegistrar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminToolRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->foreignId('tool_category_id')->nullable()->constrained('tool_categories')->nullOnDelete();
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

        AdminMenu::query()->create([
            'name' => 'tools_management',
            'display_name' => 'Tools Management',
            'route_name' => null,
            'order' => 1,
        ]);
        AdminMenu::query()->create([
            'name' => 'tools.all',
            'display_name' => 'All Tools',
            'route_name' => 'admin.tools.index',
            'parent_id' => 1,
            'order' => 1,
        ]);
    }

    public function test_guest_cannot_open_the_tool_registry(): void
    {
        $this->get(route('admin.tools.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_psychology_tools_register_once_and_block_admin_publish_of_unimplemented_tools(): void
    {
        $registrar = app(ToolRegistryRegistrar::class);
        $registrar->register();
        $registrar->register();

        $this->assertSame(4, ToolCategory::query()->count());
        $this->assertSame(10, Tool::query()->count());
        $unpublishedSlugs = [
            'career-interest-test',
            'skills-strengths-assessment',
            'bmi-bmr-calculator',
            'water-intake-tracker',
            'weight-journey',
            'calorie-nutrition-tracker',
            'tarot-reflection',
        ];
        $this->assertSame(
            count($unpublishedSlugs),
            Tool::query()->where('status', 'coming_soon')->where('is_implemented', false)->whereNull('route_name')->count()
        );
        foreach ([
            'daily-mood-check-in' => 'tools.psychology.mood',
            'digital-journal' => 'tools.psychology.journal',
            'gratitude-journal' => 'tools.psychology.gratitude',
        ] as $slug => $route) {
            $published = Tool::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame('active', $published->status, $slug);
            $this->assertTrue($published->is_implemented, $slug);
            $this->assertSame($route, $published->route_name, $slug);
            $this->assertTrue($published->canLaunch(), $slug);
        }

        $admin = Admin::query()->create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('secret'),
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.tools.index'))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Digital Journal')
            ->assertSee('Gratitude Journal')
            ->assertSee('Career Interest Test')
            ->assertSee('BMI & BMR Calculator')
            ->assertSee('Coming Soon')
            ->assertDontSee('>Enable<', false);

        $tool = Tool::query()->where('slug', 'calorie-nutrition-tracker')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.tools.toggle', $tool))
            ->assertSessionHasErrors('status');

        $this->assertSame('coming_soon', $tool->fresh()->status);
        $this->assertFalse($tool->fresh()->is_implemented);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.tools.update', $tool), [
                'name' => $tool->name,
                'slug' => $tool->slug,
                'module_key' => 'nutrition',
                'tool_category_id' => $tool->tool_category_id,
                'description' => $tool->description,
                'icon' => $tool->icon,
                'access_type' => 'free',
                'status' => 'active',
                'display_order' => 4,
            ])
            ->assertStatus(422);

        $category = ToolCategory::query()->first();
        $this->actingAs($admin, 'admin')
            ->put(route('admin.tool-categories.update', $category), [
                'name' => 'Psychology',
                'slug' => 'psychology',
                'description' => 'Updated',
                'icon' => 'ri-mental-health-line',
                'is_active' => '0',
                'display_order' => 1,
            ])
            ->assertRedirect(route('admin.tool-categories.index'));

        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame(3, $category->tools()->count());

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.tool-categories.destroy', $category))
            ->assertSessionHasErrors('category');
        $this->assertNotNull($category->fresh());

        $this->actingAs($admin, 'admin')
            ->put(route('admin.tool-settings.update'), [
                'tools' => Tool::query()->orderBy('id')->get()->values()->map(function (Tool $row, int $index) {
                    return [
                        'id' => $row->id,
                        'display_order' => 10 + $index,
                        'access_type' => 'paid',
                        'status' => 'active',
                    ];
                })->all(),
            ])
            ->assertRedirect(route('admin.tool-settings.edit'));

        $this->assertSame(count($unpublishedSlugs), Tool::query()->where('status', 'coming_soon')->where('is_implemented', false)->count());
        $this->assertSame(10, Tool::query()->where('access_type', 'paid')->count());
        $published = Tool::query()->where('slug', 'daily-mood-check-in')->firstOrFail();
        $this->assertSame('tools.psychology.mood', $published->route_name);
        $this->assertSame('active', $published->status);
        $this->assertTrue($published->canLaunch());

        $this->actingAs($admin, 'admin')
            ->get(route('admin.tools.index', [
                'q' => 'Mood',
                'status' => 'active',
                'category' => $category->id,
            ]))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertDontSee('Digital Journal');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.tools.index', ['q' => 'nothing-matches']))
            ->assertOk()
            ->assertSee('No tools match these filters.');

        foreach (range(1, 12) as $number) {
            Tool::query()->create([
                'tool_category_id' => $category->id,
                'name' => 'Extra Tool '.$number,
                'slug' => 'extra-tool-'.$number,
                'module_key' => 'psychology',
                'access_type' => 'free',
                'status' => 'unavailable',
                'display_order' => 100 + $number,
                'is_implemented' => false,
            ]);
        }

        $this->actingAs($admin, 'admin')
            ->get(route('admin.tools.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Extra Tool');

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Digital Journal')
            ->assertSee('Gratitude Journal')
            ->assertSee('Start Check-in')
            ->assertSee(route('tools.psychology.mood'), false)
            ->assertSee(route('tools.psychology.journal'), false)
            ->assertSee(route('tools.psychology.gratitude'), false)
            ->assertDontSee('Career Interest Test')
            ->assertDontSee('Calorie & Nutrition Tracker')
            ->assertDontSee('Extra Tool')
            ->assertDontSee(route('tools.psychology.mood.history'), false);

        $this->get('/tools/daily-mood-check-in')->assertNotFound();
        $this->get('/tools/digital-journal')->assertNotFound();
        $this->get('/tools/gratitude-journal')->assertNotFound();
    }

    public function test_admin_without_the_menu_cannot_list_tools(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Limited',
            'email' => 'limited@example.com',
            'password' => Hash::make('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.tools.index'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
