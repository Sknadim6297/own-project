<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\DebtEmiPresentation;
use App\Support\Pfm\PfmCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Task 43 — Debt & EMI dedicated explanatory result screen.
 */
class DebtEmiExplanatoryResultTest extends TestCase
{
    private const ROUTE = 'tools.pfm.debt-emi';

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

    public function test_dedicated_result_screen_renders_without_input_form(): void
    {
        $user = $this->member('debt-r43@example.com');
        $payload = $this->payload();

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $response = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('data-yk-debt-results', false)
            ->assertSee('Your Loan Repayment Plan')
            ->assertSee('Standard monthly EMI')
            ->assertSee('Loan amount breakdown')
            ->assertSee('Principal versus interest')
            ->assertSee('Standard repayment summary')
            ->assertSee('What this means')
            ->assertSee('Save result')
            ->assertSee('Edit &amp; recalculate', false)
            ->assertSee('New Test')
            ->assertSee('Back to calculator')
            ->assertSee('View history')
            ->assertDontSee('name="principal"', false)
            ->assertDontSee('name="down_payment"', false)
            ->assertDontSee('data-yk-debt-single', false)
            ->assertDontSee('data-pfm-form', false)
            ->assertDontSee('data-pfm-submit', false);

        $this->assertStringContainsString('private', strtolower((string) $response->headers->get('Cache-Control')));
        $this->assertStringContainsString('no-store', strtolower((string) $response->headers->get('Cache-Control')));

        $html = $response->getContent();
        $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', $payload)['result'];
        $this->assertStringContainsString(number_format((float) $expected['emi'], 2), $html);
        $this->assertStringContainsString('Total purchase amount', $html);
        $this->assertStringContainsString('Actual financed loan amount', $html);
        $this->assertStringContainsString('Total loan repayment', $html);
        $this->assertStringContainsString('Total purchase-related outflow', $html);
        $this->assertStringContainsString('Financed principal + loan interest', $html);
        $this->assertStringContainsString('Down payment + total loan repayment', $html);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_partial_down_payment_labels_and_engine_outputs(): void
    {
        $user = $this->member('debt-r43-partial@example.com');
        $payload = $this->payload([
            'down_payment' => 100000,
            'extra_payment' => 0,
        ]);

        $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', $payload)['result'];
        $this->assertEqualsWithDelta(8498.82, (float) $expected['emi'], 0.05);
        $this->assertEqualsWithDelta(400000, (float) $expected['financed_principal'], 0.01);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('data-debtr-scenario="partial_down_payment"', false)
            ->getContent();

        $this->assertStringContainsString(number_format((float) $expected['emi'], 2), $html);
        $this->assertStringContainsString(number_format((float) $expected['financed_principal'], 2), $html);
        $this->assertStringContainsString(number_format((float) $expected['standard_interest'], 2), $html);
        $this->assertStringContainsString(number_format((float) $expected['loan_repayment_total'], 2), $html);
        $this->assertStringContainsString(number_format((float) $expected['total_purchase_outflow'], 2), $html);
        $this->assertStringContainsString('After paying', $html);
        $this->assertStringNotContainsString('Additional repayment comparison', $html);
    }

    public function test_extra_repayment_comparison_and_explanation(): void
    {
        $user = $this->member('debt-r43-extra@example.com');
        $payload = $this->payload([
            'down_payment' => 100000,
            'extra_payment' => 2000,
        ]);
        $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', $payload)['result'];

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Additional repayment comparison')
            ->assertSee('Interest saved')
            ->assertSee('Planned combined monthly payment')
            ->getContent();

        $this->assertStringContainsString(number_format((float) $expected['interest_saved'], 2), $html);
        $this->assertStringContainsString('By paying an additional', $html);
        $this->assertStringContainsString('data-debtr-scenario="extra_repayment"', $html);
    }

    public function test_full_down_payment_no_loan_result(): void
    {
        $user = $this->member('debt-r43-full@example.com');
        $payload = $this->payload([
            'down_payment' => 500000,
            'extra_payment' => 0,
        ]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('No loan is required')
            ->assertSee('No borrowing')
            ->assertDontSee('Additional repayment comparison')
            ->getContent();

        $this->assertStringContainsString('data-debtr-scenario="full_down_payment"', $html);
        $this->assertStringContainsString('covers the entire purchase amount', $html);
    }

    public function test_zero_interest_explanation(): void
    {
        $user = $this->member('debt-r43-zero@example.com');
        $payload = $this->payload([
            'down_payment' => 100000,
            'annual_rate_percent' => 0,
            'extra_payment' => 0,
        ]);
        $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', $payload)['result'];
        $this->assertEqualsWithDelta(6666.67, (float) $expected['emi'], 0.05);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('without additional interest', $html);
        $this->assertStringContainsString(number_format((float) $expected['emi'], 2), $html);
    }

    public function test_recalculate_restores_five_inputs_without_double_deduct(): void
    {
        $user = $this->member('debt-r43-recalc@example.com');
        $payload = $this->payload([
            'down_payment' => 100000,
            'extra_payment' => 2000,
            'annual_rate_percent' => 10.5,
            'tenure_years' => 4,
        ]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHas('status');

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('data-yk-debt-single', false)
            ->getContent();

        $this->assertStringContainsString('value="500000"', $html);
        $this->assertStringContainsString('value="100000"', $html);
        $this->assertStringContainsString('value="2000"', $html);
        $this->assertMatchesRegularExpression('/id="pfm-rate"[^>]*value="10\.5"|name="annual_rate_percent"[^>]*value="10\.5"/', $html);
        $this->assertMatchesRegularExpression('/name="tenure_years"[^>]*value="4(\.0)?"|id="pfm-tenure"[^>]*value="4(\.0)?"/', $html);

        // Re-calc must still finance 400000 (not deduct down payment twice).
        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $draft = session('pfm.draft.debt-emi-calculator');
        $this->assertEqualsWithDelta(400000, (float) ($draft['result']['financed_principal'] ?? 0), 0.01);
        $this->assertEqualsWithDelta(100000, (float) ($draft['normalized']['down_payment'] ?? 0), 0.01);
        $this->assertEqualsWithDelta(500000, (float) ($draft['normalized']['principal'] ?? 0), 0.01);
    }

    public function test_save_history_new_test_and_ownership(): void
    {
        $user = $this->member('debt-r43-save@example.com');
        $other = $this->member('debt-r43-other@example.com');
        $payload = $this->payload(['down_payment' => 100000]);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.calculate'), $payload);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.save'))->assertRedirect();
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());

        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertSame(100000.0, (float) ($record->inputs['down_payment'] ?? -1));
        $this->assertSame(400000.0, (float) ($record->outputs['financed_principal'] ?? -1));

        $show = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk()
            ->assertSee('data-yk-debt-saved', false)
            ->assertSee('Your Loan Repayment Plan')
            ->assertSee('read-only')
            ->getContent();
        $this->assertStringContainsString(number_format((float) $record->outputs['emi'], 2), $show);

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
        $this->assertMatchesRegularExpression('/id="pfm-principal"[^>]*value=""/', $fresh);
        $this->assertMatchesRegularExpression('/id="pfm-down-payment"[^>]*value="0"/', $fresh);
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_historical_record_without_down_payment_stays_readable(): void
    {
        $user = $this->member('debt-r43-legacy@example.com');
        $legacy = PfmCalculation::query()->create([
            'user_id' => $user->id,
            'tool_key' => 'debt-emi-calculator',
            'title' => 'Legacy debt',
            'inputs' => [
                'principal' => 200000,
                'annual_rate_percent' => 9,
                'tenure_years' => 2,
                'extra_payment' => 0,
            ],
            'outputs' => [
                'emi' => 9129.0,
                'standard_interest' => 19096.0,
                'standard_total' => 219096.0,
                'interest_saved' => 0,
                'months_saved' => 0,
                'summary' => 'Legacy snapshot',
            ],
            'summary' => 'Legacy snapshot',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) Str::uuid(),
            'is_draft' => false,
        ]);

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $legacy))
            ->assertOk()
            ->assertSee('Your Loan Repayment Plan')
            ->getContent();

        $this->assertStringContainsString('9,129.00', $html);
        $this->assertStringContainsString('200,000.00', $html);
        // Must not invent a nonzero down payment for legacy snapshots.
        $view = DebtEmiPresentation::build($legacy->inputs, $legacy->outputs);
        $this->assertSame(0.0, (float) $view['down_payment']);
        $this->assertEqualsWithDelta(200000, (float) $view['financed_principal'], 0.01);
    }

    public function test_missing_historical_fields_show_unavailable_not_invented(): void
    {
        $view = DebtEmiPresentation::build(
            ['principal' => 100000, 'annual_rate_percent' => 10, 'tenure_years' => 5],
            ['emi' => 2125.0, 'summary' => 'Sparse']
        );

        $this->assertSame('Unavailable', $view['money']['standard_interest']);
        $this->assertSame('₹2,125.00', $view['money']['emi']);
        $this->assertFalse($view['show_chart']);
    }

    public function test_guest_preview_and_save_redirects_to_login(): void
    {
        $payload = $this->payload(['down_payment' => 50000]);

        $this->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Your Loan Repayment Plan')
            ->assertSee('Save result (sign in)')
            ->assertDontSee('name="principal"', false);

        $this->post(route(self::ROUTE.'.save'))
            ->assertRedirect(route('login'));
    }

    public function test_api_contract_unchanged_for_legacy_request(): void
    {
        $response = $this->postJson('/api/v1/pfm/debt-emi/calculate', [
            'principal' => 500000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ]);

        if ($response->status() === 403) {
            $this->markTestSkipped('Debt EMI API tool closed in this environment.');
        }

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertEqualsWithDelta(10623.52, (float) data_get($response->json(), 'result.emi'), 0.05);
    }

    public function test_other_pfm_tools_keep_their_result_views(): void
    {
        $user = $this->member('debt-r43-other-tools@example.com');

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.emergency-fund.calculate'), [
                'monthly_expenses' => 25000,
                'coverage_months' => 6,
                'current_savings' => 50000,
                'monthly_saving_capacity' => 10000,
            ])
            ->assertRedirect(route('tools.pfm.emergency-fund.results'));

        $ef = $this->actingAs($user, 'user')
            ->get(route('tools.pfm.emergency-fund.results'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-yk-debt-results', $ef);
        $this->assertStringNotContainsString('Your Loan Repayment Plan', $ef);

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.financial-goal.calculate'), [
                'goal_type' => 'home',
                'goal_name' => 'Flat',
                'current_cost' => 2500000,
                'years' => 10,
                'inflation_percent' => 6,
                'expected_return_percent' => 10,
                'existing_savings' => 300000,
            ])
            ->assertRedirect(route('tools.pfm.financial-goal.results'));

        $this->actingAs($user, 'user')
            ->get(route('tools.pfm.financial-goal.results'))
            ->assertOk()
            ->assertSee('data-yk-goal-results', false)
            ->assertDontSee('data-yk-debt-results', false);
    }

    public function test_presentation_chart_excludes_down_payment_from_loan_mix(): void
    {
        $payload = app(PfmCalculator::class)->calculate('debt-emi-calculator', $this->payload([
            'down_payment' => 100000,
        ]));
        $view = DebtEmiPresentation::build($payload['normalized'], $payload['result']);

        $this->assertEqualsWithDelta(400000, (float) $view['financed_principal'], 0.01);
        $this->assertEqualsWithDelta(
            (float) $view['principal_percent'] + (float) $view['interest_percent'],
            100.0,
            0.2
        );
        $this->assertTrue($view['show_chart']);
        $loanRepay = (float) $view['loan_repayment_total'];
        $outflow = (float) $view['total_purchase_outflow'];
        $this->assertEqualsWithDelta($outflow, 100000 + $loanRepay, 0.05);
    }

    /**
     * @param  array<string, float|int|string>  $overrides
     * @return array<string, float|int|string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'principal' => 500000,
            'down_payment' => 0,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ], $overrides);
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Debt Result Member',
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
