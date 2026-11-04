<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MyBookingsIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_guest_is_redirected_from_my_bookings(): void
    {
        $this->storage();

        $this->get(route('user.my-bookings'))
            ->assertRedirect();
    }

    public function test_customer_sees_only_their_own_bookings_and_empty_state_for_others(): void
    {
        $this->storage();

        $owner = $this->member('owner-bookings@example.com');
        $other = $this->member('other-bookings@example.com');
        $professional = $this->professional();

        $ownerBooking = $this->booking($owner, $professional, 'Owner Career Consult');
        $this->booking($other, $professional, 'Other Career Consult');

        $this->actingAs($owner, 'user')
            ->get(route('user.my-bookings'))
            ->assertOk()
            ->assertSee('My Bookings')
            ->assertSee('Owner Career Consult')
            ->assertSee('Booking #' . $ownerBooking->id)
            ->assertDontSee('Other Career Consult');

        $this->actingAs($other, 'user')
            ->get(route('user.my-bookings'))
            ->assertOk()
            ->assertSee('Other Career Consult')
            ->assertDontSee('Owner Career Consult');

        $empty = $this->member('empty-bookings@example.com');

        $this->actingAs($empty, 'user')
            ->get(route('user.my-bookings'))
            ->assertOk()
            ->assertSee('No bookings found')
            ->assertSee('Browse professionals');
    }

    public function test_my_bookings_paginates_for_the_authenticated_user_guard(): void
    {
        $this->storage();

        $owner = $this->member('paginate-bookings@example.com');
        $professional = $this->professional();

        for ($i = 1; $i <= 11; $i++) {
            $booking = $this->booking($owner, $professional, sprintf('Pagination Case %02d', $i));
            $booking->forceFill([
                'created_at' => now()->subMinutes(12 - $i),
                'updated_at' => now()->subMinutes(12 - $i),
            ])->save();
        }

        $this->actingAs($owner, 'user')
            ->get(route('user.my-bookings'))
            ->assertOk()
            ->assertSee('11 bookings')
            ->assertSee('Pagination Case 11')
            ->assertDontSee('Pagination Case 01');

        $this->actingAs($owner, 'user')
            ->get(route('user.my-bookings', ['page' => 2]))
            ->assertOk()
            ->assertSee('Pagination Case 01')
            ->assertDontSee('Pagination Case 11');
    }

    public function test_controller_queries_the_user_guard_not_the_web_guard(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Customer/MyBookingController.php'));

        $this->assertStringContainsString("Auth::guard('user')->id()", $source);
        $this->assertStringNotContainsString("Auth::guard('web')->id()", $source);
        $this->assertStringContainsString('abort(401)', $source);
        $this->assertStringNotContainsString("'chat.messages'", $source);
    }

    private function storage(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('profile_image')->nullable();
            $table->timestamps();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('professional_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('booking_date')->nullable();
            $table->timestamps();
        });
    }

    private function member(string $email): User
    {
        return User::query()->create([
            'name' => 'Customer Member',
            'email' => $email,
            'password' => 'password-123',
        ]);
    }

    private function professional(): Professional
    {
        return Professional::query()->create([
            'name' => 'Test Professional',
            'email' => 'pro-' . uniqid() . '@example.com',
            'password' => 'password-123',
        ]);
    }

    private function booking(User $user, Professional $professional, string $serviceName): Booking
    {
        return Booking::query()->create([
            'user_id' => $user->id,
            'professional_id' => $professional->id,
            'service_name' => $serviceName,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'amount' => 1000,
            'booking_date' => now()->toDateString(),
        ]);
    }
}
