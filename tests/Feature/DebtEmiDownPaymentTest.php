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
 * Task 42 — Debt & EMI optional down payment (backward-compatible principal key).
 */
class DebtEmiDownPaymentTest extends TestCase
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

    public function test_down_payment_field_renders_on_single_page(): void
    {
        $html = $this->actingAs($this->member('debt-dp@example.com'), 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('name="down_payment"', false)
            ->assertSee('data-yk-debt-down-payment', false)
            ->assertSee('data-yk-debt-financed-preview', false)
            ->assertSee('Total purchase amount')
            ->assertSee('Down payment')
            ->assertSee('Actual loan amount')
            ->assertDontSee('data-pfm-wizard', false)
            ->getContent();

        foreach (['principal', 'down_payment', 'annual_rate_percent', 'tenure_years', 'extra_payment'] as $name) {
            $this->assertStringContainsString('name="'.$name.'"', $html);
        }

        $this->assertMatchesRegularExpression('/id="pfm-down-payment"[^>]*value="0"/', $html);
    }

    public function test_missing_or_zero_down_payment_matches_legacy_engine(): void
    {
        $calculator = app(PfmCalculator::class);
        $legacy = [
            'principal' => 500000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ];

        $withoutKey = $calculator->calculate('debt-emi-calculator', $legacy);
        $withZero = $calculator->calculate('debt-emi-calculator', $legacy + ['down_payment' => 0]);
        $withEmpty = $calculator->calculate('debt-emi-calculator', $legacy + ['down_payment' => '']);

        $this->assertEqualsWithDelta($withoutKey['result']['emi'], $withZero['result']['emi'], 0.01);
        $this->assertEqualsWithDelta($withoutKey['result']['emi'], $withEmpty['result']['emi'], 0.01);
        $this->assertEqualsWithDelta(10623.52, $withoutKey['result']['emi'], 0.05);
        $this->assertSame(500000.0, (float) $withoutKey['normalized']['financed_principal']);
        $this->assertSame(0.0, (float) $withoutKey['normalized']['down_payment']);

        $user = $this->member('debt-dp-legacy@example.com');
        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $legacy)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $draft = session('pfm.draft.debt-emi-calculator');
        $this->assertEqualsWithDelta($withoutKey['result']['emi'], (float) $draft['result']['emi'], 0.01);
        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_partial_down_payment_reduces_financed_principal_and_emi(): void
    {
        $calculator = app(PfmCalculator::class);
        $payload = [
            'principal' => 500000,
            'down_payment' => 100000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ];

        $direct = $calculator->calculate('debt-emi-calculator', [
            'principal' => 400000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ]);
        $withDown = $calculator->calculate('debt-emi-calculator', $payload);

        $this->assertSame(400000.0, (float) $withDown['normalized']['financed_principal']);
        $this->assertEqualsWithDelta($direct['result']['emi'], $withDown['result']['emi'], 0.01);
        $this->assertEqualsWithDelta(8498.82, $withDown['result']['emi'], 0.05);
        $this->assertEqualsWithDelta(109929.07, $withDown['result']['standard_interest'], 1.0);
        $this->assertEqualsWithDelta(509929.07, $withDown['result']['standard_total'], 1.0);
        $this->assertEqualsWithDelta(
            (float) $direct['result']['interest_saved'],
            (float) $withDown['result']['interest_saved'],
            0.01
        );

        // Client-submitted financed_principal must be ignored.
        $tampered = $calculator->calculate('debt-emi-calculator', $payload + [
            'financed_principal' => 1,
        ]);
        $this->assertSame(400000.0, (float) $tampered['normalized']['financed_principal']);
        $this->assertEqualsWithDelta($withDown['result']['emi'], $tampered['result']['emi'], 0.01);
    }

    public function test_full_down_payment_produces_no_loan_result(): void
    {
        $full = app(PfmCalculator::class)->calculate('debt-emi-calculator', [
            'principal' => 500000,
            'down_payment' => 500000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 2000,
        ]);
        $this->assertTrue((bool) $full['result']['no_loan']);
        $this->assertSame(0.0, (float) $full['result']['emi']);
        $this->assertSame(0.0, (float) $full['result']['standard_interest']);
        $this->assertSame(0.0, (float) $full['result']['interest_saved']);
        $this->assertSame(0, (int) $full['result']['months_saved']);
        $this->assertSame(500000.0, (float) $full['result']['total_purchase_outflow']);
    }

    public function test_excessive_down_payment_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(PfmCalculator::class)->calculate('debt-emi-calculator', [
            'principal' => 500000,
            'down_payment' => 600000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ]);
    }

    public function test_zero_interest_and_extra_payment_use_financed_principal(): void
    {
        $calculator = app(PfmCalculator::class);

        $zeroRate = $calculator->calculate('debt-emi-calculator', [
            'principal' => 500000,
            'down_payment' => 100000,
            'annual_rate_percent' => 0,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ]);
        $this->assertEqualsWithDelta(6666.67, $zeroRate['result']['emi'], 0.05);

        $withExtra = $calculator->calculate('debt-emi-calculator', [
            'principal' => 500000,
            'down_payment' => 100000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 2000,
        ]);
        $engineExtra = $calculator->calculate('debt-emi-calculator', [
            'principal' => 400000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 2000,
        ]);
        $this->assertEqualsWithDelta($engineExtra['result']['emi'], $withExtra['result']['emi'], 0.01);
        $this->assertEqualsWithDelta($engineExtra['result']['interest_saved'], $withExtra['result']['interest_saved'], 0.01);
        $this->assertSame((int) $engineExtra['result']['months_saved'], (int) $withExtra['result']['months_saved']);
    }

    public function test_validation_draft_save_history_recalculate_and_new_test(): void
    {
        $user = $this->member('debt-dp-flow@example.com');
        $other = $this->member('debt-dp-other@example.com');

        $this->actingAs($user, 'user')
            ->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), [
                'principal' => 500000,
                'down_payment' => -1,
                'annual_rate_percent' => 10,
                'tenure_years' => 5,
                'extra_payment' => 0,
            ])
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $this->actingAs($user, 'user')
            ->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), [
                'principal' => 500000,
                'down_payment' => 600000,
                'annual_rate_percent' => 10,
                'tenure_years' => 5,
                'extra_payment' => 0,
            ])
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $html = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="500000"', $html);
        $this->assertStringContainsString('value="600000"', $html);

        $payload = [
            'principal' => 500000,
            'down_payment' => 100000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 2000,
        ];

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $results = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.results'))
            ->assertOk()
            ->assertSee('Total purchase amount')
            ->assertSee('Down payment')
            ->assertSee('Actual financed loan amount')
            ->getContent();
        $this->assertStringContainsString('Total purchase-related outflow', $results);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));

        // Task 43 — recalculate restores inputs (clearResult); do not deduct down payment twice.
        $formAfterRecalc = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="100000"', $formAfterRecalc);
        $this->assertStringContainsString('value="500000"', $formAfterRecalc);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $form = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="100000"', $form);
        $this->assertStringContainsString('value="500000"', $form);

        $this->actingAs($user, 'user')->post(route(self::ROUTE.'.save'))->assertRedirect();
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertSame(100000.0, (float) ($record->inputs['down_payment'] ?? -1));
        $this->assertSame(400000.0, (float) ($record->inputs['financed_principal'] ?? -1));
        $this->assertSame(500000.0, (float) ($record->inputs['principal'] ?? -1));
        $this->assertSame(400000.0, (float) ($record->outputs['financed_principal'] ?? -1));

        // Historical-style record without down_payment still displays.
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
                'interest_saved' => 0,
                'months_saved' => 0,
                'summary' => 'Legacy snapshot',
            ],
            'summary' => 'Legacy snapshot',
            'content_version' => PfmCatalog::CONTENT_VERSION,
            'completion_key' => (string) \Illuminate\Support\Str::uuid(),
            'is_draft' => false,
        ]);

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $legacy))
            ->assertOk()
            ->assertSee('EMI');

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
        $this->assertSame(2, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_api_omitting_down_payment_stays_backward_compatible(): void
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

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $emi = (float) data_get($response->json(), 'result.emi');
        $this->assertEqualsWithDelta(10623.52, $emi, 0.05);
        $this->assertEqualsWithDelta(0, (float) data_get($response->json(), 'result.down_payment'), 0.01);
        $this->assertEqualsWithDelta(500000, (float) data_get($response->json(), 'result.financed_principal'), 0.01);

        $withDown = $this->postJson('/api/v1/pfm/debt-emi/calculate', [
            'principal' => 500000,
            'down_payment' => 100000,
            'annual_rate_percent' => 10,
            'tenure_years' => 5,
            'extra_payment' => 0,
        ])->assertOk();

        $this->assertEqualsWithDelta(8498.82, (float) data_get($withDown->json(), 'result.emi'), 0.05);
    }

    public function test_other_calculators_unaffected(): void
    {
        $ef = $this->actingAs($this->member('debt-dp-ef@example.com'), 'user')
            ->get(route('tools.pfm.emergency-fund'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-pfm-wizard', $ef);
        $this->assertStringNotContainsString('name="down_payment"', $ef);
        $this->assertStringNotContainsString('data-yk-debt-down-payment', $ef);
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
            'name' => 'Debt Down Payment Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
