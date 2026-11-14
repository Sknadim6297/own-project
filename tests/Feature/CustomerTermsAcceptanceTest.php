<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Auth\IntendedUrl;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerTermsAcceptanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('registration_completed')->default(true);
            $table->timestamp('password_set_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_version', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id');
            $table->string('device_info')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('profile_image')->nullable();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('plan_type')->nullable();
            $table->timestamps();
        });

        config([
            'terms.current_version' => '1.0',
            'terms.require_reacceptance_on_version_change' => false,
            'terms.enforce_after_login' => false,
        ]);
    }

    public function test_login_goes_to_home_by_default_not_terms_page(): void
    {
        $this->member('first-login@example.test');

        $response = $this->postJson(route('login.submit'), [
            'email' => 'first-login@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', route('home'));
    }

    public function test_login_checkbox_persists_terms_acceptance(): void
    {
        $member = $this->member('persist-login@example.test');

        $this->postJson(route('login.submit'), [
            'email' => 'persist-login@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ])->assertOk();

        $member->refresh();
        $this->assertNotNull($member->terms_accepted_at);
        $this->assertSame('1.0', $member->terms_version);
    }

    public function test_enforced_first_login_redirects_to_terms_acceptance(): void
    {
        config(['terms.enforce_after_login' => true]);
        $this->member('enforce-first@example.test');

        $response = $this->postJson(route('login.submit'), [
            'email' => 'enforce-first@example.test',
            'password' => 'password-123',
            // No checkbox — forces post-login Terms page when enforcement is on.
            'terms_accepted' => '0',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', route('terms.accept.show'));
    }

    public function test_returning_user_login_skips_terms_and_goes_to_home(): void
    {
        $this->member('returning@example.test', true);

        $response = $this->postJson(route('login.submit'), [
            'email' => 'returning@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', route('home'));
    }

    public function test_unaccepted_user_cannot_open_dashboard_when_enforced(): void
    {
        config(['terms.enforce_after_login' => true]);
        $member = $this->member('blocked@example.test');

        $this->actingAs($member, 'user')
            ->get(route('user.dashboard'))
            ->assertRedirect(route('terms.accept.show'));
    }

    public function test_unaccepted_user_can_open_dashboard_when_not_enforced(): void
    {
        $member = $this->member('open-dash@example.test');

        $this->actingAs($member, 'user')
            ->get(route('user.dashboard'))
            ->assertOk();
    }

    public function test_accepting_terms_persists_and_opens_intended_destination(): void
    {
        $member = $this->member('accept@example.test');

        $response = $this->actingAs($member, 'user')
            ->withSession([
                IntendedUrl::USER_KEY => url('/user/all-appointment'),
            ])
            ->postJson(route('terms.accept'), [
                'accepted' => '1',
                'user_id' => $member->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', url('/user/all-appointment'));

        $member->refresh();
        $this->assertNotNull($member->terms_accepted_at);
        $this->assertSame('1.0', $member->terms_version);
    }

    public function test_duplicate_acceptance_keeps_original_timestamp(): void
    {
        $member = $this->member('dup@example.test', true);
        $original = $member->terms_accepted_at->copy();

        $this->travel(2)->hours();

        $this->actingAs($member, 'user')
            ->postJson(route('terms.accept'), [
                'accepted' => '1',
                'user_id' => $member->id,
            ])
            ->assertOk();

        $member->refresh();
        $this->assertTrue($member->terms_accepted_at->equalTo($original));
        $this->assertSame('1.0', $member->terms_version);
    }

    public function test_decline_signs_out_without_saving_acceptance(): void
    {
        $member = $this->member('decline@example.test');

        $this->actingAs($member, 'user')
            ->post(route('terms.decline'))
            ->assertRedirect(route('login'));

        $this->assertGuest('user');
        $member->refresh();
        $this->assertNull($member->terms_accepted_at);
        $this->assertNull($member->terms_version);
    }

    public function test_guest_cannot_accept_terms(): void
    {
        $this->postJson(route('terms.accept'), [
            'accepted' => '1',
        ])->assertUnauthorized();
    }

    public function test_acceptance_requires_explicit_checkbox(): void
    {
        $member = $this->member('nocheck@example.test');

        $this->actingAs($member, 'user')
            ->postJson(route('terms.accept'), [
                'user_id' => $member->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['accepted']);

        $member->refresh();
        $this->assertNull($member->terms_accepted_at);
    }

    public function test_cannot_accept_terms_for_another_user_id(): void
    {
        $member = $this->member('self@example.test');
        $other = $this->member('other@example.test');

        $this->actingAs($member, 'user')
            ->postJson(route('terms.accept'), [
                'accepted' => '1',
                'user_id' => $other->id,
            ])
            ->assertStatus(422);

        $member->refresh();
        $other->refresh();
        $this->assertNull($member->terms_accepted_at);
        $this->assertNull($other->terms_accepted_at);
    }

    public function test_terms_page_renders_for_unaccepted_user(): void
    {
        config(['terms.enforce_after_login' => true]);
        $member = $this->member('view@example.test');

        $this->actingAs($member, 'user')
            ->get(route('terms.accept.show'))
            ->assertOk()
            ->assertSee('Terms &amp; Conditions', false)
            ->assertSee('I have read and agree to the Terms &amp; Conditions', false)
            ->assertSee('I Agree &amp; Continue', false)
            ->assertSee('Decline &amp; Sign Out', false)
            ->assertSee('User Agreement for Yuktione', false);
    }

    public function test_second_login_after_acceptance_skips_terms(): void
    {
        config(['terms.enforce_after_login' => true]);
        $member = $this->member('second@example.test');

        $this->actingAs($member, 'user')
            ->postJson(route('terms.accept'), [
                'accepted' => '1',
                'user_id' => $member->id,
            ])
            ->assertOk();

        auth('user')->logout();

        $response = $this->postJson(route('login.submit'), [
            'email' => 'second@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ]);

        $response->assertOk()
            ->assertJsonPath('redirect_url', route('home'));
    }

    private function member(string $email, bool $accepted = false): User
    {
        return User::query()->create([
            'name' => 'Terms Customer',
            'email' => $email,
            'password' => 'password-123',
            'registration_completed' => true,
            'password_set_at' => now(),
            'terms_accepted_at' => $accepted ? now() : null,
            'terms_version' => $accepted ? '1.0' : null,
        ]);
    }
}
