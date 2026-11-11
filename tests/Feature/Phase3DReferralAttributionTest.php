<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\Tool;
use App\Models\User;
use App\Services\YuktiRewards\ReferralAttributionService;
use App\Services\YuktiRewards\ReferralCaptureStore;
use App\Services\YuktiRewards\ReferralCodeGenerator;
use App\Services\YuktiRewards\ReferralLinkBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase3DReferralAttributionTest extends TestCase
{
    private object $migration;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->boolean('registration_completed')->default(false);
            $table->string('registration_status')->nullable();
            $table->timestamp('password_set_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module_key')->nullable();
            $table->string('status')->default('coming_soon');
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
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
            $table->timestamps();
        });

        $this->migration = require database_path(
            'migrations/2026_11_11_200000_create_yukti_refer_earn_foundation_tables.php'
        );
        $this->migration->up();

        Config::set('yuktione_rewards.engine_enabled', false);
        Config::set('yuktione_rewards.referral_capture_ttl_minutes', 4320);
    }

    public function test_unique_referral_code_generation_is_lazy_and_stable(): void
    {
        $user = $this->completedUser('referrer@example.com');
        $gen = app(ReferralCodeGenerator::class);

        $first = $gen->forUser($user);
        $second = $gen->forUser($user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->code, $second->code);
        $this->assertSame(1, ReferralCode::query()->where('user_id', $user->id)->count());
        $this->assertDoesNotMatchRegularExpression('/@|\d{5,}/', $first->code);
        $this->assertFalse(str_contains(strtolower($first->code), (string) $user->id));
    }

    public function test_code_collision_retries_until_unique(): void
    {
        $user = $this->completedUser('a@example.com');
        $other = $this->completedUser('b@example.com');

        ReferralCode::query()->create([
            'user_id' => $other->id,
            'code' => 'AAAAAAAAAA',
            'is_active' => true,
        ]);

        $gen = new class extends ReferralCodeGenerator
        {
            private int $n = 0;

            public function generate(int $length = 10): string
            {
                $this->n++;

                return $this->n === 1 ? 'AAAAAAAAAA' : 'BBBBBBBBBB';
            }
        };

        $code = $gen->forUser($user);
        $this->assertSame('BBBBBBBBBB', $code->code);
    }

    public function test_general_tool_and_expert_capture_and_invalid_targets(): void
    {
        $referrer = $this->completedUser('r@example.com');
        $builder = app(ReferralLinkBuilder::class);
        $code = $builder->codeFor($referrer)->code;

        $tool = Tool::query()->create([
            'name' => 'BMI',
            'slug' => 'bmi-bmr-calculator',
            'module_key' => 'bmi',
            'status' => 'active',
            'route_name' => 'tools.bmi-bmr',
            'is_implemented' => true,
        ]);

        $expert = Professional::query()->create([
            'name' => 'QA Expert',
            'email' => 'expert@example.com',
            'status' => 'accepted',
            'active' => true,
        ]);

        $this->get(route('referral.landing', ['code' => $code]))
            ->assertRedirect(route('register'));
        $this->assertNotNull(session(ReferralCaptureStore::SESSION_KEY));
        $this->assertSame('general', session(ReferralCaptureStore::SESSION_KEY)['referral_type']);

        // First-touch: second link must not overwrite.
        session()->forget(ReferralCaptureStore::SESSION_KEY);
        $store = app(ReferralCaptureStore::class);
        $store->remember([
            'code' => $code,
            'referral_type' => 'general',
            'target_type' => null,
            'target_id' => null,
            'referrer_user_id' => $referrer->id,
            'destination_route' => 'register',
            'destination_params' => [],
        ]);

        $this->get(route('referral.landing.tool', ['code' => $code, 'tool' => $tool->slug]))
            ->assertRedirect();
        $this->assertSame('general', session(ReferralCaptureStore::SESSION_KEY)['referral_type']);

        // Fresh session: tool capture works.
        session()->forget(ReferralCaptureStore::SESSION_KEY);
        $this->get(route('referral.landing.tool', ['code' => $code, 'tool' => $tool->slug]))
            ->assertRedirect(route('tools.bmi-bmr'));
        $this->assertSame('tool', session(ReferralCaptureStore::SESSION_KEY)['referral_type']);
        $this->assertSame($tool->id, session(ReferralCaptureStore::SESSION_KEY)['target_id']);

        session()->forget(ReferralCaptureStore::SESSION_KEY);
        $this->get(route('referral.landing.expert', ['code' => $code, 'professional' => $expert->id]))
            ->assertRedirect();
        $this->assertSame('expert', session(ReferralCaptureStore::SESSION_KEY)['referral_type']);

        // Invalid code / target → home, no capture.
        session()->forget(ReferralCaptureStore::SESSION_KEY);
        $this->get(route('referral.landing', ['code' => 'ZZZZZZZZZZ']))
            ->assertRedirect(route('home'));
        $this->assertNull(session(ReferralCaptureStore::SESSION_KEY));

        session()->forget(ReferralCaptureStore::SESSION_KEY);
        $this->get(route('referral.landing.tool', ['code' => $code, 'tool' => 'missing-tool']))
            ->assertRedirect(route('home'));
        $this->assertNull(session(ReferralCaptureStore::SESSION_KEY));

        $inactive = Professional::query()->create([
            'name' => 'Hidden',
            'status' => 'pending',
            'active' => false,
        ]);
        $this->get(route('referral.landing.expert', ['code' => $code, 'professional' => $inactive->id]))
            ->assertRedirect(route('home'));
    }

    public function test_signup_binding_after_complete_registration_and_otp_path(): void
    {
        Config::set('yuktione_rewards.engine_enabled', false);

        $referrer = $this->completedUser('ref@example.com');
        $code = app(ReferralCodeGenerator::class)->forUser($referrer)->code;

        $this->get(route('referral.landing', ['code' => $code]))->assertRedirect();

        // Incomplete user (lead) must NOT bind yet.
        $incomplete = User::query()->create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'phone' => '9999999999',
            'registration_completed' => false,
            'registration_status' => 'otp_verified',
            'email_verified' => true,
        ]);

        $this->assertNull(app(ReferralAttributionService::class)->bindCompletedRegistration($incomplete));
        $this->assertSame(0, ReferralAttribution::query()->count());
        $this->assertNotNull(session(ReferralCaptureStore::SESSION_KEY));

        // Complete registration via endpoint.
        $this->postJson(route('register.complete'), [
            'user_id' => $incomplete->id,
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
        ])->assertOk()->assertJson(['status' => 'success']);

        $attr = ReferralAttribution::query()->where('referee_user_id', $incomplete->id)->first();
        $this->assertNotNull($attr);
        $this->assertSame($referrer->id, $attr->referrer_user_id);
        $this->assertSame('joined', $attr->status);
        $this->assertSame('general', $attr->referral_type);
        $this->assertNull(session(ReferralCaptureStore::SESSION_KEY));
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $this->assertDatabaseCount('yukti_reward_ledger', 0);
    }

    public function test_self_referral_and_duplicate_attribution_prevention(): void
    {
        $user = $this->completedUser('self@example.com');
        $code = app(ReferralCodeGenerator::class)->forUser($user)->code;

        // Capture as guest then somehow become same user — bind must reject self.
        $this->get(route('referral.landing', ['code' => $code]));
        $clone = User::query()->create([
            'name' => 'Same Person',
            'email' => 'self2@example.com',
            'registration_completed' => true,
            'registration_status' => 'completed',
        ]);
        // Force capture referrer to equal referee by rewriting session (attack simulation).
        $payload = session(ReferralCaptureStore::SESSION_KEY);
        $payload['referrer_user_id'] = $clone->id;
        session([ReferralCaptureStore::SESSION_KEY => $payload]);

        $this->assertNull(app(ReferralAttributionService::class)->bindCompletedRegistration($clone));

        // Duplicate: bind once, then again.
        $referrer = $this->completedUser('dup-r@example.com');
        $code2 = app(ReferralCodeGenerator::class)->forUser($referrer)->code;
        session()->forget(ReferralCaptureStore::SESSION_KEY);
        $this->get(route('referral.landing', ['code' => $code2]));

        $newbie = User::query()->create([
            'name' => 'Newbie',
            'email' => 'newbie@example.com',
            'registration_completed' => true,
            'registration_status' => 'completed',
        ]);
        $first = app(ReferralAttributionService::class)->bindCompletedRegistration($newbie);
        $this->assertNotNull($first);

        // Re-inject capture and attempt second bind — existing attribution wins.
        app(ReferralCaptureStore::class)->remember([
            'code' => $code2,
            'referral_type' => 'general',
            'target_type' => null,
            'target_id' => null,
            'referrer_user_id' => $referrer->id,
            'destination_route' => 'register',
            'destination_params' => [],
        ], overwrite: true);

        $second = app(ReferralAttributionService::class)->bindCompletedRegistration($newbie);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ReferralAttribution::query()->where('referee_user_id', $newbie->id)->count());
    }

    public function test_authenticated_customer_does_not_get_signup_capture(): void
    {
        $referrer = $this->completedUser('owner@example.com');
        $visitor = $this->completedUser('visitor@example.com');
        $code = app(ReferralCodeGenerator::class)->forUser($referrer)->code;

        $this->actingAs($visitor, 'user');
        $this->get(route('referral.landing', ['code' => $code]))->assertRedirect();
        $this->assertNull(session(ReferralCaptureStore::SESSION_KEY));
        Auth::guard('user')->logout();
    }

    public function test_session_expiration_clears_capture(): void
    {
        Config::set('yuktione_rewards.referral_capture_ttl_minutes', 1);
        $referrer = $this->completedUser('ttl@example.com');
        $code = app(ReferralCodeGenerator::class)->forUser($referrer)->code;

        $this->get(route('referral.landing', ['code' => $code]));
        $payload = session(ReferralCaptureStore::SESSION_KEY);
        $payload['expires_at'] = now()->subMinute()->timestamp;
        session([ReferralCaptureStore::SESSION_KEY => $payload]);

        $this->assertNull(app(ReferralCaptureStore::class)->get());
    }

    public function test_engine_remains_disabled_and_no_points_on_capture(): void
    {
        $this->assertFalse(config('yuktione_rewards.engine_enabled'));
        $referrer = $this->completedUser('nopoints@example.com');
        $code = app(ReferralCodeGenerator::class)->forUser($referrer)->code;
        $this->get(route('referral.landing', ['code' => $code]));
        $this->assertDatabaseCount('yukti_reward_ledger', 0);
        $this->assertDatabaseCount('yukti_wallets', 0);
    }

    private function completedUser(string $email): User
    {
        return User::query()->create([
            'name' => 'Customer',
            'email' => $email,
            'phone' => '9000000000',
            'password' => 'secret12',
            'email_verified' => true,
            'registration_completed' => true,
            'registration_status' => 'completed',
            'password_set_at' => now(),
        ]);
    }
}
