<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Notifications\ProfessionalResetPassword;
use App\Services\AccountRecovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

    public function test_unknown_mobile_shows_the_registration_alert_and_does_not_send_a_reset(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['phone' => '9000000001'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('recovery_unregistered', true)
            ->assertSessionMissing('status');

        Notification::assertNothingSent();

        session()->flash('recovery_unregistered', true);
        $html = view('frontend.login.forget-password')->render();

        $this->assertStringContainsString(AccountRecovery::UNREGISTERED_TITLE, $html);
        $this->assertStringContainsString(AccountRecovery::UNREGISTERED_MESSAGE, $html);
        $this->assertStringContainsString(route('register'), $html);
        $this->assertStringContainsString('Register Now', $html);
        $this->assertStringNotContainsString("If an account matches your details", $html);
    }

    public function test_incomplete_customer_registration_is_not_treated_as_registered(): void
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
            ->post('/forgot-password', ['phone' => '9876543210'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('recovery_unregistered', true);

        Notification::assertNothingSent();
    }

    public function test_registered_customer_receives_the_existing_reset_without_exposing_the_email(): void
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
            ->post('/forgot-password', ['phone' => '9876543210']);

        $response->assertRedirect('/forgot-password')
            ->assertSessionHas('status', AccountRecovery::SENT_MESSAGE)
            ->assertSessionMissing('recovery_unregistered');

        $this->assertStringNotContainsString($user->email, (string) session('status'));
        Notification::assertSentTo($user, CustomerResetPassword::class);

        session()->flash('status', AccountRecovery::SENT_MESSAGE);
        $html = view('frontend.login.forget-password')->render();
        $this->assertStringContainsString(AccountRecovery::SENT_MESSAGE, $html);
        $this->assertStringNotContainsString($user->email, $html);
        $this->assertStringNotContainsString('role="alert"', $html);
        $this->assertStringNotContainsString('Register Now', $html);
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

    public function test_password_reset_endpoint_does_not_reveal_the_mobile_registration_result(): void
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
            $this->post('/forgot-password', ['phone' => '9000000099'])->assertRedirect();
        }

        $this->post('/forgot-password', ['phone' => '9000000099'])->assertStatus(429);
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
