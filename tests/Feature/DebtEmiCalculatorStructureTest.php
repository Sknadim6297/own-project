<?php

namespace Tests\Feature;

use App\Models\PfmCalculation;
use App\Models\Tool;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\Pfm\PfmCalculator;
use App\Support\Pfm\PfmCatalog;
use App\Support\Tools\ToolPageLeads;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 40 — Debt & EMI Calculator structural improvements (no formula / field changes).
 */
class DebtEmiCalculatorStructureTest extends TestCase
{
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

    public function test_route_compact_header_and_short_description_remain(): void
    {
        $html = $this->get(route('tools.pfm.debt-emi'))
            ->assertOk()
            ->assertSee('Debt &amp; EMI Calculator', false)
            ->assertSee('data-yk-tool-shell-ref="debt-emi"', false)
            ->assertSee('data-yk-debt-structure="1"', false)
            ->assertSee('yk-pfm--debt', false)
            ->assertSee('yk-pfm__hero--compact', false)
            ->assertSee('data-yk-tool-page-lead', false)
            ->assertSee(ToolPageLeads::for('debt-emi-calculator'))
            ->assertSee('not financial advice')
            ->getContent();

        $this->assertStringNotContainsString('id="pfmHeroBar"', $html);
        $this->assertStringNotContainsString('yk-pfm__hero-art', $html);
        $this->assertStringContainsString('data-yk-debt-fields', $html);
        $this->assertStringContainsString('Loan details', $html);
        $this->assertStringContainsString('Extra monthly payment', $html);
    }

    public function test_existing_financial_inputs_and_defaults_remain_unchanged(): void
    {
        $html = $this->get(route('tools.pfm.debt-emi'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="principal"[^>]*min="0\.01"/', $html);
        $this->assertMatchesRegularExpression('/name="annual_rate_percent"[^>]*min="0"[^>]*max="50"/', $html);
        $this->assertMatchesRegularExpression('/name="tenure_years"[^>]*min="0\.01"[^>]*max="100"/', $html);
        $this->assertMatchesRegularExpression('/name="extra_payment"[^>]*min="0"/', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('yk-debt__affix', $html);
        $this->assertStringContainsString('years', $html);
        $this->assertStringContainsString('%', $html);
        $this->assertStringContainsString('₹', $html);

        // Wizard steps preserved (Task 41 single-page deferred).
        $this->assertStringContainsString('data-pfm-wizard="1"', $html);
        $this->assertStringContainsString('data-pfm-step="0"', $html);
        $this->assertStringContainsString('data-pfm-step="1"', $html);
        $this->assertStringContainsString('data-pfm-next', $html);
        $this->assertStringContainsString('data-pfm-submit', $html);
    }

    public function test_calculate_outputs_match_engine_and_save_history_ownership(): void
    {
        $input = [
            'principal' => 2000000,
            'annual_rate_percent' => 9,
            'tenure_years' => 15,
            'extra_payment' => 5000,
        ];
        $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', $input);
        $user = $this->member('t40-debt@example.com');

        $this->actingAs($user, 'user')
            ->post(route('tools.pfm.debt-emi.calculate'), $input)
            ->assertRedirect(route('tools.pfm.debt-emi.results'));

        $results = $this->actingAs($user, 'user')
            ->get(route('tools.pfm.debt-emi.results'))
            ->assertOk()
            ->assertSee('EMI')
            ->assertSee(number_format($expected['result']['emi'], 2))
            ->assertSee(number_format($expected['result']['standard_interest'], 2))
            ->assertSee((string) (int) $expected['result']['months_saved'])
            ->getContent();

        $this->assertStringNotContainsString('NaN', $results);

        $this->actingAs($user, 'user')->post(route('tools.pfm.debt-emi.save'))->assertRedirect();

        $record = PfmCalculation::query()
            ->where('user_id', $user->id)
            ->where('tool_key', 'debt-emi-calculator')
            ->first();
        $this->assertNotNull($record);
        $this->assertSame(2000000.0, (float) $record->inputs['principal']);
        $this->assertSame(9.0, (float) $record->inputs['annual_rate_percent']);
        $this->assertSame(15.0, (float) $record->inputs['tenure_years']);
        $this->assertSame(5000.0, (float) $record->inputs['extra_payment']);
        $this->assertEqualsWithDelta($expected['result']['emi'], (float) $record->outputs['emi'], 0.011);

        $other = $this->member('t40-other@example.com');
        $this->actingAs($other, 'user')
            ->get(route('tools.pfm.debt-emi.history.show', $record))
            ->assertForbidden();

        $this->actingAs($user, 'user')
            ->get(route('tools.pfm.debt-emi.history'))
            ->assertOk();

        $this->actingAs($user, 'user')
            ->get(route('tools.pfm.debt-emi.history.show', $record))
            ->assertOk()
            ->assertSee(number_format($expected['result']['emi'], 2));
    }

    public function test_validation_and_draft_restoration_preserve_field_names(): void
    {
        $this->post(route('tools.pfm.debt-emi.calculate'), [
            'principal' => '',
            'annual_rate_percent' => 9,
            'tenure_years' => 15,
            'extra_payment' => 0,
        ])->assertRedirect();

        $html = $this->get(route('tools.pfm.debt-emi'))
            ->assertOk()
            ->assertSee('name="principal"', false)
            ->assertSee('value="9"', false)
            ->assertSee('value="15"', false)
            ->getContent();

        $this->assertStringContainsString('yk-pfm__error', $html);
    }

    public function test_zero_interest_and_decimal_rate_still_work(): void
    {
        $zero = app(PfmCalculator::class)->calculate('debt-emi-calculator', [
            'principal' => 120000,
            'annual_rate_percent' => 0,
            'tenure_years' => 1,
            'extra_payment' => 0,
        ]);
        $this->assertSame(10000.0, $zero['result']['emi']);

        $this->post(route('tools.pfm.debt-emi.calculate'), [
            'principal' => 500000,
            'annual_rate_percent' => 8.75,
            'tenure_years' => 7.5,
            'extra_payment' => 0,
        ])->assertRedirect(route('tools.pfm.debt-emi.results'));

        $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', [
            'principal' => 500000,
            'annual_rate_percent' => 8.75,
            'tenure_years' => 7.5,
            'extra_payment' => 0,
        ]);

        $this->get(route('tools.pfm.debt-emi.results'))
            ->assertOk()
            ->assertSee(number_format($expected['result']['emi'], 2));
    }

    public function test_other_pfm_tools_are_not_wrapped_in_debt_structure(): void
    {
        foreach ([
            'tools.pfm.money-planner',
            'tools.pfm.net-worth',
            'tools.pfm.emergency-fund',
            'tools.pfm.retirement',
            'tools.pfm.inflation',
            'tools.pfm.savings-investment',
        ] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertStringNotContainsString('yk-pfm--debt', $html);
            $this->assertStringNotContainsString('data-yk-debt-structure', $html);
            $this->assertStringNotContainsString('data-yk-debt-fields', $html);
        }

        $fg = $this->get(route('tools.pfm.financial-goal'))->assertOk()->getContent();
        $this->assertStringNotContainsString('yk-pfm--debt', $fg);
        $this->assertStringContainsString('data-yk-goal-compact-header', $fg);
        $this->assertStringContainsString('yk-goal--single', $fg);
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
            'name' => 'Task 40 Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
