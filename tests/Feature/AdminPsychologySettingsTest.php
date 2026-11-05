<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminMenu;
use App\Models\Tool;
use App\Models\ToolConfigItem;
use App\Services\PsychologyConfigRegistrar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPsychologySettingsTest extends TestCase
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
        Schema::create('tool_config_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->string('group_key');
            $table->string('item_key');
            $table->string('label');
            $table->text('hint')->nullable();
            $table->text('detail')->nullable();
            $table->string('bucket')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['tool_id', 'group_key', 'item_key']);
        });

        $parent = AdminMenu::query()->create([
            'name' => 'tools_management',
            'display_name' => 'Tools Management',
            'route_name' => null,
            'order' => 1,
        ]);
        AdminMenu::query()->create([
            'name' => 'tools.psychology',
            'display_name' => 'Psychology',
            'route_name' => 'admin.psychology.settings.edit',
            'parent_id' => $parent->id,
            'order' => 4,
        ]);
    }

    public function test_guests_and_unassigned_admins_cannot_configure_psychology(): void
    {
        $this->get(route('admin.psychology.settings.edit'))
            ->assertRedirect(route('admin.login'));
        $this->put(route('admin.psychology.settings.update'), [])
            ->assertRedirect(route('admin.login'));

        $admin = $this->admin('limited@example.com', 'admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.psychology.settings.edit'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin, 'admin')
            ->put(route('admin.psychology.settings.update'), [])
            ->assertForbidden();
    }

    public function test_settings_page_shows_documented_options_and_keeps_tools_private(): void
    {
        app(PsychologyConfigRegistrar::class)->sync();
        app(PsychologyConfigRegistrar::class)->sync();

        $this->assertSame(29, ToolConfigItem::query()->count());
        $this->assertSame(5, ToolConfigItem::query()->where('group_key', 'primary_mood')->count());
        $this->assertSame(15, ToolConfigItem::query()->where('group_key', 'emotion')->count());
        $this->assertSame(6, ToolConfigItem::query()->where('group_key', 'journal_prompt')->count());
        $this->assertSame(3, ToolConfigItem::query()->where('group_key', 'gratitude_card')->count());
        $this->assertFalse(Schema::hasTable('mood_checkins'));
        $this->assertFalse(Schema::hasTable('journal_entries'));
        $this->assertFalse(Schema::hasTable('gratitude_entries'));

        $this->actingAs($this->admin('super@example.com', 'super_admin'), 'admin')
            ->get(route('admin.psychology.settings.edit'))
            ->assertOk()
            ->assertSee('Psychology Settings')
            ->assertSee('Mood Check-in')
            ->assertSee('Digital Journal')
            ->assertSee('Gratitude Journal')
            ->assertSee('Very happy')
            ->assertSee('Good')
            ->assertSee('Neutral')
            ->assertSee('Sad')
            ->assertSee('Stressed')
            ->assertSee('Positive')
            ->assertSee('Difficult')
            ->assertSee('Happy')
            ->assertSee('Grateful')
            ->assertSee('Anxious')
            ->assertSee('at most six emotions')
            ->assertSee('1,000 characters')
            ->assertSee('My thoughts')
            ->assertSee('Personal growth')
            ->assertSee('Work &amp; career', false)
            ->assertSee('Relationships')
            ->assertSee('Difficult moments')
            ->assertSee('My aspirations')
            ->assertSee('Something good today')
            ->assertSee('Someone I appreciate')
            ->assertSee('Something about myself')
            ->assertSee('Finish &amp; Save', false)
            ->assertSee('At least one answered card')
            ->assertSee('Save Changes')
            ->assertSee('Public')
            ->assertDontSee('name="reflection"', false)
            ->assertDontSee('name="journal_body"', false)
            ->assertDontSee('name="gratitude_response"', false);

        $unpublishedSlugs = [
            'career-interest-test',
            'skills-strengths-assessment',
            'bmi-bmr-calculator',
            'water-intake-tracker',
            'weight-journey',
            'calorie-nutrition-tracker',
        ];
        $this->assertSame(
            count($unpublishedSlugs),
            Tool::query()
                ->whereIn('slug', $unpublishedSlugs)
                ->where('status', 'coming_soon')
                ->where('is_implemented', false)
                ->whereNull('route_name')
                ->count()
        );
        $this->assertSame(
            count($unpublishedSlugs),
            Tool::query()->where('status', 'coming_soon')->where('is_implemented', false)->whereNull('route_name')->count()
        );
        foreach (['daily-mood-check-in', 'digital-journal', 'gratitude-journal'] as $slug) {
            $this->assertTrue(Tool::query()->where('slug', $slug)->firstOrFail()->canLaunch(), $slug);
        }

        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('Daily Mood Check-in')
            ->assertSee('Digital Journal')
            ->assertSee('Gratitude Journal')
            ->assertSee('Start Check-in')
            ->assertDontSee('Something good today');

        $this->get('/tools/daily-mood-check-in')->assertNotFound();
    }

    public function test_assigned_admin_can_save_wording_prompts_and_gratitude_cards(): void
    {
        app(PsychologyConfigRegistrar::class)->sync();
        $admin = $this->admin('editor@example.com', 'admin');
        $admin->menus()->attach(AdminMenu::query()->where('name', 'tools.psychology')->value('id'));

        $payload = $this->payload();
        $payload['moods']['very-happy']['label'] = 'Really glad';
        $payload['prompts']['my-thoughts']['label'] = 'What is on my mind';
        $payload['prompts']['my-thoughts']['is_active'] = '0';
        $payload['prompts']['my-thoughts']['display_order'] = 9;
        $payload['new_prompt'] = ['label' => 'Evening review', 'display_order' => 30];
        $payload['cards']['something-good-today']['detail'] = 'Which moment felt good today?';
        $payload['cards']['something-good-today']['hint'] = 'Keep it to one moment.';
        $payload['tab'] = 'gratitude';

        $this->actingAs($admin, 'admin')
            ->get(route('admin.psychology.settings.edit'))
            ->assertOk()
            ->assertSee('My thoughts');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.psychology.settings.update'), $payload)
            ->assertRedirect(route('admin.psychology.settings.edit', ['tab' => 'gratitude']));

        $this->assertSame('Really glad', ToolConfigItem::query()->where('item_key', 'very-happy')->value('label'));
        $this->assertSame('What is on my mind', ToolConfigItem::query()->where('item_key', 'my-thoughts')->value('label'));
        $this->assertFalse((bool) ToolConfigItem::query()->where('item_key', 'my-thoughts')->value('is_active'));
        $this->assertSame(9, (int) ToolConfigItem::query()->where('item_key', 'my-thoughts')->value('display_order'));
        $this->assertNotNull(ToolConfigItem::query()->where('item_key', 'evening-review')->where('is_system', false)->first());
        $this->assertSame('Which moment felt good today?', ToolConfigItem::query()->where('item_key', 'something-good-today')->value('detail'));
        $unpublishedSlugs = [
            'career-interest-test',
            'skills-strengths-assessment',
            'bmi-bmr-calculator',
            'water-intake-tracker',
            'weight-journey',
            'calorie-nutrition-tracker',
        ];
        $this->assertSame(
            count($unpublishedSlugs),
            Tool::query()->where('status', 'coming_soon')->where('is_implemented', false)->count()
        );
        $this->assertTrue(Tool::query()->where('slug', 'daily-mood-check-in')->firstOrFail()->canLaunch());
        $this->assertTrue(Tool::query()->where('slug', 'digital-journal')->firstOrFail()->canLaunch());
        $this->assertTrue(Tool::query()->where('slug', 'gratitude-journal')->firstOrFail()->canLaunch());

        app(PsychologyConfigRegistrar::class)->sync();
        $this->assertSame(30, ToolConfigItem::query()->count());
        $this->assertSame('Really glad', ToolConfigItem::query()->where('item_key', 'very-happy')->value('label'));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.psychology.settings.edit', ['tab' => 'journal']))
            ->assertOk()
            ->assertSee('What is on my mind')
            ->assertSee('Evening review');
    }

    public function test_constraints_and_system_prompts_cannot_be_bypassed(): void
    {
        app(PsychologyConfigRegistrar::class)->sync();
        $admin = $this->admin('super@example.com', 'super_admin');
        $payload = $this->payload();
        $payload['moods']['good']['label'] = 'Pretty good';
        $payload['reflection_max'] = 5000;

        $this->actingAs($admin, 'admin')
            ->from(route('admin.psychology.settings.edit'))
            ->put(route('admin.psychology.settings.update'), $payload)
            ->assertSessionHasErrors('reflection_max');
        $this->assertSame('Good', ToolConfigItem::query()->where('item_key', 'good')->value('label'));

        $brokenMoods = $payload;
        unset($brokenMoods['reflection_max'], $brokenMoods['moods']['stressed']);
        $brokenMoods['moods']['ecstatic'] = ['label' => 'Ecstatic', 'display_order' => 6];
        $this->actingAs($admin, 'admin')
            ->put(route('admin.psychology.settings.update'), $brokenMoods)
            ->assertSessionHasErrors('moods');

        $brokenEmotions = $this->payload();
        unset($brokenEmotions['emotions']['happy']);
        $brokenEmotions['emotions']['overjoyed'] = ['label' => 'Overjoyed', 'display_order' => 1];
        $this->actingAs($admin, 'admin')
            ->put(route('admin.psychology.settings.update'), $brokenEmotions)
            ->assertSessionHasErrors('emotions');

        $brokenCards = $this->payload();
        unset($brokenCards['cards']['something-about-myself']);
        $this->actingAs($admin, 'admin')
            ->put(route('admin.psychology.settings.update'), $brokenCards)
            ->assertSessionHasErrors('cards');

        $remove = $this->payload();
        $systemId = ToolConfigItem::query()->where('item_key', 'my-thoughts')->value('id');
        $remove['remove_prompts'] = [$systemId];
        $remove['prompts']['my-thoughts']['label'] = 'Gone';
        $this->actingAs($admin, 'admin')
            ->put(route('admin.psychology.settings.update'), $remove)
            ->assertSessionHasErrors('remove_prompts');
        $this->assertSame('My thoughts', ToolConfigItem::query()->where('item_key', 'my-thoughts')->value('label'));

        $added = $this->payload();
        $added['new_prompt'] = ['label' => 'Evening review', 'display_order' => 40];
        $this->actingAs($admin, 'admin')
            ->put(route('admin.psychology.settings.update'), $added)
            ->assertRedirect();

        $customId = ToolConfigItem::query()->where('item_key', 'evening-review')->value('id');
        $drop = $this->payload();
        $drop['remove_prompts'] = [$customId];
        $this->actingAs($admin, 'admin')
            ->put(route('admin.psychology.settings.update'), $drop)
            ->assertRedirect();
        $this->assertNull(ToolConfigItem::query()->where('item_key', 'evening-review')->first());
        $this->assertSame(29, ToolConfigItem::query()->count());
    }

    private function admin(string $email, string $role): Admin
    {
        return Admin::query()->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => Hash::make('secret'),
            'role' => $role,
        ]);
    }

    private function payload(): array
    {
        $payload = [
            'moods' => [],
            'emotions' => [],
            'prompts' => [],
            'cards' => [],
            'new_prompt' => ['label' => '', 'display_order' => 20],
        ];

        foreach (ToolConfigItem::query()->orderBy('id')->get() as $item) {
            if ($item->group_key === 'primary_mood') {
                $payload['moods'][$item->item_key] = [
                    'label' => $item->label,
                    'display_order' => $item->display_order,
                ];
            } elseif ($item->group_key === 'emotion') {
                $payload['emotions'][$item->item_key] = [
                    'label' => $item->label,
                    'display_order' => $item->display_order,
                ];
            } elseif ($item->group_key === 'journal_prompt') {
                $payload['prompts'][$item->item_key] = [
                    'label' => $item->label,
                    'display_order' => $item->display_order,
                    'is_active' => $item->is_active ? '1' : '0',
                ];
            } elseif ($item->group_key === 'gratitude_card') {
                $payload['cards'][$item->item_key] = [
                    'label' => $item->label,
                    'hint' => $item->hint,
                    'detail' => $item->detail,
                ];
            }
        }

        return $payload;
    }
}
