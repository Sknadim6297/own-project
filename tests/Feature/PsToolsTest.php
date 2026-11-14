<?php

namespace Tests\Feature;

use App\Models\PsToolResult;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Support\ParentingSpirituality\PsCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PsToolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
            \App\Http\Middleware\EnsureCustomerTermsAccepted::class,
        ]);
    }

    public function test_hub_lists_parenting_and_spirituality_tools(): void
    {
        $this->storage();

        $this->get(route('tools.ps'))
            ->assertOk()
            ->assertSee('Parenting Stress Check')
            ->assertSee('Purpose &amp; Values Finder', false);
    }

    public function test_guest_can_complete_stress_check(): void
    {
        $this->storage();

        $this->get(route('tools.ps.stress-check'))
            ->assertOk()
            ->assertSee('Parenting Stress Check')
            ->assertSee('not a diagnosis', false);

        $this->post(route('tools.ps.stress-check.calculate'), [
            'q1' => 4, 'q2' => 4, 'q3' => 3, 'q4' => 3,
            'q5' => 2, 'q6' => 2, 'q7' => 4, 'q8' => 4,
        ])->assertRedirect(route('tools.ps.stress-check.results'));

        $this->get(route('tools.ps.stress-check.results'))
            ->assertOk()
            ->assertSee('Stress level')
            ->assertSee('Pressure areas');
    }

    public function test_guest_save_requires_login(): void
    {
        $this->storage();

        $this->post(route('tools.ps.mindfulness.calculate'), [
            'state' => 'stressed',
            'duration' => 3,
            'practice' => 'Breathing',
        ])->assertRedirect(route('tools.ps.mindfulness.results'));

        $this->post(route('tools.ps.mindfulness.save'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_save_and_delete_private_history(): void
    {
        $this->storage();
        $user = $this->member('ps-user@example.com');

        $this->actingAs($user, 'user')
            ->post(route('tools.ps.conversation-guide.calculate'), [
                'situation' => 'screen_time',
                'age_band' => '8-12',
                'goal' => 'boundary',
                'context' => 'Child wanted more game time after homework.',
            ])->assertRedirect(route('tools.ps.conversation-guide.results'));

        $this->actingAs($user, 'user')
            ->post(route('tools.ps.conversation-guide.save'))
            ->assertRedirect();

        $record = PsToolResult::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('parent-child-conversation-guide', $record->tool_key);

        $other = $this->member('ps-other@example.com');
        $this->actingAs($other, 'user')
            ->get(route('tools.ps.conversation-guide.history.show', $record))
            ->assertForbidden();

        $this->actingAs($user, 'user')
            ->delete(route('tools.ps.conversation-guide.history.destroy', $record))
            ->assertRedirect(route('tools.ps.conversation-guide.history'));

        $this->assertDatabaseMissing('ps_tool_results', ['id' => $record->id]);
    }

    public function test_values_finder_guest_flow(): void
    {
        $this->storage();
        $input = [];
        foreach (\App\Support\ParentingSpirituality\PsContent::valuePairs() as $pair) {
            $input['q'.$pair['q']] = $pair['a'];
        }

        $this->post(route('tools.ps.values-finder.calculate'), $input)
            ->assertRedirect(route('tools.ps.values-finder.results'));

        $this->get(route('tools.ps.values-finder.results'))
            ->assertOk()
            ->assertSee('Top values')
            ->assertSee('Action plan');
    }

    public function test_routine_planner_guest_flow(): void
    {
        $this->storage();

        $this->post(route('tools.ps.routine-planner.calculate'), [
            'child_age' => 9,
            'wake_time' => '07:00',
            'bed_time' => '21:00',
            'school_start' => '08:30',
            'school_end' => '14:00',
            'study_minutes' => 40,
            'play_minutes' => 40,
            'screen_minutes' => 20,
            'family_minutes' => 20,
            'commute_minutes' => 15,
        ])->assertRedirect(route('tools.ps.routine-planner.results'));

        $this->get(route('tools.ps.routine-planner.results'))
            ->assertOk()
            ->assertSee('Daily timeline')
            ->assertSee('School / daycare');
    }

    private function storage(): void
    {
        foreach (['ps_tool_results', 'tools', 'tool_categories', 'customer_profiles', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('profile_image')->nullable();
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
            $table->string('status');
            $table->string('route_name')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        Schema::create('ps_tool_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tool_key', 64)->index();
            $table->string('title', 160);
            $table->json('inputs');
            $table->json('outputs');
            $table->string('summary', 255)->nullable();
            $table->string('content_version', 64)->nullable();
            $table->uuid('completion_key')->unique();
            $table->boolean('is_draft')->default(false)->index();
            $table->timestamps();
        });

        $parenting = ToolCategory::query()->create([
            'name' => 'Parenting',
            'slug' => 'parenting',
            'is_active' => true,
            'display_order' => 6,
        ]);
        $spirituality = ToolCategory::query()->create([
            'name' => 'Spirituality',
            'slug' => 'spirituality',
            'is_active' => true,
            'display_order' => 7,
        ]);

        foreach (PsCatalog::TOOLS as $tool) {
            $categoryId = $tool['category'] === 'spirituality' ? $spirituality->id : $parenting->id;
            Tool::query()->create([
                'tool_category_id' => $categoryId,
                'name' => $tool['name'],
                'slug' => $tool['slug'],
                'module_key' => $tool['module_key'],
                'description' => $tool['description'],
                'status' => 'active',
                'route_name' => $tool['route'],
                'is_implemented' => true,
                'display_order' => $tool['display_order'],
            ]);
        }
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'PS Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
