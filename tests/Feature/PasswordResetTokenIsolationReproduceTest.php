<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Notifications\ProfessionalResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 09 reproduction against the pre-fix shared token table.
 * Kept as documentation of the vulnerability; assertions describe the bug.
 */
class PasswordResetTokenIsolationReproduceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->storageSharedOnly();
        config([
            'auth.passwords.users.table' => 'password_reset_tokens',
            'auth.passwords.professionals.table' => 'password_reset_tokens',
            'auth.passwords.users.throttle' => 0,
            'auth.passwords.professionals.throttle' => 0,
        ]);
    }

    public function test_shared_token_table_second_role_request_overwrites_first_token(): void
    {
        Notification::fake();

        $email = 'shared-collision@example.test';

        $customer = User::query()->create([
            'name' => 'Collision Customer',
            'email' => $email,
            'phone' => '9811100001',
            'password' => 'customer-password-old',
            'registration_completed' => true,
        ]);

        $professional = Professional::query()->create([
            'name' => 'Collision Professional',
            'email' => $email,
            'phone' => '9811100002',
            'password' => Hash::make('professional-password-old'),
            'status' => 'accepted',
        ]);

        $this->post('/forgot-password', ['email' => $email])->assertRedirect();
        Notification::assertSentTo($customer, CustomerResetPassword::class);
        $tokenAfterCustomer = DB::table('password_reset_tokens')->where('email', $email)->value('token');

        $this->post('/professional/forgot-password', ['email' => $email])->assertRedirect();
        Notification::assertSentTo($professional, ProfessionalResetPassword::class);

        $rows = DB::table('password_reset_tokens')->where('email', $email)->get();
        $this->assertCount(1, $rows, 'Shared primary key allows only one token row per email');
        $this->assertNotSame(
            $tokenAfterCustomer,
            $rows->first()->token,
            'Professional request overwrites the Customer token hash for the same email'
        );
    }

    public function test_shared_token_table_allows_customer_issued_token_to_reset_professional(): void
    {
        Notification::fake();

        $email = 'cross-accept@example.test';

        User::query()->create([
            'name' => 'Cross Customer',
            'email' => $email,
            'phone' => '9822200001',
            'password' => 'customer-password-old',
            'registration_completed' => true,
        ]);

        $professional = Professional::query()->create([
            'name' => 'Cross Professional',
            'email' => $email,
            'phone' => '9822200002',
            'password' => Hash::make('professional-password-old'),
            'status' => 'accepted',
        ]);

        $customerPlainToken = null;
        $this->post('/forgot-password', ['email' => $email]);
        Notification::assertSentTo(
            User::query()->where('email', $email)->first(),
            CustomerResetPassword::class,
            function (CustomerResetPassword $notification) use (&$customerPlainToken) {
                $customerPlainToken = $notification->token;

                return true;
            }
        );

        $status = Password::broker('professionals')->reset(
            [
                'email' => $email,
                'password' => 'hijacked-via-customer-token',
                'password_confirmation' => 'hijacked-via-customer-token',
                'token' => $customerPlainToken,
            ],
            function (Professional $account, string $password) {
                $account->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        $this->assertSame(
            Password::PASSWORD_RESET,
            $status,
            'Customer-issued token in shared table is accepted by the professionals broker'
        );

        $professional->refresh();
        $this->assertTrue(Hash::check('hijacked-via-customer-token', $professional->password));
    }

    private function storageSharedOnly(): void
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
    }
}
