<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Notifications\ProfessionalResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetBrandingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->storage();
        Config::set('yuktione.name', 'YuktiOne');
        Config::set('mail.from.name', 'YuktiOne');
        Config::set('mail.from.address', 'hello@example.com');
    }

    public function test_customer_forgot_and_reset_pages_use_yuktione_branding(): void
    {
        $forgot = $this->get('/forgot-password');
        $forgot->assertOk();
        $forgot->assertSee('Forgot Password | YuktiOne', false);
        $forgot->assertSee('frontend/assets/Yuktione_logo.png', false);
        $forgot->assertSee('alt="YuktiOne"', false);
        $forgot->assertDontSee('Tazen', false);
        $forgot->assertDontSee('support@tazen.in', false);

        $reset = $this->get('/reset-password?token=test-token&email=customer@example.test');
        $reset->assertOk();
        $reset->assertSee('Reset Password | YuktiOne', false);
        $reset->assertSee('frontend/assets/Yuktione_logo.png', false);
        $reset->assertSee('YuktiOne account', false);
        $reset->assertDontSee('Tazen', false);
    }

    public function test_professional_forgot_and_reset_pages_use_yuktione_branding(): void
    {
        $forgot = $this->get('/professional/forgot-password');
        $forgot->assertOk();
        $forgot->assertSee('Forgot Password | YuktiOne', false);
        $forgot->assertSee('frontend/assets/Yuktione_logo.png', false);
        $forgot->assertSee('alt="YuktiOne"', false);
        $forgot->assertDontSee('Tazen', false);

        $reset = $this->get('/professional/reset-password?token=test-token&email=pro@example.test');
        $reset->assertOk();
        $reset->assertSee('Reset Password | YuktiOne', false);
        $reset->assertSee('frontend/assets/Yuktione_logo.png', false);
        $reset->assertDontSee('Tazen', false);
    }

    public function test_customer_reset_email_is_branded_yuktione_without_tazen(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Branding Customer',
            'email' => 'branding-customer@example.test',
            'phone' => '9876500001',
            'password' => 'already-set-password',
            'registration_completed' => true,
        ]);

        $user->sendPasswordResetNotification('customer-reset-token');

        Notification::assertSentTo($user, CustomerResetPassword::class, function (CustomerResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);
            $rendered = $mail->render();

            $this->assertSame('YuktiOne Password Reset', $mail->subject);
            $this->assertSame(['hello@example.com', 'YuktiOne'], $mail->from);
            $this->assertStringContainsString('YuktiOne', $rendered);
            $this->assertStringContainsString('Yuktione_logo.png', $rendered);
            $this->assertStringContainsString('customer account', $rendered);
            $this->assertStringContainsString('customer-reset-token', $rendered);
            $this->assertStringNotContainsString('Tazen', $rendered);
            $this->assertStringNotContainsString('support@tazen.in', $rendered);
            $this->assertStringNotContainsString('Support Tazen', $rendered);

            return true;
        });
    }

    public function test_professional_reset_email_is_branded_yuktione_and_stays_on_professional_route(): void
    {
        Notification::fake();

        $professional = Professional::query()->create([
            'name' => 'Branding Professional',
            'email' => 'branding-professional@example.test',
            'phone' => '9876500002',
            'password' => Hash::make('already-set-password'),
            'status' => 'accepted',
        ]);

        $professional->sendPasswordResetNotification('professional-reset-token');

        Notification::assertSentTo($professional, ProfessionalResetPassword::class, function (ProfessionalResetPassword $notification) use ($professional) {
            $mail = $notification->toMail($professional);
            $rendered = $mail->render();

            $this->assertSame('YuktiOne Professional Password Reset', $mail->subject);
            $this->assertSame(['hello@example.com', 'YuktiOne'], $mail->from);
            $this->assertStringContainsString('professional account', $rendered);
            $this->assertStringContainsString('Yuktione_logo.png', $rendered);
            $this->assertStringContainsString('/professional/reset-password', $rendered);
            $this->assertStringContainsString('professional-reset-token', $rendered);
            $this->assertStringNotContainsString('Tazen', $rendered);
            $this->assertStringNotContainsString('support@tazen.in', $rendered);

            return true;
        });
    }

    public function test_mail_config_defaults_are_not_legacy_tazen(): void
    {
        $mailConfig = file_get_contents(base_path('config/mail.php'));

        $this->assertStringNotContainsString("support@tazen.in", $mailConfig);
        $this->assertStringNotContainsString('Support Tazen', $mailConfig);
        $this->assertStringContainsString("'name' => env('MAIL_FROM_NAME', 'YuktiOne')", $mailConfig);
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
