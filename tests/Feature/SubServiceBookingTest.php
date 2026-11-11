<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Professional;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sub-service persistence on user.booking.store.
 *
 * Avoids RefreshDatabase: several historical migrations use MySQL-only
 * information_schema lookups and ->change() (doctrine/dbal), which break
 * PHPUnit's SQLite :memory: connection. Minimal tables preserve the booking
 * contract without pulling the full production migration graph.
 */
class SubServiceBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);

        foreach ([
            'booking_timedates',
            'bookings',
            'sub_services',
            'professionals',
            'customer_profiles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('profile_image')->nullable();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('sub_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('professional_id');
            $table->unsignedBigInteger('sub_service_id')->nullable();
            $table->string('sub_service_name')->nullable();
            $table->string('service_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('session_type')->nullable();
            $table->string('month')->nullable();
            $table->text('days')->nullable();
            $table->date('booking_date')->nullable();
            $table->text('time_slot')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_timedates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->date('date');
            $table->string('time_slot');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /** @test */
    public function it_can_store_sub_service_data_in_booking()
    {
        $user = User::query()->create([
            'name' => 'Booking Customer',
            'email' => 'booking-customer@example.com',
            'password' => 'password-123',
        ]);
        $professional = Professional::query()->create([
            'name' => 'Test Professional',
            'email' => 'pro-booking@example.com',
            'password' => 'password-123',
        ]);
        $subService = SubService::query()->create([
            'name' => 'Test Sub Service',
            'description' => 'Test Description',
            'status' => 1,
        ]);

        $this->actingAs($user, 'user');

        session([
            'booking_data' => [
                'professional_id' => $professional->id,
                'plan_type' => 'monthly',
                'bookings' => [
                    [
                        'date' => now()->addDay()->format('Y-m-d'),
                        'time_slot' => '10:00 AM - 11:00 AM',
                    ],
                ],
            ],
            'selected_service_name' => 'Test Service',
        ]);

        $response = $this->postJson(route('user.booking.store'), [
            'phone' => '1234567890',
            'sub_service_id' => $subService->id,
            'sub_service_name' => $subService->name,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Your booking has been successfully placed.',
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'professional_id' => $professional->id,
            'sub_service_id' => $subService->id,
            'sub_service_name' => $subService->name,
            'customer_phone' => '1234567890',
        ]);
        $this->assertSame(1, Booking::query()->count());
    }

    /** @test */
    public function it_can_create_booking_without_sub_service()
    {
        $user = User::query()->create([
            'name' => 'Booking Customer Two',
            'email' => 'booking-customer-2@example.com',
            'password' => 'password-123',
        ]);
        $professional = Professional::query()->create([
            'name' => 'Test Professional Two',
            'email' => 'pro-booking-2@example.com',
            'password' => 'password-123',
        ]);

        $this->actingAs($user, 'user');

        session([
            'booking_data' => [
                'professional_id' => $professional->id,
                'plan_type' => 'monthly',
                'bookings' => [
                    [
                        'date' => now()->addDay()->format('Y-m-d'),
                        'time_slot' => '10:00 AM - 11:00 AM',
                    ],
                ],
            ],
            'selected_service_name' => 'Test Service',
        ]);

        $response = $this->postJson(route('user.booking.store'), [
            'phone' => '1234567890',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'professional_id' => $professional->id,
            'sub_service_id' => null,
            'sub_service_name' => null,
            'customer_phone' => '1234567890',
        ]);
    }
}
