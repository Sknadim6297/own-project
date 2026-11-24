<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Notifications\ProfessionalResetPassword;
use App\Services\AccountRecovery;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetTokenIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->storage();
        config([
            'auth.passwords.users.table' => 'password_reset_tokens',
            'auth.passwords.professionals.table' => 'professional_password_reset_tokens',
            'auth.passwords.users.throttle' => 0,
            'auth.passwords.professionals.throttle' => 0,
        ]);
    }

    public function test_auth_config_uses_separate_professional_token_table(): void
    {
        $this->assertSame('password_reset_tokens', config('auth.passwords.users.table'));
        $this->assertSame('professional_password_reset_tokens', config('auth.passwords.professionals.table'));
    }

    public function test_simultaneous_shared_email_resets_store_independent_tokens(): void
    {
        Notification::fake();
        [$customer, $professional, $email] = $this->sharedEmailAccounts();

        $this->post('/forgot-password', ['email' => $email])->assertRedirect();
        $this->post('/professional/forgot-password', ['email' => $professional->email])->assertRedirect();

        Notification::assertSentTo($customer, CustomerResetPassword::class);
        Notification::assertSentTo($professional, ProfessionalResetPassword::class);

        $this->assertSame(1, DB::table('password_reset_tokens')->where('email', $email)->count());
        $this->assertSame(1, DB::table('professional_password_reset_tokens')->where('email', $email)->count());
        $this->assertNotSame(
            DB::table('password_reset_tokens')->where('email', $email)->value('token'),
            DB::table('professional_password_reset_tokens')->where('email', $email)->value('token')
        );
    }

    public function test_customer_token_is_rejected_by_professional_reset_endpoint(): void
    {
        Notification::fake();
        [$customer, $professional, $email] = $this->sharedEmailAccounts();

        $customerToken = null;
        $this->post('/forgot-password', ['email' => $email]);
        Notification::assertSentTo($customer, CustomerResetPassword::class, function ($notification) use (&$customerToken) {
            $customerToken = $notification->token;

            return true;
        });

        $this->post('/professional/reset-password', [
            'token' => $customerToken,
            'email' => $email,
            'password' => 'should-not-apply-pro',
            'password_confirmation' => 'should-not-apply-pro',
        ])->assertSessionHasErrors('email');

        $professional->refresh();
        $customer->refresh();
        $this->assertTrue(Hash::check('professional-password-old', $professional->password));
        $this->assertTrue(Hash::check('customer-password-old', $customer->password));
    }

    public function test_professional_token_is_rejected_by_customer_reset_endpoint(): void
    {
        Notification::fake();
        [$customer, $professional, $email] = $this->sharedEmailAccounts();

        $professionalToken = null;
        $this->post('/professional/forgot-password', ['email' => $professional->email]);
        Notification::assertSentTo($professional, ProfessionalResetPassword::class, function ($notification) use (&$professionalToken) {
            $professionalToken = $notification->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $professionalToken,
            'email' => $email,
            'password' => 'should-not-apply-customer',
            'password_confirmation' => 'should-not-apply-customer',
        ])->assertSessionHasErrors('email');

        $professional->refresh();
        $customer->refresh();
        $this->assertTrue(Hash::check('professional-password-old', $professional->password));
        $this->assertTrue(Hash::check('customer-password-old', $customer->password));
    }

    public function test_both_role_tokens_remain_independently_usable(): void
    {
        Notification::fake();
        Event::fake([PasswordReset::class]);
        [$customer, $professional, $email] = $this->sharedEmailAccounts();

        $customerToken = null;
        $professionalToken = null;

        $this->post('/forgot-password', ['email' => $email]);
        $this->post('/professional/forgot-password', ['email' => $professional->email]);

        Notification::assertSentTo($customer, CustomerResetPassword::class, function ($notification) use (&$customerToken) {
            $customerToken = $notification->token;

            return true;
        });
        Notification::assertSentTo($professional, ProfessionalResetPassword::class, function ($notification) use (&$professionalToken) {
            $professionalToken = $notification->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $customerToken,
            'email' => $email,
            'password' => 'customer-password-new',
            'password_confirmation' => 'customer-password-new',
        ])->assertRedirect(route('login'));

        $customer->refresh();
        $professional->refresh();
        $this->assertTrue(Hash::check('customer-password-new', $customer->password));
        $this->assertTrue(Hash::check('professional-password-old', $professional->password));
        $this->assertSame(1, DB::table('professional_password_reset_tokens')->where('email', $email)->count());

        $this->post('/professional/reset-password', [
            'token' => $professionalToken,
            'email' => $email,
            'password' => 'professional-password-new',
            'password_confirmation' => 'professional-password-new',
        ])->assertRedirect(route('professional.login'));

        $professional->refresh();
        $this->assertTrue(Hash::check('professional-password-new', $professional->password));
        $this->assertTrue(Hash::check('customer-password-new', $customer->fresh()->password));
    }

    public function test_resetting_one_role_does_not_invalidate_the_other_token(): void
    {
        Notification::fake();
        [$customer, $professional, $email] = $this->sharedEmailAccounts();

        $professionalToken = null;
        $this->post('/forgot-password', ['email' => $email]);
        $this->post('/professional/forgot-password', ['email' => $professional->email]);
        Notification::assertSentTo($professional, ProfessionalResetPassword::class, function ($notification) use (&$professionalToken) {
            $professionalToken = $notification->token;

            return true;
        });

        $customerToken = null;
        Notification::assertSentTo($customer, CustomerResetPassword::class, function ($notification) use (&$customerToken) {
            $customerToken = $notification->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $customerToken,
            'email' => $email,
            'password' => 'customer-only-new',
            'password_confirmation' => 'customer-only-new',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', $email)->count());
        $this->assertSame(1, DB::table('professional_password_reset_tokens')->where('email', $email)->count());

        $this->post('/professional/reset-password', [
            'token' => $professionalToken,
            'email' => $email,
            'password' => 'professional-still-valid',
            'password_confirmation' => 'professional-still-valid',
        ])->assertRedirect(route('professional.login'));

        $this->assertTrue(Hash::check('professional-still-valid', $professional->fresh()->password));
    }

    public function test_reused_and_invalid_tokens_are_rejected_per_role(): void
    {
        Notification::fake();
        [$customer, $professional, $email] = $this->sharedEmailAccounts();

        $customerToken = null;
        $this->post('/forgot-password', ['email' => $email]);
        Notification::assertSentTo($customer, CustomerResetPassword::class, function ($notification) use (&$customerToken) {
            $customerToken = $notification->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $customerToken,
            'email' => $email,
            'password' => 'first-reset-ok',
            'password_confirmation' => 'first-reset-ok',
        ])->assertRedirect(route('login'));

        $this->post('/reset-password', [
            'token' => $customerToken,
            'email' => $email,
            'password' => 'reuse-attempt',
            'password_confirmation' => 'reuse-attempt',
        ])->assertSessionHasErrors('email');

        $this->post('/professional/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $email,
            'password' => 'invalid-attempt',
            'password_confirmation' => 'invalid-attempt',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('professional-password-old', $professional->fresh()->password));
    }

    public function test_expired_professional_token_is_rejected(): void
    {
        Notification::fake();
        [, $professional, $email] = $this->sharedEmailAccounts();

        $token = null;
        $this->post('/professional/forgot-password', ['email' => $professional->email]);
        Notification::assertSentTo($professional, ProfessionalResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        DB::table('professional_password_reset_tokens')
            ->where('email', $email)
            ->update(['created_at' => now()->subHours(2)]);

        $this->post('/professional/reset-password', [
            'token' => $token,
            'email' => $email,
            'password' => 'too-late-password',
            'password_confirmation' => 'too-late-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('professional-password-old', $professional->fresh()->password));
    }

    public function test_cross_role_email_sharing_remains_permitted_for_accounts(): void
    {
        [$customer, $professional, $email] = $this->sharedEmailAccounts();

        $this->assertSame($email, $customer->email);
        $this->assertSame($email, $professional->email);
        $this->assertNotSame($customer->getTable(), $professional->getTable());
    }

    public function test_professional_broker_ignores_tokens_left_only_in_shared_customer_table(): void
    {
        [$customer, $professional, $email] = $this->sharedEmailAccounts();

        // Pre-migration leftover: a token row only in the Customer table.
        // Migration clears these in production; if one remains, professionals broker must ignore it.
        $legacyPlain = 'legacy-shared-plain-token';
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($legacyPlain),
            'created_at' => now(),
        ]);

        $status = Password::broker('professionals')->reset(
            [
                'email' => $email,
                'password' => 'pro-via-legacy-shared',
                'password_confirmation' => 'pro-via-legacy-shared',
                'token' => $legacyPlain,
            ],
            function (Professional $account, string $password) {
                $account->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        $this->assertNotSame(Password::PASSWORD_RESET, $status);
        $this->assertTrue(Hash::check('professional-password-old', $professional->fresh()->password));
        $this->assertTrue(Hash::check('customer-password-old', $customer->fresh()->password));
        $this->assertSame(0, DB::table('professional_password_reset_tokens')->where('email', $email)->count());
    }

    public function test_customer_email_recovery_message_unchanged(): void
    {
        Notification::fake();
        User::query()->create([
            'name' => 'Solo Customer',
            'email' => 'solo-customer@example.test',
            'phone' => '9833300001',
            'password' => 'already-set',
            'registration_completed' => true,
        ]);

        $this->post('/forgot-password', ['email' => 'solo-customer@example.test'])
            ->assertSessionHas('status', AccountRecovery::SENT_MESSAGE);
    }

    /**
     * @return array{0: User, 1: Professional, 2: string}
     */
    private function sharedEmailAccounts(): array
    {
        $email = 'shared-reset@example.test';

        $customer = User::query()->create([
            'name' => 'Shared Customer',
            'email' => $email,
            'phone' => '9844400001',
            'password' => 'customer-password-old',
            'registration_completed' => true,
        ]);

        $professional = Professional::query()->create([
            'name' => 'Shared Professional',
            'email' => $email,
            'phone' => '9844400002',
            'password' => Hash::make('professional-password-old'),
            'status' => 'accepted',
        ]);

        return [$customer, $professional, $email];
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
