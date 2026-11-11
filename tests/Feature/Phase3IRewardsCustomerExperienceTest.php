<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckActiveSession;
use App\Models\Admin;
use App\Models\Professional;
use App\Models\ReferralAttribution;
use App\Models\Tool;
use App\Models\User;
use App\Models\YuktiWallet;
use App\Services\YuktiRewards\ReferralLinkBuilder;
use App\Services\YuktiRewards\YuktiRewardsHubPresenter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase3IRewardsCustomerExperienceTest extends TestCase
{
    private object $foundation;

    private object $activationMeta;

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('registration_completed')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
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

        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_category_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module_key')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('access_type')->default('free');
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
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

        $this->activationMeta = require database_path(
            'migrations/2026_11_11_210000_add_activation_meta_to_referral_attributions.php'
        );
        $this->activationMeta->up();

        Config::set('yuktione_rewards.week_timezone', 'Asia/Kolkata');
        Config::set('yuktione_rewards.engine_enabled', false);
        Config::set('yuktione_rewards.redemption.enabled', false);
        Config::set('yuktione_rewards.eligible_tool_completion_slugs', [
            'psychology-assessment',
            'career-interest',
            'skills-strengths',
            'bmi-bmr',
            'mood-check-in',
            'journal',
            'gratitude',
        ]);

        $this->withoutMiddleware(CheckActiveSession::class);
    }

    public function test_authenticated_customer_sees_rewards_hub_with_real_balances_and_engine_off_copy(): void
    {
        $user = $this->user('hub@example.com', 'Priya Sharma');
        YuktiWallet::query()->create([
            'user_id' => $user->id,
            'available_points' => 40,
            'pending_points' => 5,
        ]);

        $response = $this->actingAs($user, 'user')
            ->get(route('user.yukti-points.index'));

        $response->assertOk()
            ->assertSee('Welcome back, Priya', false)
            ->assertSee('40', false)
            ->assertSee('Available YuktiPoints', false)
            ->assertSee('Rewards are not currently active', false)
            ->assertSee('Redemption is not available yet', false)
            ->assertSee('Weekly login', false)
            ->assertSee('Social share', false)
            ->assertSee('Not active yet', false)
            ->assertDontSee('Redeem now', false)
            ->assertDontSee('data-yk-action="redeem"', false);

        $html = $response->getContent();
        preg_match('/class="yk-rewards".*?<\/section>/s', $html, $section);
        $this->assertNotEmpty($section);
        $this->assertStringNotContainsString('₹', $section[0]);
        $this->assertStringContainsString('data-yk-action="copy"', $html);
        $this->assertStringContainsString('data-yk-action="whatsapp"', $html);
        $this->assertStringContainsString('yuktione-rewards.css', $html);
        $this->assertStringContainsString('yuktione-rewards.js', $html);
    }

    public function test_wallet_and_activity_pages_render_ledger_and_lifecycle_honestly(): void
    {
        $referrer = $this->user('ref@example.com');
        $referee = $this->user('join@example.com');
        YuktiWallet::query()->create([
            'user_id' => $referrer->id,
            'available_points' => 12,
            'pending_points' => 0,
        ]);

        ReferralAttribution::query()->create([
            'referrer_user_id' => $referrer->id,
            'referee_user_id' => $referee->id,
            'referral_code' => 'TESTCODE12',
            'referral_type' => 'tool',
            'target_type' => 'tool',
            'target_id' => 1,
            'status' => 'activated',
            'idempotency_key' => 'attr:'.$referee->id,
            'signed_up_at' => now()->subDay(),
            'activated_at' => now(),
            'rewarded_at' => null,
            'activation_meta' => ['activated_week_id' => '2026-11-10'],
        ]);

        $wallet = $this->actingAs($referrer, 'user')
            ->get(route('user.yukti-points.wallet'));
        $wallet->assertOk()
            ->assertSee('Your YuktiPoints', false)
            ->assertSee('Redemption is disabled', false)
            ->assertSee('12', false);
        preg_match('/class="yk-rewards".*?<\/section>/s', $wallet->getContent(), $walletSection);
        $this->assertNotEmpty($walletSection);
        $this->assertStringNotContainsString('₹', $walletSection[0]);

        $activity = $this->actingAs($referrer, 'user')
            ->get(route('user.yukti-points.activity', ['status' => 'activated']));
        $activity->assertOk()
            ->assertSee('activated', false)
            ->assertSee('tool', false)
            ->assertSee('Estimate only', false)
            ->assertSee('Invited → Joined → Activated → Rewarded', false);
    }

    public function test_presenter_share_targets_use_server_referral_links_only(): void
    {
        $user = $this->user('share@example.com');
        $links = app(ReferralLinkBuilder::class);

        Tool::query()->create([
            'name' => 'Career Interest',
            'slug' => 'career-interest',
            'module_key' => 'career_interest',
            'status' => 'active',
            'route_name' => 'tools.index',
            'is_implemented' => true,
            'display_order' => 1,
        ]);
        Tool::query()->create([
            'name' => 'Hidden Water',
            'slug' => 'water-intake',
            'module_key' => 'water',
            'status' => 'active',
            'route_name' => 'tools.index',
            'is_implemented' => true,
            'display_order' => 2,
        ]);
        Tool::query()->create([
            'name' => 'Broken Tool',
            'slug' => 'mood-check-in',
            'module_key' => 'mood',
            'status' => 'active',
            'route_name' => 'tools.does-not-exist',
            'is_implemented' => true,
            'display_order' => 3,
        ]);

        Professional::query()->create([
            'name' => 'Visible Expert',
            'email' => 'vis@example.com',
            'status' => 'accepted',
            'active' => true,
        ]);
        Professional::query()->create([
            'name' => 'Hidden Expert',
            'email' => 'hid@example.com',
            'status' => 'pending',
            'active' => false,
        ]);

        $hub = app(YuktiRewardsHubPresenter::class)->hub($user);

        $this->assertSame($links->generalUrl($user), $hub['share']['general_url']);
        $this->assertFalse($hub['engine_enabled']);
        $this->assertFalse($hub['summary']['redemption']['executable']);

        $toolSlugs = collect($hub['share']['tools'])->pluck('slug')->all();
        $this->assertContains('career-interest', $toolSlugs);
        $this->assertNotContains('water-intake', $toolSlugs);
        $this->assertNotContains('mood-check-in', $toolSlugs);

        foreach ($hub['share']['tools'] as $tool) {
            $this->assertStringContainsString('/r/', $tool['url']);
            $this->assertStringContainsString($hub['share']['code'], $tool['url']);
        }

        $this->assertCount(1, $hub['share']['experts']);
        $this->assertSame('Visible Expert', $hub['share']['experts'][0]['name']);

        foreach ($hub['weekly_cards'] as $card) {
            $this->assertNull($card['cta_label']);
            $this->assertSame('Not active yet', $card['status_label']);
        }
    }

    public function test_guest_admin_professional_cannot_open_rewards_html_pages(): void
    {
        $this->get(route('user.yukti-points.index'))->assertRedirect();
        $this->get(route('user.yukti-points.wallet'))->assertRedirect();
        $this->get(route('user.yukti-points.activity'))->assertRedirect();

        $admin = Admin::query()->create(['name' => 'Admin', 'email' => 'a@example.com']);
        $this->actingAs($admin, 'admin')
            ->get(route('user.yukti-points.index'))
            ->assertRedirect();

        $pro = Professional::query()->create([
            'name' => 'Pro',
            'email' => 'p@example.com',
            'status' => 'accepted',
            'active' => true,
        ]);
        $this->actingAs($pro, 'professional')
            ->get(route('user.yukti-points.wallet'))
            ->assertRedirect();
    }

    public function test_no_mutation_or_redeem_routes_introduced_in_phase_3i(): void
    {
        $methods = collect(Route::getRoutes())->map(fn ($r) => [
            'uri' => strtolower($r->uri()),
            'name' => $r->getName(),
            'methods' => $r->methods(),
        ]);

        $this->assertTrue($methods->contains(fn ($r) => $r['name'] === 'user.yukti-points.wallet'));
        $this->assertTrue($methods->contains(fn ($r) => $r['name'] === 'user.yukti-points.activity'));

        $mutating = $methods->filter(function ($row) {
            if (! str_contains($row['uri'], 'yukti-points') && ! str_contains($row['uri'], 'yukti')) {
                return false;
            }
            $write = array_intersect($row['methods'], ['POST', 'PUT', 'PATCH', 'DELETE']);

            return $write !== [];
        });

        $this->assertCount(0, $mutating);
        $this->assertFalse($methods->contains(fn ($r) => str_contains($r['uri'], 'redeem')));
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertFalse(config('yuktione_rewards.redemption.enabled'));
    }

    public function test_json_wallet_contract_from_phase_3g_still_works_alongside_html(): void
    {
        $user = $this->user('json@example.com');
        YuktiWallet::query()->create([
            'user_id' => $user->id,
            'available_points' => 7,
            'pending_points' => 1,
        ]);

        $this->actingAs($user, 'user')
            ->getJson(route('user.yukti-points.index'))
            ->assertOk()
            ->assertJsonPath('summary.available_points', 7)
            ->assertJsonPath('summary.pending_points', 1)
            ->assertJsonPath('redemption_architecture.executable', false);

        $this->actingAs($user, 'user')
            ->get(route('user.yukti-points.index'))
            ->assertOk()
            ->assertSee('7', false);
    }

    private function user(string $email, string $name = 'QA Customer'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
            'registration_completed' => true,
        ]);
    }
}
