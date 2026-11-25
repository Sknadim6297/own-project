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
 * Task 41 — Debt & EMI Calculator single continuous input form (no wizard).
 */
class DebtEmiSinglePageFormTest extends TestCase
{
    private const ROUTE = 'tools.pfm.debt-emi';

    private const FIELDS = [
        'principal',
        'annual_rate_percent',
        'tenure_years',
        'extra_payment',
    ];

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

    public function test_all_four_inputs_render_without_wizard_navigation(): void
    {
        $html = $this->actingAs($this->member('debt-single@example.com'), 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('yk-pfm--debt', false)
            ->assertSee('data-yk-debt-single', false)
            ->assertSee('debt-section-loan', false)
            ->assertSee('debt-section-extra', false)
            ->assertSee('yk-debt__control', false)
            ->assertSee('Calculate EMI')
            ->assertSee('all inputs on one page')
            ->getContent();

        foreach (self::FIELDS as $name) {
            $this->assertStringContainsString('name="'.$name.'"', $html);
        }

        $this->assertStringNotContainsString('data-pfm-wizard', $html);
        $this->assertStringNotContainsString('data-pfm-next', $html);
        $this->assertStringNotContainsString('data-pfm-prev', $html);
        $this->assertStringNotContainsString('data-pfm-step=', $html);
        $this->assertStringNotContainsString('Calculator steps', $html);
        $this->assertStringNotContainsString('>Continue<', $html);
        $this->assertStringNotContainsString('2 quick steps', $html);

        // Defaults preserved
        $this->assertMatchesRegularExpression('/id="pfm-extra"[^>]*value="0"/', $html);
        $this->assertMatchesRegularExpression('/id="pfm-principal"[^>]*value=""/', $html);
        $this->assertMatchesRegularExpression('/id="pfm-tenure"[^>]*min="0\.01"[^>]*max="100"|id="pfm-tenure"[^>]*max="100"[^>]*min="0\.01"/', $html);
    }

    public function test_all_four_inputs_submit_together_and_match_engine(): void
    {
        $user = $this->member('debt-single-calc@example.com');
        $payload = $this->payload();

        $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', $payload);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $draft = session('pfm.draft.debt-emi-calculator');
        $this->assertIsArray($draft);
        $this->assertEqualsWithDelta((float) $expected['result']['emi'], (float) $draft['result']['emi'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['result']['interest_saved'], (float) $draft['result']['interest_saved'], 0.01);
        $this->assertSame((int) $expected['result']['months_saved'], (int) $draft['result']['months_saved']);
        $this->assertEqualsWithDelta((float) $expected['result']['standard_interest'], (float) $draft['result']['standard_interest'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['result']['accelerated_interest'], (float) $draft['result']['accelerated_interest'], 0.01);

        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_validation_preserves_values_on_single_page_form(): void
    {
        $user = $this->member('debt-single-val@example.com');
        $this->actingAs($user, 'user')
            ->from(route(self::ROUTE))
            ->post(route(self::ROUTE.'.calculate'), [
                'principal' => 1500000,
                'annual_rate_percent' => 8.5,
                'tenure_years' => 0, // invalid
                'extra_payment' => 2500,
            ])
            ->assertRedirect(route(self::ROUTE))
            ->assertSessionHasErrors();

        $restored = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->assertSee('yk-pfm--debt', false)
            ->getContent();

        $this->assertStringContainsString('value="1500000"', $restored);
        $this->assertStringContainsString('value="8.5"', $restored);
        $this->assertStringContainsString('value="2500"', $restored);
        $this->assertStringNotContainsString('data-pfm-wizard', $restored);
        $this->assertStringNotContainsString('data-pfm-next', $restored);
        $this->assertStringContainsString('yk-debt__control', $restored);
    }

    public function test_draft_restore_save_history_recalculate_and_new_test(): void
    {
        $user = $this->member('debt-single-flow@example.com');
        $other = $this->member('debt-single-other@example.com');
        $payload = $this->payload([
            'principal' => 900000,
            'annual_rate_percent' => 9.25,
            'tenure_years' => 7,
            'extra_payment' => 1000,
        ]);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $form = $this->actingAs($user, 'user')
            ->get(route(self::ROUTE))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="900000"', $form);
        $this->assertMatchesRegularExpression('/id="pfm-tenure"[^>]*value="7(\.0)?"/', $form);
        $this->assertStringContainsString('yk-pfm--debt', $form);

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.recalculate'))
            ->assertRedirect(route(self::ROUTE));

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.calculate'), $payload)
            ->assertRedirect(route(self::ROUTE.'.results'));

        $this->assertSame(0, PfmCalculation::query()->where('user_id', $user->id)->count());

        $this->actingAs($user, 'user')
            ->post(route(self::ROUTE.'.save'))
            ->assertRedirect();

        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
        $record = PfmCalculation::query()->where('user_id', $user->id)->first();
        $this->assertSame(7.0, (float) ($record->inputs['tenure_years'] ?? 0));
        $this->assertSame(1000.0, (float) ($record->inputs['extra_payment'] ?? -1));

        $this->actingAs($user, 'user')
            ->get(route(self::ROUTE.'.history.show', $record))
            ->assertOk();

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
        $this->assertMatchesRegularExpression('/id="pfm-extra"[^>]*value="0"/', $fresh);
        $this->assertStringNotContainsString('data-pfm-next', $fresh);
        $this->assertSame(1, PfmCalculation::query()->where('user_id', $user->id)->count());
    }

    public function test_zero_extra_and_zero_interest_remain_supported(): void
    {
        $user = $this->member('debt-single-edge@example.com');

        foreach ([
            ['extra_payment' => 0, 'annual_rate_percent' => 9],
            ['extra_payment' => 0, 'annual_rate_percent' => 0],
            ['extra_payment' => '', 'annual_rate_percent' => 8],
        ] as $overrides) {
            $payload = $this->payload($overrides);
            $expected = app(PfmCalculator::class)->calculate('debt-emi-calculator', array_merge($payload, [
                'extra_payment' => $payload['extra_payment'] === '' ? 0 : $payload['extra_payment'],
            ]));

            $this->actingAs($user, 'user')
                ->post(route(self::ROUTE.'.calculate'), $payload)
                ->assertRedirect(route(self::ROUTE.'.results'));

            $draft = session('pfm.draft.debt-emi-calculator');
            $this->assertEqualsWithDelta((float) $expected['result']['emi'], (float) $draft['result']['emi'], 0.01);
        }
    }

    public function test_other_pfm_wizards_remain_intact(): void
    {
        $ef = $this->actingAs($this->member('debt-single-ef@example.com'), 'user')
            ->get(route('tools.pfm.emergency-fund'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-pfm-wizard', $ef);
        $this->assertStringContainsString('data-pfm-next', $ef);
        $this->assertStringNotContainsString('yk-pfm--debt', $ef);
        $this->assertStringNotContainsString('data-yk-debt-single', $ef);

        $mmp = $this->actingAs($this->member('debt-single-mmp@example.com'), 'user')
            ->get(route('tools.pfm.money-planner'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-pfm-wizard', $mmp);
        $this->assertStringContainsString('data-pfm-next', $mmp);

        $fg = $this->actingAs($this->member('debt-single-fg@example.com'), 'user')
            ->get(route('tools.pfm.financial-goal'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('yk-goal--single', $fg);
        $this->assertStringNotContainsString('data-pfm-wizard', $fg);
        $this->assertStringNotContainsString('yk-pfm--debt', $fg);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'principal' => 2000000,
            'annual_rate_percent' => 9,
            'tenure_years' => 15,
            'extra_payment' => 5000,
        ], $overrides);
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
            'name' => 'Debt Single Page Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }
}
