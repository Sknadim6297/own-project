<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventBookingSuccessSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_event_success_redirects_when_success_session_is_missing(): void
    {
        $this->storage();
        $user = $this->customer();
        $this->eventBookingFor($user, 'Prior Programme');

        $this->actingAs($user, 'user')
            ->get(route('user.event.booking.success'))
            ->assertRedirect(route('user.dashboard'))
            ->assertSessionHas('error')
            ->assertDontSee('Prior Programme');
    }

    public function test_event_success_rejects_foreign_booking_reference(): void
    {
        $this->storage();
        $owner = $this->customer('event-owner');
        $intruder = $this->customer('event-intruder');
        $foreign = $this->eventBookingFor($owner, 'Owner Programme Secret');

        $this->actingAs($intruder, 'user')
            ->withSession([
                'event_booking_success' => [
                    'booking_id' => $foreign->id,
                    'user_id' => $intruder->id,
                ],
            ])
            ->get(route('user.event.booking.success'))
            ->assertRedirect(route('user.dashboard'))
            ->assertSessionHas('error')
            ->assertSessionMissing('event_booking_success')
            ->assertDontSee('Owner Programme Secret');
    }

    public function test_event_success_renders_owned_verified_session_booking(): void
    {
        $this->storage();
        $user = $this->customer('event-owner-ok');
        $booking = $this->eventBookingFor($user, 'QA Programme Confirmed');

        $this->actingAs($user, 'user')
            ->withSession([
                'event_booking_success' => [
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                ],
            ])
            ->get(route('user.event.booking.success'))
            ->assertOk()
            ->assertSee('Your booking has been confirmed!')
            ->assertSee('QA Programme Confirmed')
            ->assertSee('Booking ID:')
            ->assertSee((string) $booking->id);
    }

    public function test_legacy_event_booking_success_route_requires_auth_and_owned_session(): void
    {
        $this->storage();
        $user = $this->customer('legacy-event');
        $booking = $this->eventBookingFor($user, 'Legacy Route Programme');

        $this->get(route('event.booking.success'))
            ->assertRedirect();

        $this->actingAs($user, 'user')
            ->get(route('event.booking.success'))
            ->assertRedirect(route('user.dashboard'));

        $this->actingAs($user, 'user')
            ->withSession([
                'event_booking_success' => [
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                ],
            ])
            ->get(route('event.booking.success'))
            ->assertOk()
            ->assertSee('Legacy Route Programme');
    }

    public function test_event_success_blade_no_longer_loads_latest_booking_fallback(): void
    {
        $blade = file_get_contents(resource_path('views/customer/booking/event_success.blade.php'));

        $this->assertStringNotContainsString('EventBooking::where(\'user_id\'', $blade);
        $this->assertStringNotContainsString('->latest()', $blade);
        $this->assertStringContainsString('@if(isset($booking))', $blade);
    }

    private function eventBookingFor(User $user, string $eventName): EventBooking
    {
        return EventBooking::query()->create([
            'user_id' => $user->id,
            'event_id' => null,
            'event_name' => $eventName,
            'event_date' => now()->addWeek()->toDateString(),
            'location' => 'Online',
            'type' => 'online',
            'persons' => 1,
            'phone' => '9999999999',
            'price' => 299,
            'total_price' => 299,
            'payment_status' => 'success',
            'razorpay_payment_id' => 'pay_test_' . uniqid(),
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

        Schema::create('all_events', function (Blueprint $table) {
            $table->id();
            $table->string('heading')->nullable();
            $table->string('status')->nullable();
            $table->string('city')->nullable();
            $table->string('meet_link')->nullable();
            $table->timestamps();
        });

        Schema::create('event_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('event_id')->nullable();
            $table->string('event_name')->nullable();
            $table->date('event_date')->nullable();
            $table->string('location')->nullable();
            $table->string('type')->nullable();
            $table->unsignedInteger('persons')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->string('order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->timestamps();
        });
    }

    private function customer(string $suffix = ''): User
    {
        return User::query()->create([
            'name' => 'QA Event Customer',
            'email' => 'event-success-' . ($suffix !== '' ? $suffix . '-' : '') . uniqid() . '@example.com',
            'password' => 'password-123',
        ]);
    }
}
