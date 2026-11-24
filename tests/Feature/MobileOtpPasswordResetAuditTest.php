<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Notifications\ProfessionalResetPassword;
use App\Services\AccountRecovery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 08: documents that SMS/mobile OTP password reset is not implemented.
 * Protects existing email recovery and blocks registration-OTP misuse for reset.
 */
class MobileOtpPasswordResetAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->storage();
    }

    public function test_no_password_reset_otp_routes_are_registered(): void
    {
        $paths = collect(Route::getRoutes())->map(function ($route) {
            return strtolower($route->uri());
        });

        $resetOtpPaths = $paths->filter(function (string $uri) {
            return str_contains($uri, 'otp')
                && (str_contains($uri, 'forgot') || str_contains($uri, 'reset') || str_contains($uri, 'recovery'));
        });

        $this->assertTrue(
            $resetOtpPaths->isEmpty(),
            'Unexpected password-reset OTP routes: '.$resetOtpPaths->implode(', ')
        );

        $this->assertFalse(Route::has('forgot.send-otp'));
        $this->assertFalse(Route::has('forgot.verify-otp'));
        $this->assertFalse(Route::has('professional.forgot.send-otp'));
        $this->assertFalse(Route::has('professional.forgot.verify-otp'));
    }

    public function test_registration_otp_routes_exist_but_are_not_password_reset(): void
    {
        $this->assertTrue(Route::has('register.send-otp'));
        $this->assertTrue(Route::has('register.verify-otp'));
        $this->assertTrue(Route::has('professional.send.otp'));
        $this->assertTrue(Route::has('professional.verify.otp'));

        $this->assertSame(
            1,
            collect(Route::getRoutes())->filter(fn ($route) => $route->getName() === 'register.verify-otp')->count()
        );
    }

    public function test_customer_forgot_page_has_no_otp_ui_and_uses_email(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Email Address', false)
            ->assertDontSee('Verify OTP', false)
            ->assertDontSee('Resend OTP', false)
            ->assertDontSee('name="otp"', false)
            ->assertDontSee('Mobile number', false);
    }

    public function test_professional_recovery_delivers_email_reset_not_sms_otp(): void
    {
        Notification::fake();

        $professional = Professional::query()->create([
            'name' => 'OTP Audit Pro',
            'email' => 'otp-audit-pro@example.test',
            'phone' => '9333300001',
            'password' => Hash::make('existing-password'),
            'status' => 'accepted',
        ]);

        $this->post('/professional/forgot-password', ['email' => $professional->email])
            ->assertSessionHas('status', AccountRecovery::PROFESSIONAL_SENT_MESSAGE);

        Notification::assertSentTo($professional, ProfessionalResetPassword::class);
        Notification::assertNotSentTo($professional, CustomerResetPassword::class);
    }

    public function test_professional_forgot_page_uses_email_not_mobile_or_otp(): void
    {
        $this->get('/professional/forgot-password')
            ->assertOk()
            ->assertSee('Email address', false)
            ->assertSee('name="email"', false)
            ->assertDontSee('Mobile number', false)
            ->assertDontSee('name="phone"', false)
            ->assertDontSee('Verify OTP', false)
            ->assertDontSee('Resend OTP', false);
    }

    public function test_unregistered_professional_email_does_not_send_reset(): void
    {
        Notification::fake();

        $this->from('/professional/forgot-password')
            ->post('/professional/forgot-password', ['email' => 'unknown-pro@example.test'])
            ->assertSessionHas('status', AccountRecovery::PROFESSIONAL_SENT_MESSAGE)
            ->assertSessionMissing('recovery_unregistered');

        Notification::assertNothingSent();
    }

    public function test_registration_otp_verify_does_not_authorize_password_reset(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Reg OTP Customer',
            'email' => 'reg-otp-customer@example.test',
            'phone' => '9444400001',
            'password' => 'original-password-99',
            'registration_completed' => true,
        ]);

        // Even with a forged registration OTP session, reset still requires a broker token.
        $this->withSession([
            'registration_otp' => '123456',
            'otp_expiry' => now()->addMinutes(10)->toDateTimeString(),
            'email_verified' => true,
            'registration_email' => $user->email,
        ])->from('/reset-password')
            ->post('/reset-password', [
                'token' => 'registration-otp-is-not-a-reset-token',
                'email' => $user->email,
                'password' => 'hijacked-password-99',
                'password_confirmation' => 'hijacked-password-99',
            ])
            ->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertTrue(Hash::check('original-password-99', $user->password));
        $this->assertFalse(Hash::check('hijacked-password-99', $user->password));
    }

    public function test_professional_registration_otp_session_cannot_reset_professional_password(): void
    {
        $professional = Professional::query()->create([
            'name' => 'Reg OTP Pro',
            'email' => 'reg-otp-pro@example.test',
            'phone' => '9555500001',
            'password' => Hash::make('original-pro-password'),
            'status' => 'accepted',
        ]);

        $this->withSession([
            'registration_otp' => '654321',
            'otp_expiry' => now()->addMinutes(10)->toDateTimeString(),
            'email_verified' => true,
            'registration_email' => $professional->email,
        ])->from('/professional/reset-password')
            ->post('/professional/reset-password', [
                'token' => 'registration-otp-is-not-a-reset-token',
                'email' => $professional->email,
                'password' => 'hijacked-pro-password',
                'password_confirmation' => 'hijacked-pro-password',
            ])
            ->assertSessionHasErrors('email');

        $professional->refresh();
        $this->assertTrue(Hash::check('original-pro-password', $professional->password));
    }

    public function test_direct_password_reset_without_valid_token_is_rejected(): void
    {
        User::query()->create([
            'name' => 'Direct Reset Customer',
            'email' => 'direct-reset@example.test',
            'phone' => '9666600001',
            'password' => 'keep-this-password',
            'registration_completed' => true,
        ]);

        $this->post('/reset-password', [
            'token' => 'missing-otp-or-token',
            'email' => 'direct-reset@example.test',
            'password' => 'should-not-apply-99',
            'password_confirmation' => 'should-not-apply-99',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check(
            'keep-this-password',
            User::query()->where('email', 'direct-reset@example.test')->first()->password
        ));
    }

    public function test_customer_email_recovery_still_works_after_otp_audit_clarifications(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Email Recovery Regression',
            'email' => 'email-recovery-regression@example.test',
            'phone' => '9777700001',
            'password' => 'already-set-password',
            'registration_completed' => true,
        ]);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', AccountRecovery::SENT_MESSAGE);

        Notification::assertSentTo($user, CustomerResetPassword::class);
    }

    private function storage(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('professional_password_reset_tokens');
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

        Schema::create('professional_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
}
