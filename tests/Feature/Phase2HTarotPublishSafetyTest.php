<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\ToolCategory;
use App\Services\ToolRegistryRegistrar;
use Database\Seeders\TarotCardSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class Phase2HTarotPublishSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tool_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_category_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module_key');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('access_type')->default('free');
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        Schema::create('tarot_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_id', 40)->unique();
            $table->string('card_name');
            $table->string('arcana', 20);
            $table->string('suit', 20)->nullable();
            $table->string('number', 20)->nullable();
            $table->text('upright_meaning');
            $table->text('reversed_meaning');
            $table->string('upright_keywords')->nullable();
            $table->string('reversed_keywords')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_register_migration_creates_unpublished_tarot_row(): void
    {
        $migration = require database_path('migrations/2026_11_11_100001_register_tarot_reflection_tool.php');
        $migration->up();
        $migration->up(); // idempotent

        $tarot = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $this->assertSame('coming_soon', $tarot->status);
        $this->assertFalse($tarot->is_implemented);
        $this->assertNull($tarot->route_name);
        $this->assertTrue(ToolCategory::query()->where('slug', 'astrology-tarot')->exists());
    }

    public function test_publish_migration_fails_when_registry_row_missing(): void
    {
        $this->seed(TarotCardSeeder::class);
        $this->assertSame(78, DB::table('tarot_cards')->count());

        $migration = require database_path('migrations/2026_11_11_120000_publish_tarot_reflection.php');

        try {
            $migration->up();
            $this->fail('Expected RuntimeException when tarot-reflection row is missing.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tarot-reflection is missing', $e->getMessage());
        }
    }

    public function test_publish_migration_fails_when_deck_incomplete(): void
    {
        app(ToolRegistryRegistrar::class)->register();
        DB::table('tarot_cards')->insert([
            'card_id' => 'only-one',
            'card_name' => 'Only One',
            'arcana' => 'major',
            'suit' => null,
            'number' => '0',
            'upright_meaning' => 'x',
            'reversed_meaning' => 'y',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_11_11_120000_publish_tarot_reflection.php');

        try {
            $migration->up();
            $this->fail('Expected RuntimeException when deck is incomplete.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('expected 78 unique tarot_cards', $e->getMessage());
        }

        $tarot = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $this->assertSame('coming_soon', $tarot->status);
        $this->assertFalse($tarot->is_implemented);
    }

    public function test_publish_and_scoped_rollback(): void
    {
        app(ToolRegistryRegistrar::class)->register();
        $this->seed(TarotCardSeeder::class);

        $migration = require database_path('migrations/2026_11_11_120000_publish_tarot_reflection.php');
        $migration->up();
        $migration->up(); // idempotent

        $tarot = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $this->assertSame('active', $tarot->status);
        $this->assertTrue($tarot->is_implemented);
        $this->assertSame('tools.tarot.intro', $tarot->route_name);
        $this->assertTrue($tarot->canLaunch());

        $migration->down();
        $tarot->refresh();
        $this->assertSame('coming_soon', $tarot->status);
        $this->assertFalse($tarot->is_implemented);
        $this->assertNull($tarot->route_name);
    }

    public function test_rollback_does_not_overwrite_admin_changed_route(): void
    {
        app(ToolRegistryRegistrar::class)->register();
        $this->seed(TarotCardSeeder::class);

        $migration = require database_path('migrations/2026_11_11_120000_publish_tarot_reflection.php');
        $migration->up();

        Tool::query()->where('slug', 'tarot-reflection')->update([
            'route_name' => 'tools.index',
            'status' => 'inactive',
            'is_implemented' => true,
        ]);

        $migration->down();

        $tarot = Tool::query()->where('slug', 'tarot-reflection')->firstOrFail();
        $this->assertSame('inactive', $tarot->status);
        $this->assertTrue($tarot->is_implemented);
        $this->assertSame('tools.index', $tarot->route_name);
    }
}
