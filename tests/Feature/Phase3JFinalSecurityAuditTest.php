<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\Admin;
use App\Models\Professional;
use App\Models\User;
use App\Services\YuktiRewards\YuktiRedemptionArchitecture;
use App\Services\YuktiRewards\YuktiRewardEngine;
use App\Services\YuktiRewards\YuktiRewardResult;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 3J — release gates for Refer & Earn (engine/redemption remain off).
 */
class Phase3JFinalSecurityAuditTest extends TestCase
{
    private object $foundation;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('registration_completed')->default(true);
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->default('admin');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });

        $this->foundation = require database_path(
            'migrations/2026_11_11_200000_create_yukti_refer_earn_foundation_tables.php'
        );
        $this->foundation->up();

        $activationMeta = require database_path(
            'migrations/2026_11_11_210000_add_activation_meta_to_referral_attributions.php'
        );
        $activationMeta->up();

        Config::set('yuktione_rewards.engine_enabled', false);
        Config::set('yuktione_rewards.redemption.enabled', false);

        $this->withoutMiddleware(CheckActiveSession::class);
    }

    public function test_config_defaults_keep_engine_and_redemption_disabled(): void
    {
        $this->assertFalse((bool) config('yuktione_rewards.engine_enabled'));
        $this->assertFalse((bool) config('yuktione_rewards.redemption.enabled'));
        $this->assertSame('activation_at', config('yuktione_rewards.referral_accounting_week'));
        $this->assertFalse(YuktiRedemptionArchitecture::snapshot()['executable']);
        $this->assertContains('booking_refund_model_missing', YuktiRedemptionArchitecture::snapshot()['blockers']);
    }

    public function test_engine_refuses_grants_while_disabled_even_with_valid_user(): void
    {
        $user = User::query()->create([
            'name' => 'Gate',
            'email' => 'gate@example.com',
            'password' => 'secret',
            'registration_completed' => true,
        ]);

        $result = app(YuktiRewardEngine::class)->grant(
            userId: (int) $user->id,
            rewardType: YuktiRewardEngine::TYPE_WEEKLY_LOGIN,
            idempotencyKey: 'phase3j:login:'.$user->id,
        );

        $this->assertSame(YuktiRewardResult::STATUS_DISABLED, $result->status);
        $this->assertFalse($result->granted);
        $this->assertDatabaseCount('yukti_reward_ledger', 0);
        $this->assertDatabaseCount('yukti_wallets', 0);
    }

    public function test_no_public_or_admin_mutating_yukti_routes_exist(): void
    {
        $mutating = collect(Route::getRoutes())->filter(function ($route) {
            $uri = strtolower($route->uri());
            $isYukti = str_contains($uri, 'yukti') || str_contains($uri, 'yukti-points');
            $isReferralLanding = (bool) preg_match('#^r(/|$)#', $uri);
            if (! $isYukti && ! $isReferralLanding) {
                return false;
            }

            return array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [];
        });

        $this->assertCount(0, $mutating->values());

        $names = collect(Route::getRoutes())->map->getName();
        $this->assertTrue($names->contains('user.yukti-points.index'));
        $this->assertTrue($names->contains('admin.yukti-points.dashboard'));
        $this->assertFalse($names->contains(fn ($n) => is_string($n) && str_contains($n, 'redeem')));
        $this->assertFalse($names->contains(fn ($n) => is_string($n) && str_contains($n, 'grant')));
    }

    public function test_customer_admin_professional_isolation_on_rewards_surfaces(): void
    {
        $customer = User::query()->create([
            'name' => 'C',
            'email' => 'c@example.com',
            'password' => 'secret',
            'registration_completed' => true,
        ]);

        $this->get(route('user.yukti-points.index'))->assertRedirect();
        $this->getJson(route('user.yukti-points.index'))->assertUnauthorized();

        $admin = Admin::query()->create(['name' => 'A', 'email' => 'a@example.com', 'role' => 'admin']);
        $this->actingAs($admin, 'admin')
            ->getJson(route('user.yukti-points.index'))
            ->assertUnauthorized();

        $pro = Professional::query()->create([
            'name' => 'P',
            'email' => 'p@example.com',
            'status' => 'accepted',
            'active' => true,
        ]);
        $this->actingAs($pro, 'professional')
            ->getJson(route('user.yukti-points.history'))
            ->assertUnauthorized();

        $this->actingAs($customer, 'user')
            ->getJson(route('user.yukti-points.index'))
            ->assertOk()
            ->assertJsonPath('summary.engine_enabled', false)
            ->assertJsonPath('redemption_architecture.executable', false);
    }

    public function test_admin_yukti_routes_require_admin_guard(): void
    {
        $this->get(route('admin.yukti-points.dashboard'))->assertRedirect();

        $customer = User::query()->create([
            'name' => 'Cust',
            'email' => 'cust@example.com',
            'password' => 'secret',
            'registration_completed' => true,
        ]);
        $this->actingAs($customer, 'user')
            ->get(route('admin.yukti-points.dashboard'))
            ->assertRedirect();
    }

    public function test_bookings_payment_status_has_no_refunded_enum_in_schema_docs(): void
    {
        // Safety gate for expert-referral clawback — refund lifecycle still missing.
        $migration = file_get_contents(database_path('migrations/2025_04_30_add_payment_fields_to_bookings_table.php'));
        $this->assertNotFalse($migration);
        $this->assertStringContainsString("['pending', 'paid', 'failed']", $migration);
        $this->assertStringNotContainsString('refunded', $migration);
    }
}
