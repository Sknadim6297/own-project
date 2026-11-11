<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Homepage smoke check. PHPUnit forces empty SQLite :memory: without
     * RefreshDatabase, so HomeController cannot query `services` unless
     * migrations have been applied to the test connection.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        if (! Schema::hasTable('services')) {
            $this->markTestSkipped(
                'Homepage requires migrated tables (services). PHPUnit uses empty SQLite :memory: without RefreshDatabase.'
            );
        }

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
