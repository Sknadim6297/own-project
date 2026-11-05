<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerLoginHomeRedirectTest extends TestCase
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
    }

    public function test_successful_customer_login_redirects_to_home_by_default(): void
    {
        $this->member('home-login@example.test');

        $response = $this->postJson(route('login.submit'), [
            'email' => 'home-login@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', route('home'));
    }

    public function test_customer_login_preserves_safe_intended_destination(): void
    {
        $this->member('intended-login@example.test');

        $response = $this->withSession([
            'url.intended' => url('/user/all-appointment'),
        ])->postJson(route('login.submit'), [
            'email' => 'intended-login@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', url('/user/all-appointment'));
    }

    public function test_customer_login_rejects_external_redirect_targets(): void
    {
        $this->member('safe-login@example.test');

        $response = $this->postJson(route('login.submit'), [
            'email' => 'safe-login@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
            'redirect' => 'https://evil.example/phish',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', route('home'));
    }

    public function test_authenticated_customer_visiting_login_is_sent_home(): void
    {
        $member = $this->member('already-in@example.test');

        $this->actingAs($member, 'user')
            ->get(route('login'))
            ->assertRedirect(route('home'));
    }

    public function test_public_header_exposes_view_dashboard_for_customers(): void
    {
        $member = $this->member('header-dash@example.test');

        $this->actingAs($member, 'user');

        $html = view('frontend.sections.header')->render();

        $this->assertStringContainsString('View Dashboard', $html);
        $this->assertStringContainsString('yk-navbar__dashboard', $html);
        $this->assertStringContainsString(route('user.dashboard'), $html);
        $this->assertStringContainsString('My Profile', $html);
    }

    public function test_guest_middleware_sends_authenticated_customers_home(): void
    {
        $member = $this->member('guest-mw@example.test');

        $this->actingAs($member, 'user')
            ->get(route('forgot.form'))
            ->assertRedirect(route('home'));
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Phase One Customer',
            'email' => $email,
            'password' => 'password-123',
            'registration_completed' => true,
            'password_set_at' => now(),
        ]);
    }
}
