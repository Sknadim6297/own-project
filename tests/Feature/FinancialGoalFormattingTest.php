<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 34 — Financial Goal Calculator premium UI formatting.
 */
class FinancialGoalFormattingTest extends TestCase
{
    private const ROUTE = 'tools.pfm.financial-goal';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
            \App\Http\Middleware\EnsureCustomerTermsAccepted::class,
        ]);
        $this->storage();
    }

    public function test_form_has_section_hierarchy_currency_and_percent_affixes(): void
    {
        $html = $this->actingAs($this->member('fg-format@example.com'), 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('yk-goal__section', false)
            ->assertSee('goal-section-details', false)
            ->assertSee('goal-section-cost', false)
            ->assertSee('goal-section-duration', false)
            ->assertSee('goal-section-savings', false)
            ->assertSee('goal-section-assumptions', false)
            ->assertSee('yk-goal__control--money', false)
            ->assertSee('yk-goal__control--percent', false)
            ->assertSee('yk-goal__years-head', false)
            ->assertSee('data-yk-icon="target"', false)
            ->assertSee('data-yk-icon="wallet"', false)
            ->assertSee('data-yk-icon="calendar-days"', false)
            ->assertSee('data-yk-icon="activity"', false)
            ->getContent();

        $this->assertGreaterThanOrEqual(1, substr_count($html, 'name="goal_type"'));
        $this->assertSame(1, substr_count($html, 'name="current_cost"'));
        $this->assertSame(1, substr_count($html, 'name="years"'));
        $this->assertStringContainsString('name="current_cost"', $html);
        $this->assertStringContainsString('name="years"', $html);
        $this->assertStringContainsString('name="existing_savings"', $html);
        $this->assertStringContainsString('name="inflation_percent"', $html);
        $this->assertStringContainsString('name="expected_return_percent"', $html);
        $this->assertStringContainsString('name="goal_name"', $html);

        // Currency ₹ and % suffixes remain presentation-only (inputs stay type=number).
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'yk-goal__control--money'));
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'yk-goal__control--percent'));
        $this->assertStringContainsString('>₹</span>', $html);
        $this->assertStringContainsString('>%</span>', $html);

        // Years slider still present (Task 33) and full-width section (not squeezed in a two-col with savings).
        $this->assertStringContainsString('data-yk-goal-years', $html);
        $this->assertStringContainsString('data-goal-years-slider', $html);
        $this->assertStringContainsString('yk-goal--compact-header', $html);

        $css = file_get_contents(public_path('frontend/assets/css/yuktione-pfm-goal.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('min-height: 48px', $css);
        $this->assertStringContainsString('.yk-goal__section-title', $css);
        $this->assertStringContainsString('.yk-goal__years-head', $css);
        $this->assertStringContainsString('--g-green: #8a0f4d', $css);
        $this->assertStringContainsString('--g-paper: #fff8fb', $css);
        $this->assertStringContainsString('--g-mint: #f8eaf1', $css);
    }

    public function test_calculate_results_preserve_identical_financial_outputs(): void
    {
        $user = $this->member('fg-format-calc@example.com');
        $payload = [
            'goal_type' => 'home',
            'goal_name' => 'Dream flat',
            'current_cost' => 2500000,
            'years' => 10,
            'existing_savings' => 300000,
            'inflation_percent' => 6,
            'expected_return_percent' => 10,
        ];

        $engine = app(PfmCalculator::class);
        $computed = $engine->calculate('financial-goal-calculator', $payload);
        $expected = $computed['result'];

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $draft = session('pfm.draft.financial-goal-calculator');
        $this->assertIsArray($draft);
        $this->assertIsArray($draft['result'] ?? null);
        $this->assertEqualsWithDelta((float) $expected['monthly_investment'], (float) $draft['result']['monthly_investment'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['required_corpus'], (float) $draft['result']['required_corpus'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['funding_gap'], (float) $draft['result']['funding_gap'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['future_value_existing'], (float) $draft['result']['future_value_existing'], 0.01);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Required monthly investment')
            ->assertSee('Your Financial Goal Plan')
            ->assertSee('Save result')
            ->assertSee('Edit &amp; recalculate', false)
            ->assertSee('Assumptions you used')
            ->getContent();

        $this->assertStringContainsString('yk-goalr__kpi', $html);
        $this->assertStringContainsString('yk-goalr__figure', $html);
        $this->assertStringContainsString('₹', $html);
        $this->assertStringContainsString($this->inrWhole((float) $expected['monthly_investment']), $html);
        $this->assertStringContainsString($this->inrWhole((float) $expected['required_corpus']), $html);
    }

    private function inrWhole(float $value): string
    {
        $n = (string) (int) round(abs($value));
        if (strlen($n) > 3) {
            $head = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($n, 0, -3));
            $n = $head.','.substr($n, -3);
        }

        return ($value < 0 ? '-' : '').'₹'.$n;
    }

    public function test_other_pfm_calculators_are_unaffected_by_goal_formatting_css(): void
    {
        foreach ([
            'tools.pfm.emergency-fund',
            'tools.pfm.money-planner',
            'tools.pfm.net-worth',
        ] as $route) {
            $html = $this->actingAs($this->member('fg-format-other-'.md5($route).'@example.com'), 'user')
                ->get(route($route))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('yuktione-pfm.css', $html);
            $this->assertStringNotContainsString('yuktione-pfm-goal.css', $html);
            $this->assertStringNotContainsString('yk-goal__section-title', $html);
        }
    }

    private function storage(): void
    {
        foreach (['pfm_calculations', 'tools', 'tool_categories', 'customer_profiles', 'users'] as $table) {
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
        Schema::create('pfm_calculations', function (Blueprint $table) {
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

        $category = ToolCategory::query()->create([
            'name' => 'Money & Finance',
            'slug' => 'money-finance',
            'is_active' => true,
            'display_order' => 5,
        ]);

        foreach (PfmCatalog::TOOLS as $tool) {
            Tool::query()->create([
                'tool_category_id' => $category->id,
                'name' => $tool['name'],
                'slug' => $tool['slug'],
                'module_key' => PfmCatalog::MODULE_KEY,
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
            'name' => 'FG Format Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
