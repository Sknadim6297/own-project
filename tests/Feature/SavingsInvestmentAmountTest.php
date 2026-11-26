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
 * Task 48 — Savings & Investment Calculator investment amount UI.
 */
class SavingsInvestmentAmountTest extends TestCase
{
    private const ROUTE = 'tools.pfm.savings-investment';

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

    public function test_form_renders_investment_amount_ui(): void
    {
        $html = $this->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('data-yk-si-amount', false)
            ->assertSee('data-yk-si-form', false)
            ->assertSee('Investment amount')
            ->assertSee('Investment type')
            ->assertSee('Lump Sum')
            ->assertSee('Recurring Investment')
            ->assertSee('Combined Investment')
            ->assertSee('Contribution preview')
            ->assertSee('Total invested (planned)')
            ->assertSee('excludes estimated investment returns', false)
            ->assertSee('name="initial_amount"', false)
            ->assertSee('name="contribution_amount"', false)
            ->assertSee('name="monthly_contribution"', false)
            ->assertSee('name="investment_frequency"', false)
            ->assertSee('data-yk-si-preset="initial"', false)
            ->assertSee('data-yk-si-preset="monthly"', false)
            ->getContent();

        $this->assertStringContainsString('₹', $html);
        $this->assertStringContainsString('data-yk-si-preset-value="10000"', $html);
        $this->assertStringContainsString('data-yk-si-preset-value="500000"', $html);
        $this->assertStringContainsString('data-yk-si-preset-value="1000"', $html);
        $this->assertStringContainsString('data-yk-si-preset-value="10000"', $html);
    }

    public function test_lump_sum_scenario_excludes_monthly_contribution(): void
    {
        $payload = $this->payload([
            'initial_amount' => 100000,
            'monthly_contribution' => 0,
            'years' => 10,
            'expected_return_percent' => 10,
        ]);
        $expected = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee(number_format((float) $expected['corpus'], 2), false)
            ->assertSee(number_format((float) $expected['invested'], 2), false)
            ->assertSee('Your Investment Results')
            ->assertSee('lump sum', false);

        $this->assertSame(100000.0, (float) $expected['invested']);
        $this->assertSame(0, PfmCalculation::query()->count());
    }

    public function test_monthly_only_scenario_excludes_initial_amount(): void
    {
        $payload = $this->payload([
            'initial_amount' => 0,
            'monthly_contribution' => 5000,
            'years' => 10,
            'expected_return_percent' => 10,
        ]);
        $expected = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $html = $this->get(route(self::ROUTE.'.results'))->assertOk()->getContent();
        $this->assertStringContainsString(number_format((float) $expected['corpus'], 2), $html);
        $this->assertStringContainsString(number_format((float) $expected['invested'], 2), $html);
        $this->assertStringContainsString('every month', $html);
        $this->assertSame(600000.0, (float) $expected['invested']);
    }

    public function test_combined_baseline_financial_outputs_unchanged(): void
    {
        $payload = $this->payload([
            'initial_amount' => 100000,
            'monthly_contribution' => 5000,
            'years' => 10,
            'expected_return_percent' => 10,
        ]);

        $expected = app(PfmCalculator::class)->calculate('savings-investment-calculator', $payload)['result'];
        $this->assertEqualsWithDelta(700000.0, (float) $expected['invested'], 0.001);
        $this->assertEqualsWithDelta(1294929.04, (float) $expected['corpus'], 0.001);
        $this->assertEqualsWithDelta(594929.04, (float) $expected['gains'], 0.001);

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('1,294,929.04', false)
            ->assertSee('700,000.00', false)
            ->assertSee('Your Investment Results')
            ->assertSee('initial amount', false);
    }

    public function test_negative_and_both_zero_amounts_are_rejected(): void
    {
        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'initial_amount' => -1000,
                'monthly_contribution' => 0,
            ]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');

        $this->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'initial_amount' => 0,
                'monthly_contribution' => 0,
            ]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');
    }

    public function test_draft_restoration_infers_investment_type(): void
    {
        $user = $this->member('si48-draft@example.com');

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'initial_amount' => 250000,
                'monthly_contribution' => 0,
            ]))
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));

        // Existing SI recalculate forgets draft; form should still render type controls.
        $blank = $this->actingAs($user, 'user')->get(route(self::ROUTE))->assertOk()->getContent();
        $this->assertStringContainsString('data-yk-si-amount', $blank);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'initial_amount' => 0,
                'monthly_contribution' => 5000,
            ]));

        // With input errors, old() restores values and type inference uses them.
        $this->actingAs($user, 'user')
            ->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), $this->payload([
                'initial_amount' => 0,
                'monthly_contribution' => 5000,
                'years' => -1,
            ]))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors('calculate');

        $restored = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('data-yk-si-type="monthly"', false)
            ->assertSee('value="5000"', false)
            ->getContent();

        $this->assertStringContainsString('name="monthly_contribution"', $restored);
    }

    public function test_calculate_does_not_save_and_explicit_save_works(): void
    {
        $user = $this->member('si48-save@example.com');
        $payload = $this->payload();

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.save'))
            ->assertRedirect();

        $saved = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($saved);
        $this->assertSame('savings-investment-calculator', $saved->tool_key);
        $this->assertEquals(100000, (float) ($saved->inputs['initial_amount'] ?? 0));
        $this->assertEquals(5000, (float) ($saved->inputs['monthly_contribution'] ?? 0));

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history'))
            ->assertOk()
            ->assertSee((string) $saved->id);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $saved))
            ->assertOk()
            ->assertSee('Your Investment Results')
            ->assertSee('1,294,929.04', false);
    }

    public function test_extra_ui_type_field_does_not_change_engine_outputs(): void
    {
        $base = $this->payload();
        $withUi = array_merge($base, ['si_investment_type_ui' => 'combined']);

        $a = app(PfmCalculator::class)->calculate('savings-investment-calculator', $base)['result'];
        $b = app(PfmCalculator::class)->calculate('savings-investment-calculator', $withUi)['result'];

        $this->assertSame($a['corpus'], $b['corpus']);
        $this->assertSame($a['invested'], $b['invested']);
        $this->assertSame($a['gains'], $b['gains']);
    }

    public function test_task_47_summary_and_other_calculators_unaffected(): void
    {
        $this->post(route(self::ROUTE.'.calculate'), $this->payload())
            ->assertRedirect(route(self::ROUTE.'.results'));
        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Your Investment Results')
            ->assertSee('Your Investment Conclusion')
            ->assertSee('Assumptions used');

        $user = $this->member('si48-other@example.com');
        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.inflation.calculate'), [
                'current_amount' => 100000,
                'inflation_percent' => 6,
                'years' => 10,
            ])
            ->assertRedirect(route('tools.pfm.inflation.results'));

        $inf = $this->actingAs($user, 'user')->get(route('tools.pfm.inflation.results'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-yk-si-amount', $inf);
        $this->assertStringNotContainsString('Investment type', $inf);
    }

    /**
     * @param  array<string, float|int|string>  $overrides
     * @return array<string, float|int|string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'initial_amount' => 100000,
            'monthly_contribution' => 5000,
            'expected_return_percent' => 10,
            'years' => 10,
        ], $overrides);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'SI Amount Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
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
}
