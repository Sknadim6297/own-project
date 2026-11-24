<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 36 — Financial Goal dedicated result screen.
 */
class FinancialGoalSeparateResultScreenTest extends TestCase
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

    public function test_calculate_opens_dedicated_result_without_input_form(): void
    {
        $user = $this->member('fg-result@example.com');
        $payload = $this->payload();

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $response = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('data-yk-goal-results', false)
            ->assertSee('Your Financial Goal Plan')
            ->assertSee('Required monthly investment')
            ->assertSee('Financial breakdown')
            ->assertSee('Save result')
            ->assertSee('Edit &amp; recalculate', false)
            ->assertSee('New Test')
            ->assertSee('Back to calculator')
            ->assertSee('View history')
            ->assertDontSee('data-yk-goal-single', false)
            ->assertDontSee('name="current_cost"', false)
            ->assertDontSee('data-goal-years-slider', false)
            ->assertDontSee('data-pfm-form', false);

        $this->assertStringContainsString('private', strtolower((string) $response->headers->get('Cache-Control')));
        $this->assertStringContainsString('no-store', strtolower((string) $response->headers->get('Cache-Control')));

        $html = $response->getContent();
        $expected = app(PfmCalculator::class)->calculate('financial-goal-calculator', $payload)['result'];
        $this->assertStringContainsString($this->inrWhole((float) $expected['monthly_investment']), $html);
        $this->assertStringContainsString($this->inrWhole((float) $expected['required_corpus']), $html);
        $this->assertStringContainsString('Dream flat', $html);
        $this->assertStringContainsString('10 years', $html);
        $this->assertStringContainsString('How your goal gets funded', $html);
        $this->assertStringContainsString('Savings growth vs. goal cost', $html);

        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_missing_result_draft_redirects_to_calculator(): void
    {
        $this->actingAs($this->member('fg-result-miss@example.com'), 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertRedirect(route(self::ROUTE));
    }

    public function test_recalculate_restores_all_seven_inputs_on_single_page_form(): void
    {
        $user = $this->member('fg-result-recalc@example.com');
        $payload = $this->payload([
            'goal_type' => 'education',
            'goal_name' => 'MBA fund',
            'years' => 8,
            'existing_savings' => 120000,
            'inflation_percent' => 5.5,
            'expected_return_percent' => 9.25,
        ]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHas('status');

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('yk-goal--single', false)
            ->getContent();

        $this->assertStringContainsString('value="MBA fund"', $html);
        $this->assertStringContainsString('value="2500000"', $html);
        $this->assertMatchesRegularExpression('/id="pfm-years"[^>]*value="8(\.0)?"/', $html);
        $this->assertStringContainsString('value="120000"', $html);
        $this->assertMatchesRegularExpression('/id="pfm-inflation"[^>]*value="5\.5"/', $html);
        $this->assertMatchesRegularExpression('/id="pfm-return"[^>]*value="9\.25"/', $html);
        $this->assertMatchesRegularExpression('/value="education"[^>]*checked|checked[^>]*value="education"/', $html);
        $this->assertStringNotContainsString('data-pfm-wizard', $html);
    }

    public function test_save_history_new_test_and_ownership(): void
    {
        $user = $this->member('fg-result-save@example.com');
        $other = $this->member('fg-result-other@example.com');
        $payload = $this->payload();

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.save'))->assertRedirect();

        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertSame(10.0, (float) ($record->inputs['years'] ?? 0));

        // Duplicate save protection / second save should not explode (may create another or redirect)
        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('read-only');

        $this->actingAs($other, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertStatus(403);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.new-test'))
            ->assertRedirect(route(self::ROUTE));

        $fresh = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="pfm-years"[^>]*value=""/', $fresh);
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_guest_can_view_result_preview_and_save_redirects_to_login(): void
    {
        $payload = $this->payload(['goal_name' => 'Guest goal']);

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Your Financial Goal Plan')
            ->assertSee('Save result (sign in)')
            ->assertDontSee('name="current_cost"', false);

        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route('login'));
    }

    public function test_other_pfm_tools_keep_their_result_views(): void
    {
        $user = $this->member('fg-result-other-tools@example.com');
        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.emergency-fund.calculate'), [
                'monthly_expenses' => 25000,
                'coverage_months' => 6,
                'current_savings' => 50000,
                'monthly_saving_capacity' => 10000,
            ])
            ->assertRedirect(route('tools.pfm.emergency-fund.results'));

        $html = $this->actingAs($user, 'user')
            ->get(route('tools.pfm.emergency-fund.results'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-yk-goal-results', $html);
        $this->assertStringNotContainsString('Your Financial Goal Plan', $html);
        $this->assertStringNotContainsString('yuktione-pfm-goal.css', $html);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'goal_type' => 'home',
            'goal_name' => 'Dream flat',
            'current_cost' => 2500000,
            'years' => 10,
            'existing_savings' => 300000,
            'inflation_percent' => 6,
            'expected_return_percent' => 10,
        ], $overrides);
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
            'name' => 'FG Result Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
