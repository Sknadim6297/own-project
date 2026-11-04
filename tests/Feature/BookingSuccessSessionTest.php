<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingSuccessSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_booking_success_redirects_when_session_is_missing(): void
    {
        $this->storage();
        $user = $this->customer();

        $this->actingAs($user, 'user')
            ->get(route('user.booking.success'))
            ->assertRedirect(route('user.upcoming-appointment.index'))
            ->assertSessionHas('error');
    }

    public function test_booking_success_redirects_when_bookings_list_is_missing(): void
    {
        $this->storage();
        $user = $this->customer();

        $this->actingAs($user, 'user')
            ->withSession([
                'booking_success' => [
                    'service_name' => 'Career Guidance',
                    'amount' => 1000,
                ],
            ])
            ->get(route('user.booking.success'))
            ->assertRedirect(route('user.upcoming-appointment.index'))
            ->assertSessionHas('error')
            ->assertSessionMissing('booking_success');
    }

    public function test_own_booking_success_renders_with_owned_booking_id(): void
    {
        $this->storage();
        $user = $this->customer();
        $booking = $this->bookingFor($user, 'QA Career Guidance');

        $this->actingAs($user, 'user')
            ->withSession([
                'booking_success' => $this->successPayload($user, $booking),
            ])
            ->get(route('user.booking.success'))
            ->assertOk()
            ->assertSee('Booking Confirmed!')
            ->assertSee('QA Career Guidance')
            ->assertSee('QA Professional')
            ->assertSee('10:00 AM - 10:30 AM')
            ->assertSee('YuktiOne | Booking Confirmed', false);
    }

    public function test_foreign_booking_id_in_session_is_rejected(): void
    {
        $this->storage();
        $owner = $this->customer('owner');
        $intruder = $this->customer('intruder');
        $foreignBooking = $this->bookingFor($owner, 'Owner Only Consult');

        $this->actingAs($intruder, 'user')
            ->withSession([
                'booking_success' => $this->successPayload($intruder, $foreignBooking, 'Stolen Consult'),
            ])
            ->get(route('user.booking.success'))
            ->assertRedirect(route('user.upcoming-appointment.index'))
            ->assertSessionHas('error')
            ->assertSessionMissing('booking_success')
            ->assertDontSee('Stolen Consult');
    }

    public function test_mismatched_session_user_id_is_rejected(): void
    {
        $this->storage();
        $owner = $this->customer('owner-uid');
        $intruder = $this->customer('intruder-uid');

        $this->actingAs($intruder, 'user')
            ->withSession([
                'booking_success' => [
                    'user_id' => $owner->id,
                    'service_name' => 'Should Not Show',
                    'professional_name' => 'Hidden Pro',
                    'bookings' => [
                        ['date' => now()->addDay()->toDateString(), 'time_slot' => '11:00 AM - 11:30 AM'],
                    ],
                ],
            ])
            ->get(route('user.booking.success'))
            ->assertRedirect(route('user.upcoming-appointment.index'))
            ->assertSessionHas('error')
            ->assertSessionMissing('booking_success');
    }

    public function test_legacy_and_canonical_success_uris_share_ownership_guard(): void
    {
        $this->storage();
        $owner = $this->customer('dual-route-owner');
        $intruder = $this->customer('dual-route-intruder');
        $foreignBooking = $this->bookingFor($owner, 'Dual Route Booking');

        $stolenSession = [
            'booking_success' => $this->successPayload($intruder, $foreignBooking, 'Dual Route Leak'),
        ];

        $this->actingAs($intruder, 'user')
            ->withSession($stolenSession)
            ->get('/user/booking/success')
            ->assertRedirect(route('user.upcoming-appointment.index'));

        $this->actingAs($intruder, 'user')
            ->withSession($stolenSession)
            ->get('/booking/success')
            ->assertRedirect(route('user.upcoming-appointment.index'));

        $owned = $this->bookingFor($intruder, 'Intruder Own Booking');

        $this->actingAs($intruder, 'user')
            ->withSession([
                'booking_success' => $this->successPayload($intruder, $owned, 'Intruder Own Booking'),
            ])
            ->get('/booking/success')
            ->assertOk()
            ->assertSee('Intruder Own Booking');
    }

    public function test_success_blade_does_not_assume_non_array_bookings_without_guard(): void
    {
        $blade = file_get_contents(resource_path('views/customer/booking/success.blade.php'));

        $this->assertStringContainsString('is_array($successBookings)', $blade);
        $this->assertStringContainsString('@forelse($successBookings as $booking)', $blade);
    }

    private function successPayload(User $user, Booking $booking, string $serviceName = 'QA Career Guidance'): array
    {
        return [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'professional_name' => 'QA Professional',
            'professional_address' => 'QA Address',
            'service_name' => $serviceName,
            'plan_type' => 'one_time',
            'base_amount' => 847.46,
            'cgst' => 76.27,
            'sgst' => 76.27,
            'igst' => 0,
            'amount' => 1000,
            'bookings' => [
                [
                    'date' => now()->addDay()->toDateString(),
                    'time_slot' => '10:00 AM - 10:30 AM',
                ],
            ],
        ];
    }

    private function bookingFor(User $user, string $serviceName): Booking
    {
        return Booking::query()->create([
            'user_id' => $user->id,
            'service_name' => $serviceName,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'amount' => 1000,
            'booking_date' => now()->toDateString(),
        ]);
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

    private function customer(string $suffix = ''): User
    {
        return User::query()->create([
            'name' => 'QA Customer',
            'email' => 'booking-success-' . ($suffix !== '' ? $suffix . '-' : '') . uniqid() . '@example.com',
            'password' => 'password-123',
        ]);
    }
}
