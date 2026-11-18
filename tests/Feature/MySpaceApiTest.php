<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingTimedate;
use App\Models\Professional;
use App\Models\PsychologyGratitudeEntry;
use App\Models\PsychologyJournalEntry;
use App\Models\PsychologyMoodCheckin;
use App\Models\User;
use App\Notifications\AdditionalServiceNotification;
use App\Notifications\NewChatMessage;
use App\Services\YuktiRewards\YuktiCustomerRewardHooks;
use Mockery\MockInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MySpaceApiTest extends TestCase
{
    private MockInterface $rewards;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureApiActiveSession::class);
        $this->rewards = $this->mock(YuktiCustomerRewardHooks::class);
        $this->rewards->shouldReceive('onEligibleToolCompleted')->andReturnNull()->byDefault();
        $this->storage();
        Carbon::setTestNow(Carbon::parse('2026-03-15 06:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_every_my_space_endpoint_rejects_guests(): void
    {
        foreach ([
            ['GET', '/api/v1/my/bookings'],
            ['GET', '/api/v1/my/activity/summary'],
            ['GET', '/api/v1/my/mood-checkins'],
            ['GET', '/api/v1/my/journal-entries'],
            ['GET', '/api/v1/my/gratitude-entries'],
            ['GET', '/api/v1/my/notifications'],
            ['POST', '/api/v1/my/notifications/'.Str::uuid().'/read'],
            ['GET', '/api/v1/my/psychology/options'],
            ['POST', '/api/v1/my/mood-checkins'],
            ['POST', '/api/v1/my/journal-entries'],
            ['POST', '/api/v1/my/gratitude-entries'],
        ] as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }
    }

    public function test_every_my_space_route_requires_sanctum_and_the_single_session_guard(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/my/'));

        $this->assertCount(11, $routes);
        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:sanctum', $middleware, $route->uri());
            $this->assertContains('api.active.session', $middleware, $route->uri());
        }
    }

    public function test_bookings_list_only_the_signed_in_customers_rows_without_sensitive_fields(): void
    {
        $owner = $this->member('owner@example.com');
        $other = $this->member('other@example.com');
        $pro = $this->professional('Dr Asha Rao');

        $mine = $this->booking($owner, $pro, 'Owner Consult', '2026-03-20');
        $this->booking($other, $pro, 'Other Private Consult', '2026-03-21');

        Sanctum::actingAs($owner);
        $response = $this->getJson('/api/v1/my/bookings')->assertOk();

        $response->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.id', $mine->id)
            ->assertJsonPath('bookings.0.service_name', 'Owner Consult')
            ->assertJsonPath('bookings.0.professional.name', 'Dr Asha Rao')
            ->assertJsonPath('bookings.0.professional.specialization', 'Career Guidance')
            ->assertJsonPath('bookings.0.status', 'confirmed')
            ->assertJsonPath('bookings.0.status_tone', 'success')
            ->assertJsonPath('bookings.0.payment_status', 'paid')
            ->assertJsonPath('bookings.0.amount', 1000)
            ->assertJsonPath('meta.total', 1);

        $body = $response->getContent();
        $this->assertStringNotContainsString('Other Private Consult', $body);
        foreach (['razorpay', 'transaction_id', 'payment_screenshot', 'customer_document', 'remarks_for_professional', 'meeting_link', 'customer_email'] as $field) {
            $this->assertStringNotContainsString($field, $body, "Booking payload must not expose {$field}");
        }
    }

    public function test_bookings_split_into_upcoming_and_past_using_session_rows_and_status(): void
    {
        $owner = $this->member('scope@example.com');
        $pro = $this->professional('Dr Scope');

        $future = $this->booking($owner, $pro, 'Future Single', '2026-03-18');
        $past = $this->booking($owner, $pro, 'Past Single', '2026-03-01');
        $cancelled = $this->booking($owner, $pro, 'Cancelled Future', '2026-03-25', 'cancelled');

        $multi = $this->booking($owner, $pro, 'Multi Session', '2026-03-01');
        BookingTimedate::query()->create(['booking_id' => $multi->id, 'date' => '2026-03-02', 'time_slot' => '10:00 AM', 'status' => 'completed']);
        BookingTimedate::query()->create(['booking_id' => $multi->id, 'date' => '2026-03-16', 'time_slot' => '11:00 AM', 'status' => 'pending']);

        Sanctum::actingAs($owner);

        $upcoming = $this->getJson('/api/v1/my/bookings?scope=upcoming')->assertOk();
        $ids = collect($upcoming->json('bookings'))->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([$future->id, $multi->id])->sort()->values()->all(), $ids);
        $upcoming->assertJsonPath('counts.upcoming', 2)->assertJsonPath('counts.past', 2)->assertJsonPath('counts.all', 4);

        $multiRow = collect($upcoming->json('bookings'))->firstWhere('id', $multi->id);
        $this->assertSame(['date' => '2026-03-16', 'time_slot' => '11:00 AM'], $multiRow['next_session']);
        $this->assertSame(2, $multiRow['sessions_total']);
        $this->assertSame(1, $multiRow['sessions_completed']);

        $pastIds = collect($this->getJson('/api/v1/my/bookings?scope=past')->assertOk()->json('bookings'))->pluck('id')->sort()->values()->all();
        $this->assertSame(collect([$past->id, $cancelled->id])->sort()->values()->all(), $pastIds);

        $this->getJson('/api/v1/my/bookings?scope=everything')->assertUnprocessable();
    }

    public function test_bookings_paginate(): void
    {
        $owner = $this->member('pages@example.com');
        $pro = $this->professional('Dr Pages');
        for ($i = 1; $i <= 12; $i++) {
            $this->booking($owner, $pro, sprintf('Case %02d', $i), '2026-04-01')
                ->forceFill(['created_at' => now()->subMinutes(20 - $i)])->save();
        }

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/my/bookings?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'bookings')
            ->assertJsonPath('bookings.0.service_name', 'Case 12')
            ->assertJsonPath('meta.last_page', 3);
        $this->getJson('/api/v1/my/bookings?per_page=5&page=3')
            ->assertOk()
            ->assertJsonCount(2, 'bookings')
            ->assertJsonPath('bookings.1.service_name', 'Case 01');
        $this->getJson('/api/v1/my/bookings?per_page=500')->assertUnprocessable();
    }

    public function test_activity_is_scoped_to_owner_and_filtered_by_india_calendar_days(): void
    {
        $owner = $this->member('mood-owner@example.com');
        $other = $this->member('mood-other@example.com');

        // 2026-03-10 19:00 UTC is 2026-03-11 00:30 in Asia/Kolkata.
        $this->mood($owner, 'good', '2026-03-10 19:00:00');
        $this->mood($owner, 'stressed', '2026-03-12 04:00:00');
        $this->mood($owner, 'good', '2026-03-12 09:00:00');
        $this->mood($owner, 'sad', '2026-01-01 04:00:00');
        $this->mood($other, 'very-happy', '2026-03-12 05:00:00');
        $this->journal($owner, '2026-03-12 05:00:00', 'Owner private thoughts');
        $this->journal($other, '2026-03-12 05:00:00', 'Other private thoughts');
        $this->gratitude($owner, '2026-03-13 05:00:00');

        Sanctum::actingAs($owner);

        $summary = $this->getJson('/api/v1/my/activity/summary?from=2026-03-11&to=2026-03-15')->assertOk();
        $summary->assertJsonPath('range.from', '2026-03-11')
            ->assertJsonPath('range.timezone', 'Asia/Kolkata')
            ->assertJsonPath('mood.count', 3)
            ->assertJsonPath('mood.active_days', 2)
            ->assertJsonPath('mood.latest.key', 'good')
            ->assertJsonPath('journal.count', 1)
            ->assertJsonPath('gratitude.count', 1)
            ->assertJsonPath('all_time.mood', 4);
        $distribution = collect($summary->json('mood.distribution'))->pluck('count', 'key');
        $this->assertSame(2, $distribution['good']);
        $this->assertSame(1, $distribution['stressed']);
        $this->assertSame(0, $distribution['very-happy']);

        $this->getJson('/api/v1/my/activity/summary?from=2026-03-10&to=2026-03-10')
            ->assertOk()->assertJsonPath('mood.count', 0);

        $moods = $this->getJson('/api/v1/my/mood-checkins?from=2026-03-01&to=2026-03-15')->assertOk();
        $moods->assertJsonCount(3, 'entries')->assertJsonPath('entries.0.mood_label', 'Good');
        $this->assertStringNotContainsString('very-happy', $moods->getContent());

        $journal = $this->getJson('/api/v1/my/journal-entries?from=2026-03-01&to=2026-03-15')->assertOk();
        $journal->assertJsonCount(1, 'entries')->assertJsonPath('entries.0.prompt_label', 'My thoughts');
        $this->assertStringNotContainsString('Other private thoughts', $journal->getContent());

        $this->getJson('/api/v1/my/gratitude-entries?from=2026-03-01&to=2026-03-15')
            ->assertOk()
            ->assertJsonCount(1, 'entries')
            ->assertJsonPath('entries.0.answers.0.label', 'Something good today');
    }

    public function test_activity_range_validation(): void
    {
        Sanctum::actingAs($this->member('range@example.com'));

        $this->getJson('/api/v1/my/activity/summary?from=2026-03-10&to=2026-03-01')->assertUnprocessable();
        $this->getJson('/api/v1/my/activity/summary?from=2024-01-01&to=2026-03-01')->assertUnprocessable();
        $this->getJson('/api/v1/my/mood-checkins?from=not-a-date')->assertUnprocessable();
        $this->getJson('/api/v1/my/activity/summary')
            ->assertOk()
            ->assertJsonPath('range.from', '2026-02-14')
            ->assertJsonPath('range.to', '2026-03-15');
    }

    public function test_notifications_are_owned_filtered_and_markable_only_by_their_owner(): void
    {
        $owner = $this->member('n-owner@example.com');
        $other = $this->member('n-other@example.com');

        $mine = $this->notification($owner, AdditionalServiceNotification::class, 'Payment Successful');
        $this->notification($owner, NewChatMessage::class, 'Chat ping');
        $theirs = $this->notification($other, AdditionalServiceNotification::class, 'Other customer update');

        Sanctum::actingAs($owner);

        $list = $this->getJson('/api/v1/my/notifications')->assertOk();
        $list->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.id', $mine)
            ->assertJsonPath('notifications.0.title', 'Payment Successful')
            ->assertJsonPath('notifications.0.read', false)
            ->assertJsonPath('unread_count', 1);
        $this->assertStringNotContainsString('Other customer update', $list->getContent());
        $this->assertStringNotContainsString('Chat ping', $list->getContent());

        $this->postJson("/api/v1/my/notifications/{$theirs}/read")->assertNotFound();
        $this->assertNull(DB::table('notifications')->where('id', $theirs)->value('read_at'));

        $this->postJson("/api/v1/my/notifications/{$mine}/read")
            ->assertOk()
            ->assertJsonPath('notification.read', true)
            ->assertJsonPath('unread_count', 0);
        $this->assertNotNull(DB::table('notifications')->where('id', $mine)->value('read_at'));

        $this->getJson('/api/v1/my/notifications?filter=unread')->assertOk()->assertJsonCount(0, 'notifications');
    }

    public function test_bookings_and_summaries_cannot_be_written_and_empty_saves_are_rejected(): void
    {
        $owner = $this->member('readonly@example.com');
        Sanctum::actingAs($owner);

        foreach (['/api/v1/my/bookings', '/api/v1/my/activity/summary', '/api/v1/my/notifications'] as $uri) {
            $this->postJson($uri, [])->assertStatus(405);
        }
        foreach (['/api/v1/my/mood-checkins', '/api/v1/my/journal-entries', '/api/v1/my/gratitude-entries'] as $uri) {
            $this->postJson($uri, [])->assertUnprocessable();
        }

        $this->assertSame(0, Booking::query()->count());
        $this->assertSame(0, PsychologyMoodCheckin::query()->count());
        $this->assertSame(0, PsychologyJournalEntry::query()->count());
        $this->assertSame(0, PsychologyGratitudeEntry::query()->count());
    }

    public function test_options_list_the_website_catalogue(): void
    {
        Sanctum::actingAs($this->member('options@example.com'));

        $options = $this->getJson('/api/v1/my/psychology/options')->assertOk();
        $options->assertJsonPath('mood.open', true)
            ->assertJsonCount(5, 'mood.moods')
            ->assertJsonPath('mood.moods.0.key', 'very-happy')
            ->assertJsonCount(3, 'mood.emotion_groups')
            ->assertJsonPath('mood.emotion_groups.0.tone', 'positive')
            ->assertJsonCount(5, 'mood.emotion_groups.0.emotions')
            ->assertJsonPath('mood.max_emotions', 6)
            ->assertJsonPath('mood.reflection_max', 1000)
            ->assertJsonCount(6, 'journal.prompts')
            ->assertJsonPath('journal.prompts.0.key', 'my-thoughts')
            ->assertJsonPath('journal.body_max', 5000)
            ->assertJsonCount(3, 'gratitude.cards')
            ->assertJsonPath('gratitude.cards.0.key', 'something-good-today');
    }

    public function test_mood_checkin_saves_for_the_signed_in_customer_and_a_retry_is_idempotent(): void
    {
        $owner = $this->member('save-mood@example.com');
        $key = (string) Str::uuid();
        $this->rewards->shouldReceive('onEligibleToolCompleted')
            ->once()
            ->with($owner->id, 'daily-mood-check-in', $key, \Mockery::type('int'))
            ->andReturnNull();
        Sanctum::actingAs($owner);

        $payload = ['completion_key' => $key, 'mood' => 'good', 'emotions' => ['calm', 'grateful'], 'reflection' => '  A quiet morning  '];
        $this->postJson('/api/v1/my/mood-checkins', $payload)
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('entry.mood_label', 'Good')
            ->assertJsonPath('entry.emotions.0.label', 'Calm')
            ->assertJsonPath('entry.reflection', 'A quiet morning');

        $this->postJson('/api/v1/my/mood-checkins', $payload)
            ->assertOk()
            ->assertJsonPath('created', false);

        $this->assertSame(1, PsychologyMoodCheckin::query()->count());
        $saved = PsychologyMoodCheckin::query()->first();
        $this->assertSame($owner->id, (int) $saved->user_id);
        $this->assertSame(['calm', 'grateful'], $saved->emotion_keys);

        $this->getJson('/api/v1/my/activity/summary')->assertOk()->assertJsonPath('mood.count', 1);
    }

    public function test_another_customers_completion_key_is_rejected(): void
    {
        $owner = $this->member('key-owner@example.com');
        $intruder = $this->member('key-intruder@example.com');
        $key = (string) Str::uuid();

        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/my/journal-entries', ['completion_key' => $key, 'prompt_key' => 'my-thoughts', 'body' => 'Owner only'])
            ->assertCreated();

        Sanctum::actingAs($intruder);
        $this->postJson('/api/v1/my/journal-entries', ['completion_key' => $key, 'prompt_key' => 'my-thoughts', 'body' => 'Takeover'])
            ->assertForbidden();
        $this->postJson('/api/v1/my/mood-checkins', ['completion_key' => (string) Str::uuid(), 'mood' => 'good', 'emotions' => ['calm']])
            ->assertCreated();

        $this->assertSame(1, PsychologyJournalEntry::query()->count());
        $this->assertSame('Owner only', PsychologyJournalEntry::query()->value('body'));
        $this->assertSame($owner->id, (int) PsychologyJournalEntry::query()->value('user_id'));
        $this->assertSame(0, PsychologyMoodCheckin::query()->where('user_id', $owner->id)->count());
    }

    public function test_mood_checkin_enforces_the_website_rules(): void
    {
        Sanctum::actingAs($this->member('mood-rules@example.com'));
        $valid = ['completion_key' => (string) Str::uuid(), 'mood' => 'sad', 'emotions' => ['worried']];

        foreach ([
            ['mood' => 'happy'],
            ['emotions' => []],
            ['emotions' => ['calm', 'happy', 'excited', 'loved', 'grateful', 'quiet', 'tired']],
            ['emotions' => ['euphoric']],
            ['emotions' => ['calm', 'calm']],
            ['reflection' => str_repeat('a', 1001)],
            ['completion_key' => 'not-a-uuid'],
        ] as $override) {
            $this->postJson('/api/v1/my/mood-checkins', array_merge($valid, $override))->assertUnprocessable();
        }

        $this->assertSame(0, PsychologyMoodCheckin::query()->count());
        $this->postJson('/api/v1/my/mood-checkins', $valid)->assertCreated()->assertJsonPath('entry.reflection', null);
    }

    public function test_journal_saves_and_enforces_the_website_rules(): void
    {
        Sanctum::actingAs($this->member('journal-rules@example.com'));
        $valid = ['completion_key' => (string) Str::uuid(), 'prompt_key' => 'relationships', 'title' => 'Sunday call', 'body' => 'Talked with my sister.'];

        foreach ([
            ['prompt_key' => 'diary'],
            ['prompt_key' => null],
            ['body' => '   '],
            ['body' => str_repeat('b', 5001)],
            ['title' => str_repeat('t', 121)],
        ] as $override) {
            $this->postJson('/api/v1/my/journal-entries', array_merge($valid, $override))->assertUnprocessable();
        }
        $this->assertSame(0, PsychologyJournalEntry::query()->count());

        $this->postJson('/api/v1/my/journal-entries', $valid)
            ->assertCreated()
            ->assertJsonPath('entry.prompt_label', 'Relationships')
            ->assertJsonPath('entry.title', 'Sunday call')
            ->assertJsonPath('entry.excerpt', 'Talked with my sister.');
    }

    public function test_gratitude_saves_answered_cards_and_enforces_the_website_rules(): void
    {
        Sanctum::actingAs($this->member('gratitude-rules@example.com'));
        $key = (string) Str::uuid();

        foreach ([
            ['answers' => ['something-good-today' => '', 'someone-i-appreciate' => null]],
            ['answers' => ['something-else' => 'Unknown card']],
            ['answers' => ['something-good-today' => str_repeat('g', 1001)]],
            ['answers' => 'not-a-list'],
        ] as $override) {
            $this->postJson('/api/v1/my/gratitude-entries', array_merge(['completion_key' => $key], $override))->assertUnprocessable();
        }
        $this->assertSame(0, PsychologyGratitudeEntry::query()->count());

        $this->postJson('/api/v1/my/gratitude-entries', [
            'completion_key' => $key,
            'answers' => ['something-good-today' => 'Morning chai', 'someone-i-appreciate' => ''],
        ])
            ->assertCreated()
            ->assertJsonCount(1, 'entry.answers')
            ->assertJsonPath('entry.answers.0.label', 'Something good today')
            ->assertJsonPath('entry.answers.0.excerpt', 'Morning chai');

        $saved = PsychologyGratitudeEntry::query()->first();
        $this->assertSame('Morning chai', $saved->something_good_today);
        $this->assertNull($saved->someone_i_appreciate);
    }

    public function test_a_tool_switched_off_on_the_website_rejects_app_saves(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('status');
            $table->timestamps();
        });
        DB::table('tools')->insert(['slug' => 'daily-mood-check-in', 'status' => 'inactive']);
        Sanctum::actingAs($this->member('closed@example.com'));

        $this->getJson('/api/v1/my/psychology/options')->assertOk()->assertJsonPath('mood.open', false);
        $this->postJson('/api/v1/my/mood-checkins', ['completion_key' => (string) Str::uuid(), 'mood' => 'good', 'emotions' => ['calm']])
            ->assertForbidden();
        $this->assertSame(0, PsychologyMoodCheckin::query()->count());
    }

    private function storage(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id');
            $table->string('specialization')->nullable();
            $table->string('photo')->nullable();
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
            $table->string('sub_service_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('session_type')->nullable();
            $table->date('booking_date')->nullable();
            $table->string('time_slot')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_status')->nullable();
            $table->string('paid_status')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('remarks_for_professional')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('booking_timedates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->date('date');
            $table->string('time_slot');
            $table->string('status')->default('pending');
            $table->string('remarks')->nullable();
            $table->string('meeting_link')->nullable();
            $table->timestamps();
        });

        Schema::create('psychology_mood_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->string('mood_key');
            $table->json('emotion_keys');
            $table->text('reflection')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamps();
        });

        Schema::create('psychology_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->string('prompt_key', 40)->nullable();
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->timestamp('written_at');
            $table->timestamps();
        });

        Schema::create('psychology_gratitude_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('completion_key')->unique();
            $table->text('something_good_today')->nullable();
            $table->text('someone_i_appreciate')->nullable();
            $table->text('something_about_myself')->nullable();
            $table->timestamp('written_at');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function member(string $email): User
    {
        return User::query()->create(['name' => 'Member '.$email, 'email' => $email, 'password' => 'password-123']);
    }

    private function professional(string $name): Professional
    {
        $pro = Professional::query()->create(['name' => $name, 'email' => Str::uuid().'@example.com', 'password' => 'password-123']);
        DB::table('profiles')->insert(['professional_id' => $pro->id, 'specialization' => 'Career Guidance']);

        return $pro;
    }

    private function booking(User $user, Professional $pro, string $service, string $date, string $status = 'confirmed'): Booking
    {
        $booking = Booking::query()->create([
            'user_id' => $user->id,
            'professional_id' => $pro->id,
            'service_name' => $service,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'plan_type' => 'one_time',
            'session_type' => 'online',
            'booking_date' => $date,
            'time_slot' => '10:00 AM - 10:30 AM',
            'status' => $status,
            'payment_status' => 'paid',
            'paid_status' => 'paid',
            'amount' => 1000,
            'razorpay_payment_id' => 'pay_secret_123',
            'transaction_id' => 'txn_secret_123',
            'remarks_for_professional' => 'internal only',
        ]);
        $booking->forceFill(['meeting_link' => 'https://meet.example/secret'])->save();

        return $booking;
    }

    private function mood(User $user, string $mood, string $utc): void
    {
        $record = new PsychologyMoodCheckin([
            'completion_key' => (string) Str::uuid(),
            'mood_key' => $mood,
            'emotion_keys' => ['calm'],
            'checked_in_at' => Carbon::parse($utc, 'UTC'),
        ]);
        $record->user_id = $user->id;
        $record->save();
    }

    private function journal(User $user, string $utc, string $body): void
    {
        $record = new PsychologyJournalEntry([
            'completion_key' => (string) Str::uuid(),
            'prompt_key' => 'my-thoughts',
            'body' => $body,
            'written_at' => Carbon::parse($utc, 'UTC'),
        ]);
        $record->user_id = $user->id;
        $record->save();
    }

    private function gratitude(User $user, string $utc): void
    {
        $record = new PsychologyGratitudeEntry([
            'completion_key' => (string) Str::uuid(),
            'something_good_today' => 'Morning walk',
            'written_at' => Carbon::parse($utc, 'UTC'),
        ]);
        $record->user_id = $user->id;
        $record->save();
    }

    private function notification(User $user, string $type, string $title): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $type,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => $title, 'message' => $title.' message', 'type' => 'payment_successful']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
