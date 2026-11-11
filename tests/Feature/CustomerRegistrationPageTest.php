<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerRegistrationPageTest extends TestCase
{
    public function test_customer_registration_page_renders_the_two_step_registration_ui(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk()
            ->assertSee('Create your account')
            ->assertSee('Join YuktiOne to connect with trusted professionals.')
            ->assertSee('frontend/assets/Yuktione_logo.png')
            ->assertSee('family=DM+Sans', false)
            ->assertSee('name="first_name"', false)
            ->assertSee('name="last_name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="email"', false)
            ->assertSee('data-otp-digit', false)
            ->assertSee('id="backToInfoBtn"', false)
            ->assertSee('id="terms_accepted"', false)
            ->assertSee('data-save-lead-url="' . route('register.save-lead') . '"', false)
            ->assertSee('data-send-otp-url="' . route('register.send-otp') . '"', false)
            ->assertSee('data-verify-otp-url="' . route('register.verify-otp') . '"', false)
            ->assertSee('data-create-incomplete-url="' . route('register.create-incomplete') . '"', false)
            ->assertSee('data-complete-url="' . route('register.complete') . '"', false)
            ->assertSee('data-yuktione-toast-region', false);
    }
}
