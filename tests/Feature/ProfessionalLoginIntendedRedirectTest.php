<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Support\Auth\IntendedUrl;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfessionalLoginIntendedRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('status')->default('accepted');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('professional_id')->nullable();
            $table->string('photo')->nullable();
        });

        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('profile_image')->nullable();
        });
    }

    public function test_accepted_professional_login_defaults_to_dashboard(): void
    {
        $this->professional('pro-default@example.test');

        $this->postJson(route('professional.store'), [
            'email' => 'pro-default@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', route('professional.dashboard'));
    }

    public function test_accepted_professional_login_returns_to_intended_panel_url(): void
    {
        $this->professional('pro-intended@example.test');

        $this->withSession([
            IntendedUrl::PROFESSIONAL_KEY => url('/professional/booking'),
        ])->postJson(route('professional.store'), [
            'email' => 'pro-intended@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', url('/professional/booking'));
    }

    public function test_professional_login_rejects_customer_intended_destination(): void
    {
        $this->professional('pro-cross@example.test');

        $this->withSession([
            'url.intended' => url('/user/dashboard'),
        ])->postJson(route('professional.store'), [
            'email' => 'pro-cross@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', route('professional.dashboard'));
    }

    public function test_pending_professional_still_lands_on_pending_page(): void
    {
        $this->professional('pro-pending@example.test', 'pending');

        $this->withSession([
            IntendedUrl::PROFESSIONAL_KEY => url('/professional/dashboard'),
        ])->postJson(route('professional.store'), [
            'email' => 'pro-pending@example.test',
            'password' => 'password-123',
            'terms_accepted' => '1',
        ])->assertOk()
            ->assertJsonPath('redirect_url', route('professional.pending.view'));
    }

    public function test_public_header_exposes_professional_dashboard_cta(): void
    {
        $pro = $this->professional('pro-header@example.test');

        $this->actingAs($pro, 'professional');

        $html = view('frontend.sections.header')->render();

        $this->assertStringContainsString('Go to Professional Dashboard', $html);
        $this->assertStringContainsString(route('professional.dashboard'), $html);
        $this->assertStringNotContainsString('Go to Customer Dashboard', $html);
    }

    private function professional(string $email, string $status = 'accepted'): Professional
    {
        return Professional::query()->create([
            'name' => 'Phase Pro',
            'email' => $email,
            'password' => Hash::make('password-123'),
            'status' => $status,
            'active' => true,
        ]);
    }
}
