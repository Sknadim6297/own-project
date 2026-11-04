<?php

namespace Tests\Feature;

use App\Models\McqAnswer;
use App\Models\Service;
use App\Models\ServiceMCQ;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingSuccessMcqSubmitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckActiveSession::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);
    }

    public function test_success_blade_uses_existing_user_mcq_submit_route_not_missing_alias(): void
    {
        $blade = file_get_contents(resource_path('views/customer/booking/success.blade.php'));

        $this->assertTrue(Route::has('user.mcq.submit'));
        $this->assertFalse(Route::has('user.booking.mcq.store'));
        $this->assertStringContainsString("route(\"user.mcq.submit\")", $blade);
        $this->assertStringNotContainsString('user.booking.mcq.store', $blade);
        $this->assertStringContainsString('answer: formData.get', $blade);
        $this->assertStringContainsString("answers[{{ \$index }}][selected_answer]", $blade);
    }

    public function test_user_mcq_submit_accepts_success_page_compatible_payload_for_authenticated_user(): void
    {
        $this->storage();

        $user = User::query()->create([
            'name' => 'MCQ Customer',
            'email' => 'mcq-customer@example.com',
            'password' => 'password-123',
        ]);

        $service = Service::query()->create(['name' => 'Career Guidance']);
        $question = ServiceMCQ::query()->create([
            'service_id' => $service->id,
            'question' => 'What is your primary goal?',
            'question_type' => 'mcq',
            'options' => ['A' => 'Growth', 'B' => 'Clarity'],
            'has_other_option' => false,
        ]);

        $this->actingAs($user, 'user')
            ->postJson(route('user.mcq.submit'), [
                'service_id' => $service->id,
                'booking_id' => 42,
                'answers' => [
                    [
                        'mcq_id' => $question->id,
                        'question' => $question->question,
                        'answer' => 'Growth',
                        'other_answer' => null,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Answers submitted successfully',
            ]);

        $this->assertDatabaseHas('user_mcq_answers', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'service_mcq_id' => $question->id,
            'question' => 'What is your primary goal?',
            'selected_answer' => 'Growth',
        ]);

        $this->assertSame(1, McqAnswer::query()->where('user_id', $user->id)->count());
    }

    public function test_user_mcq_submit_requires_authentication(): void
    {
        $this->storage();

        $this->postJson(route('user.mcq.submit'), [
            'service_id' => 1,
            'answers' => [
                [
                    'mcq_id' => 1,
                    'question' => 'Q',
                    'answer' => 'A',
                ],
            ],
        ])->assertUnauthorized();
    }

    public function test_user_mcq_submit_rejects_invalid_payload(): void
    {
        $this->storage();

        $user = User::query()->create([
            'name' => 'MCQ Invalid',
            'email' => 'mcq-invalid@example.com',
            'password' => 'password-123',
        ]);

        $this->actingAs($user, 'user')
            ->postJson(route('user.mcq.submit'), [
                'service_id' => 1,
                'answers' => [
                    [
                        'mcq_id' => 1,
                        'question' => 'Q',
                        // selected_answer is not accepted; controller requires answer
                        'selected_answer' => 'Growth',
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.0.answer']);

        $this->assertSame(0, McqAnswer::query()->where('user_id', $user->id)->count());
    }

    public function test_user_mcq_submit_returns_500_for_unexpected_server_failures(): void
    {
        $this->storage();

        $user = User::query()->create([
            'name' => 'MCQ Server Error',
            'email' => 'mcq-server-error@example.com',
            'password' => 'password-123',
        ]);

        $service = Service::query()->create(['name' => 'Broken Persist']);
        $question = ServiceMCQ::query()->create([
            'service_id' => $service->id,
            'question' => 'Forced failure question',
            'question_type' => 'mcq',
            'options' => ['A' => 'X'],
            'has_other_option' => false,
        ]);

        McqAnswer::creating(function () {
            throw new \RuntimeException('Forced MCQ persist failure');
        });

        $this->actingAs($user, 'user')
            ->postJson(route('user.mcq.submit'), [
                'service_id' => $service->id,
                'answers' => [
                    [
                        'mcq_id' => $question->id,
                        'question' => $question->question,
                        'answer' => 'X',
                        'other_answer' => null,
                    ],
                ],
            ])
            ->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Failed to submit answers',
            ]);
    }

    public function test_success_mcq_markup_and_submit_contract_remain_intact(): void
    {
        $blade = file_get_contents(resource_path('views/customer/booking/success.blade.php'));

        $this->assertStringContainsString('id="mcqForm"', $blade);
        $this->assertStringContainsString('id="submitMCQ"', $blade);
        $this->assertStringContainsString('name="service_id"', $blade);
        $this->assertStringContainsString('name="booking_id"', $blade);
        $this->assertStringContainsString("method: 'POST'", $blade);
        $this->assertStringContainsString('X-CSRF-TOKEN', $blade);
        $this->assertStringContainsString("data.status === 'success'", $blade);
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

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('service_m_c_q_s', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->text('question');
            $table->string('question_type')->default('mcq');
            $table->json('options')->nullable();
            $table->boolean('has_other_option')->default(false);
            $table->timestamps();
        });

        Schema::create('user_mcq_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('service_mcq_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->text('question');
            $table->text('selected_answer');
            $table->text('other_answer')->nullable();
            $table->timestamps();
        });
    }
}
