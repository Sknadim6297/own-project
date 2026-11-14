<?php

namespace Tests\Unit;

use App\Helpers\ImageHelper;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImageHelperCustomerAvatarTest extends TestCase
{
    public function test_missing_profile_image_falls_back_to_default(): void
    {
        $this->app->instance('request', Request::create('http://127.0.0.1:8000/tools', 'GET'));

        $user = new User(['name' => 'Rahul Saha']);
        $profile = new CustomerProfile(['profile_image' => 'uploads/profiles/does-not-exist.jpg']);

        $url = ImageHelper::customerAvatarUrl($user, $profile);

        $this->assertStringContainsString('frontend/assets/img/avatar.jpg', $url);
        $this->assertStringNotContainsString('does-not-exist', $url);
    }

    public function test_public_url_encodes_spaces(): void
    {
        $this->app->instance('request', Request::create('http://127.0.0.1:8000/tools', 'GET'));

        $url = ImageHelper::publicUrl('uploads/profiles/my photo.png');

        $this->assertStringContainsString('my%20photo.png', $url);
        $this->assertStringStartsWith('/', $url);
    }
}
