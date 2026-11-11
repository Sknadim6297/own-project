<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminMenu;
use App\Models\Professional;
use App\Models\ReferralAttribution;
use App\Models\User;
use App\Models\YuktiFraudFlag;
use App\Models\YuktiRewardLedgerEntry;
use App\Models\YuktiWallet;
use App\Services\YuktiRewards\YuktiAdminMetricsService;
use App\Services\YuktiRewards\YuktiWeekId;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase3HYuktiAdminDashboardTest extends TestCase
{
    private object $foundation;

    private object $activationMeta;

    private object $menusMigration;

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
            $table->string('password')->nullable();
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('approved');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
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

        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('last_active_at')->nullable();
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

        $this->foundation = require database_path(
            'migrations/2026_11_11_200000_create_yukti_refer_earn_foundation_tables.php'
        );
        $this->foundation->up();

        $this->activationMeta = require database_path(
            'migrations/2026_11_11_210000_add_activation_meta_to_referral_attributions.php'
        );
        $this->activationMeta->up();

        $this->menusMigration = require database_path(
            'migrations/2026_11_11_220000_register_yukti_points_admin_menus.php'
        );
        $this->menusMigration->up();

        Config::set('yuktione_rewards.engine_enabled', false);
        Config::set('yuktione_rewards.redemption.enabled', false);
        Config::set('yuktione_rewards.week_timezone', 'Asia/Kolkata');
    }

    public function test_guest_customer_and_professional_cannot_access_admin_yukti_pages(): void
    {
        $this->get(route('admin.yukti-points.dashboard'))->assertRedirect(route('admin.login'));

        $customer = User::query()->create([
            'name' => 'Cust',
            'email' => 'cust@example.com',
            'password' => bcrypt('secret'),
        ]);
        $this->actingAs($customer, 'user')
            ->get(route('admin.yukti-points.dashboard'))
            ->assertRedirect(route('admin.login'));

        $pro = Professional::query()->create([
            'name' => 'Pro',
            'email' => 'pro@example.com',
            'status' => 'approved',
            'active' => true,
        ]);
        $this->actingAs($pro, 'professional')
            ->get(route('admin.yukti-points.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_without_menu_permission_is_denied(): void
    {
        $admin = $this->admin('limited@example.com', 'admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_super_admin_dashboard_aggregates_and_empty_state(): void
    {
        $admin = $this->admin('super@example.com', 'super_admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.dashboard'))
            ->assertOk()
            ->assertSee('Customer wallets')
            ->assertSee('Engine disabled')
            ->assertSee('Redemption disabled')
            ->assertSee('No reward transactions yet')
            ->assertDontSee('₹');

        $metrics = app(YuktiAdminMetricsService::class)->dashboard();
        $this->assertSame(0, $metrics['wallet_count']);
        $this->assertSame(0, $metrics['available_points']);
        $this->assertSame(0, $metrics['credited_points']);
        $this->assertFalse($metrics['engine_enabled']);
        $this->assertFalse($metrics['redemption_enabled']);
    }

    public function test_dashboard_reflects_real_wallet_ledger_and_referral_counts(): void
    {
        $admin = $this->admin('ops@example.com', 'super_admin');
        $user = User::query()->create(['name' => 'A', 'email' => 'a@example.com', 'password' => bcrypt('x')]);
        $ref = User::query()->create(['name' => 'B', 'email' => 'b@example.com', 'password' => bcrypt('x')]);
        $ref2 = User::query()->create(['name' => 'C', 'email' => 'c@example.com', 'password' => bcrypt('x')]);

        YuktiWallet::query()->create(['user_id' => $user->id, 'available_points' => 75, 'pending_points' => 10]);
        YuktiRewardLedgerEntry::query()->create([
            'user_id' => $user->id,
            'direction' => 'credit',
            'points' => 75,
            'available_after' => 75,
            'pending_after' => 10,
            'event_type' => 'weekly_login',
            'week_id' => YuktiWeekId::for(),
            'status' => 'completed',
            'idempotency_key' => 'login:'.$user->id.':'.YuktiWeekId::for(),
            'rewarded_at' => now(),
            'created_at' => now(),
        ]);
        YuktiRewardLedgerEntry::query()->create([
            'user_id' => $user->id,
            'direction' => 'debit',
            'points' => 5,
            'available_after' => 70,
            'pending_after' => 10,
            'event_type' => 'redemption',
            'week_id' => YuktiWeekId::for(),
            'status' => 'completed',
            'idempotency_key' => 'redeem:test:1',
            'rewarded_at' => now(),
            'created_at' => now(),
        ]);

        ReferralAttribution::query()->create([
            'referrer_user_id' => $user->id,
            'referee_user_id' => $ref->id,
            'referral_code' => 'AAAAAAA001',
            'referral_type' => 'tool',
            'target_type' => 'tool',
            'target_id' => 1,
            'status' => 'joined',
            'idempotency_key' => 'signup:'.$ref->id,
            'signed_up_at' => now(),
        ]);
        ReferralAttribution::query()->create([
            'referrer_user_id' => $user->id,
            'referee_user_id' => $ref2->id,
            'referral_code' => 'AAAAAAA002',
            'referral_type' => 'expert',
            'target_type' => 'professional',
            'target_id' => 2,
            'status' => 'activated',
            'idempotency_key' => 'signup:'.$ref2->id,
            'signed_up_at' => now()->subDay(),
            'activated_at' => now(),
        ]);
        YuktiFraudFlag::query()->create([
            'user_id' => $user->id,
            'reason' => 'self_referral_attempt',
            'severity' => 'open',
            'payload' => ['note' => 'test'],
        ]);

        $metrics = app(YuktiAdminMetricsService::class)->dashboard();
        $this->assertSame(1, $metrics['wallet_count']);
        $this->assertSame(75, $metrics['available_points']);
        $this->assertSame(10, $metrics['pending_points']);
        $this->assertSame(75, $metrics['credited_points']);
        $this->assertSame(5, $metrics['debited_points']);
        $this->assertSame(1, $metrics['referrals']['joined']);
        $this->assertSame(1, $metrics['referrals']['activated']);
        $this->assertSame(1, $metrics['fraud_flag_count']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.dashboard'))
            ->assertOk()
            ->assertSee('75')
            ->assertSee('Activated (not spendable)');
    }

    public function test_configuration_screen_reads_db_values_not_hardcoded_only(): void
    {
        $admin = $this->admin('config@example.com', 'super_admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.configuration'))
            ->assertOk()
            ->assertSee('weekly_login')
            ->assertSee('tool_completion')
            ->assertSee('tool_referral')
            ->assertSee('expert_referral')
            ->assertSee('social_share')
            ->assertSee('pending_business_rule')
            ->assertSee('redemption_rupee_per_point')
            ->assertDontSee('₹');
    }

    public function test_ledger_pagination_and_filters(): void
    {
        $admin = $this->admin('ledger@example.com', 'super_admin');
        $user = User::query()->create(['name' => 'L', 'email' => 'ledger-user@example.com', 'password' => bcrypt('x')]);
        $other = User::query()->create(['name' => 'O', 'email' => 'other-user@example.com', 'password' => bcrypt('x')]);

        for ($i = 1; $i <= 30; $i++) {
            YuktiRewardLedgerEntry::query()->create([
                'user_id' => $user->id,
                'direction' => 'credit',
                'points' => 1,
                'available_after' => $i,
                'pending_after' => 0,
                'event_type' => 'weekly_login',
                'week_id' => '2026-W46',
                'status' => 'completed',
                'idempotency_key' => 'login:'.$user->id.':row:'.$i,
                'rewarded_at' => now(),
                'created_at' => now(),
            ]);
        }
        YuktiRewardLedgerEntry::query()->create([
            'user_id' => $other->id,
            'direction' => 'credit',
            'points' => 25,
            'available_after' => 25,
            'pending_after' => 0,
            'event_type' => 'tool_completion',
            'week_id' => '2026-W46',
            'status' => 'completed',
            'idempotency_key' => 'tool:'.$other->id.':1',
            'rewarded_at' => now(),
            'created_at' => now(),
        ]);

        $page = app(YuktiAdminMetricsService::class)->ledger(['user_id' => $user->id], 10);
        $this->assertSame(30, $page->total());
        $this->assertCount(10, $page->items());

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.ledger', ['event_type' => 'tool_completion']))
            ->assertOk()
            ->assertSee('tool_completion')
            ->assertSee('other-user@example.com');
    }

    public function test_referral_filtering_distinguishes_activated_from_rewarded(): void
    {
        $admin = $this->admin('refs@example.com', 'super_admin');
        $referrer = User::query()->create(['name' => 'R', 'email' => 'referrer@example.com', 'password' => bcrypt('x')]);
        $a = User::query()->create(['name' => 'A', 'email' => 'act@example.com', 'password' => bcrypt('x')]);
        $b = User::query()->create(['name' => 'B', 'email' => 'rew@example.com', 'password' => bcrypt('x')]);

        ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $a->id,
            'referral_code' => 'REFCODE001',
            'referral_type' => 'tool',
            'target_type' => 'tool',
            'target_id' => 1,
            'status' => 'activated',
            'idempotency_key' => 'signup:'.$a->id,
            'signed_up_at' => now()->subDay(),
            'activated_at' => now(),
        ]);
        ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $b->id,
            'referral_code' => 'REFCODE002',
            'referral_type' => 'expert',
            'target_type' => 'professional',
            'target_id' => 9,
            'status' => 'rewarded',
            'idempotency_key' => 'signup:'.$b->id,
            'signed_up_at' => now()->subDays(2),
            'activated_at' => now()->subDay(),
            'rewarded_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.referrals', ['status' => 'activated']))
            ->assertOk()
            ->assertSee('act@example.com')
            ->assertDontSee('rew@example.com');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.referrals', ['referral_type' => 'expert']))
            ->assertOk()
            ->assertSee('rew@example.com')
            ->assertDontSee('act@example.com');
    }

    public function test_fraud_flags_are_visible_read_only(): void
    {
        $admin = $this->admin('fraud@example.com', 'super_admin');
        $user = User::query()->create(['name' => 'F', 'email' => 'flagged@example.com', 'password' => bcrypt('x')]);
        YuktiFraudFlag::query()->create([
            'user_id' => $user->id,
            'reason' => 'duplicate_device',
            'severity' => 'open',
            'payload' => ['safe' => true],
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.fraud'))
            ->assertOk()
            ->assertSee('duplicate_device')
            ->assertSee('flagged@example.com')
            ->assertSee('No automatic user blocking');
    }

    public function test_no_mutation_routes_for_yukti_admin_and_flags_remain_disabled(): void
    {
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertFalse(config('yuktione_rewards.redemption.enabled'));

        $mutating = collect(Route::getRoutes())->filter(function ($route) {
            $uri = strtolower($route->uri());
            if (! str_contains($uri, 'yukti-points') && ! str_contains($uri, 'yukti')) {
                return false;
            }
            $write = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);

            return $write !== [];
        });

        $this->assertCount(0, $mutating);
        $this->assertTrue(AdminMenu::query()->where('name', 'yukti_points')->exists());
        $this->assertTrue(AdminMenu::query()->where('route_name', 'admin.yukti-points.dashboard')->exists());
    }

    public function test_permitted_admin_can_open_wallets_page(): void
    {
        $admin = $this->admin('menu@example.com', 'admin');
        $parent = AdminMenu::query()->where('name', 'yukti_points')->firstOrFail();
        $child = AdminMenu::query()->where('name', 'yukti.wallets')->firstOrFail();
        $admin->menus()->attach([$parent->id, $child->id]);

        YuktiWallet::query()->create([
            'user_id' => User::query()->create(['name' => 'W', 'email' => 'w@example.com', 'password' => bcrypt('x')])->id,
            'available_points' => 3,
            'pending_points' => 0,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.yukti-points.wallets'))
            ->assertOk()
            ->assertSee('w@example.com');
    }

    private function admin(string $email, string $role): Admin
    {
        return Admin::query()->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => bcrypt('secret'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
