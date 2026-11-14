<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Notifications\ProfessionalResetPassword;
use App\Services\AccountRecovery;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class AccountRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->storage();
    }

    public function test_forgot_password_page_uses_email_field(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Forgot password?', false)
            ->assertSee('Email Address', false)
            ->assertSee('Enter your registered email address', false)
            ->assertSee('Send Password Reset Link', false)
            ->assertSee('Back to Login', false)
            ->assertDontSee('Mobile number', false)
            ->assertDontSee('name="phone"', false);
    }

    public function test_empty_email_shows_validation_error(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', [])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }

    public function test_invalid_email_shows_validation_error(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }

    public function test_unknown_email_gets_generic_confirmation_and_does_not_send(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'unknown@example.test'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', AccountRecovery::SENT_MESSAGE)
            ->assertSessionMissing('recovery_unregistered');

        Notification::assertNothingSent();
    }

    public function test_incomplete_customer_registration_gets_generic_confirmation_without_email(): void
    {
        Notification::fake();

        User::query()->create([
            'name' => 'Incomplete Customer',
            'email' => 'incomplete-customer@example.test',
            'phone' => '+91 98765 43210',
            'password' => 'already-set-password',
            'registration_completed' => false,
        ]);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'incomplete-customer@example.test'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', AccountRecovery::SENT_MESSAGE);

        Notification::assertNothingSent();
    }

    public function test_registered_customer_receives_reset_without_exposing_enumeration_details(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Registered Customer',
            'email' => 'registered-customer@example.test',
            'phone' => '+91 98765 43210',
            'password' => 'already-set-password',
            'registration_completed' => true,
        ]);

        $response = $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'registered-customer@example.test']);

        $response->assertRedirect('/forgot-password')
            ->assertSessionHas('status', AccountRecovery::SENT_MESSAGE)
            ->assertSessionMissing('recovery_unregistered');

        $this->assertStringNotContainsString($user->email, (string) session('status'));
        Notification::assertSentTo($user, CustomerResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token_and_old_password_fails(): void
    {
        Notification::fake();
        Event::fake([PasswordReset::class]);

        $user = User::query()->create([
            'name' => 'Reset Customer',
            'email' => 'reset-customer@example.test',
            'phone' => '9876500111',
            'password' => 'old-password-123',
            'registration_completed' => true,
        ]);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', AccountRecovery::SENT_MESSAGE);

        $token = null;
        Notification::assertSentTo($user, CustomerResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->assertNotEmpty($token);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertFalse(Hash::check('old-password-123', $user->password));
        Event::assertDispatched(PasswordReset::class);

        // Token reuse must fail.
        $this->from('/reset-password')
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'another-password-123',
                'password_confirmation' => 'another-password-123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_password_mismatch_is_rejected(): void
    {
        $this->from('/reset-password')
            ->post('/reset-password', [
                'token' => 'any-token',
                'email' => 'someone@example.test',
                'password' => 'password-one',
                'password_confirmation' => 'password-two',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_registered_professional_continues_to_the_professional_reset(): void
    {
        Notification::fake();

        $professional = Professional::query()->create([
            'name' => 'Registered Professional',
            'email' => 'registered-professional@example.test',
            'phone' => '9123456789',
            'password' => Hash::make('already-set-password'),
            'status' => 'pending',
        ]);

        $this->from('/professional/forgot-password')
            ->post('/professional/forgot-password', ['phone' => '+91 91234 56789'])
            ->assertRedirect('/professional/forgot-password')
            ->assertSessionHas('status', AccountRecovery::SENT_MESSAGE)
            ->assertSessionMissing('recovery_unregistered');

        Notification::assertSentTo($professional, ProfessionalResetPassword::class);

        session()->flash('recovery_unregistered', true);
        $html = view('professional.login.forgot-password')->render();
        $this->assertStringContainsString(route('professional.register'), $html);
        $this->assertStringContainsString('Register Now', $html);
    }

    public function test_unknown_professional_mobile_does_not_use_a_customer_account(): void
    {
        Notification::fake();

        User::query()->create([
            'name' => 'Customer Only',
            'email' => 'customer-only@example.test',
            'phone' => '9000011111',
            'password' => 'already-set-password',
            'registration_completed' => true,
        ]);

        $this->from('/professional/forgot-password')
            ->post('/professional/forgot-password', ['phone' => '9000011111'])
            ->assertSessionHas('recovery_unregistered', true);

        Notification::assertNothingSent();
    }

    public function test_password_reset_endpoint_does_not_reveal_registration_result(): void
    {
        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'someone@example.test',
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertSessionMissing('recovery_unregistered');
    }

    public function test_recovery_posts_are_rate_limited(): void
    {
        foreach (['/forgot-password', '/professional/forgot-password'] as $uri) {
            $route = app('router')->getRoutes()->match(Request::create($uri, 'POST'));

            $this->assertContains('throttle:5,1,recovery', $route->gatherMiddleware(), $uri);
            $this->assertContains('throttle:20,60,recovery-hour', $route->gatherMiddleware(), $uri);
        }

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/forgot-password', ['email' => 'rate-limit@example.test'])->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => 'rate-limit@example.test'])->assertStatus(429);
    }

    private function storage(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('professionals');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->boolean('registration_completed')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
}
