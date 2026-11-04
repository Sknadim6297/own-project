<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingSummarySessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_booking_summary_redirects_home_when_event_booking_data_is_missing(): void
    {
        $this->storage();
        $user = $this->customer();

        $this->actingAs($user, 'user')
            ->get(route('user.booking.summary'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    public function test_booking_summary_renders_when_event_booking_data_is_present(): void
    {
        $this->storage();
        $user = $this->customer();

        $this->actingAs($user, 'user')
            ->withSession([
                'event_booking_data' => [
                    'event_name' => 'QA Local Event',
                    'total_price' => 299,
                    'persons' => 1,
                    'type' => 'online',
                    'event_date' => now()->addWeek()->toDateString(),
                ],
            ])
            ->get(route('user.booking.summary'))
            ->assertOk()
            ->assertSee('YuktiOne | Booking Summary', false);
    }

    public function test_booking_pages_use_yuktione_titles_not_tazen(): void
    {
        $pages = [
            resource_path('views/customer/booking/booking.blade.php'),
            resource_path('views/customer/booking/summary.blade.php'),
            resource_path('views/customer/booking/success.blade.php'),
            resource_path('views/customer/booking/event_success.blade.php'),
        ];

        foreach ($pages as $page) {
            $blade = file_get_contents($page);
            $this->assertStringContainsString("config('yuktione.name', 'YuktiOne')", $blade);
            $this->assertStringNotContainsString('Tazen.in', $blade);
        }
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
            $table->timestamps();
        });
    }

    private function customer(): User
    {
        return User::query()->create([
            'name' => 'QA Customer',
            'email' => 'booking-summary-' . uniqid() . '@example.com',
            'password' => 'password-123',
        ]);
    }
}
