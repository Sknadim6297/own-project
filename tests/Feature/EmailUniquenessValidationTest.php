<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use App\Services\AccountRecovery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailUniquenessValidationTest extends TestCase
{
    private AccountRecovery $recovery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage();
        $this->recovery = app(AccountRecovery::class);
    }

    public function test_normalize_email_trims_and_lowercases(): void
    {
        $this->assertSame('person@example.test', AccountRecovery::normalizeEmail('  Person@Example.TEST  '));
    }

    public function test_customer_email_taken_is_case_insensitive_within_users_table(): void
    {
        User::query()->create([
            'name' => 'Existing Customer',
            'email' => 'customer@example.test',
            'phone' => '9911100001',
            'password' => 'secret-password',
            'registration_completed' => true,
        ]);

        $this->assertTrue($this->recovery->customerEmailTaken('CUSTOMER@example.test'));
        $this->assertTrue($this->recovery->customerEmailTaken('  customer@example.test '));
        $this->assertFalse($this->recovery->customerEmailTaken('other@example.test'));
    }

    public function test_professional_email_taken_is_case_insensitive_within_professionals_table(): void
    {
        Professional::query()->create([
            'name' => 'Existing Professional',
            'email' => 'pro@example.test',
            'phone' => '9911100002',
            'password' => Hash::make('secret-password'),
            'status' => 'accepted',
        ]);

        $this->assertTrue($this->recovery->professionalEmailTaken('PRO@example.test'));
        $this->assertFalse($this->recovery->professionalEmailTaken('customer@example.test'));
    }

    public function test_cross_role_shared_email_is_permitted(): void
    {
        User::query()->create([
            'name' => 'Shared Customer',
            'email' => 'both@example.test',
            'phone' => '9911100003',
            'password' => 'secret-password',
            'registration_completed' => true,
        ]);

        Professional::query()->create([
            'name' => 'Shared Professional',
            'email' => 'both@example.test',
            'phone' => '9911100004',
            'password' => Hash::make('secret-password'),
            'status' => 'accepted',
        ]);

        $this->assertTrue($this->recovery->customerEmailTaken('both@example.test'));
        $this->assertTrue($this->recovery->professionalEmailTaken('both@example.test'));
        $this->assertFalse($this->recovery->customerEmailTaken('both@example.test', User::query()->first()->id));
    }

    public function test_professional_send_otp_rejects_duplicate_case_variant(): void
    {
        Professional::query()->create([
            'name' => 'Dup Pro',
            'email' => 'dup-pro@example.test',
            'phone' => '9911100005',
            'password' => Hash::make('secret-password'),
            'status' => 'accepted',
        ]);

        $this->postJson(route('professional.send.otp'), [
            'email' => 'DUP-PRO@example.test',
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'This email address is already registered. Please login instead.']);
    }

    private function storage(): void
    {
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
    }
}
