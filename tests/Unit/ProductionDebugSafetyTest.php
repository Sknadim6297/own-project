<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductionDebugSafetyTest extends TestCase
{
    public function test_app_config_defaults_debug_to_false(): void
    {
        $configFile = file_get_contents(base_path('config/app.php'));

        $this->assertStringContainsString("env('APP_DEBUG', false)", $configFile);
    }

    public function test_appserviceprovider_guards_production_debug(): void
    {
        $provider = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString("environment('production')", $provider);
        $this->assertStringContainsString("config('app.debug') === true", $provider);
        $this->assertStringContainsString('Unsafe configuration: APP_DEBUG must be false', $provider);
    }

    public function test_env_example_documents_production_debug_requirement(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('Production MUST set APP_ENV=production and APP_DEBUG=false', $example);
        $this->assertStringContainsString('config:clear', $example);
    }
}
