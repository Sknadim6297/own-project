<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Services\CategoryCatalog;
use App\Support\Tools\ToolsSpotlight;
use Database\Seeders\CanonicalCategorySeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Priority 3 — Solutions / Tools homepage scroller contracts with dynamic categories.
 */
class Priority03ScrollerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchema();
        Cache::flush();
    }

    public function test_solutions_rail_adapts_to_zero_one_and_many_homepage_categories(): void
    {
        $empty = view('frontend.home.partials.categories', [
            'homepageCategories' => collect(),
        ])->render();
        $this->assertStringContainsString('data-categories-carousel', $empty);
        $this->assertStringContainsString('Categories will appear here', $empty);
        $this->assertStringNotContainsString('data-categories-track', $empty);

        $one = Service::query()->create([
            'name' => 'Solo Category',
            'slug' => 'solo-category',
            'status' => 'active',
            'show_on_homepage' => true,
            'show_in_explore_tools' => false,
            'display_order' => 1,
            'short_description' => 'Only one',
        ]);

        $oneHtml = view('frontend.home.partials.categories', [
            'homepageCategories' => collect([$one]),
        ])->render();
        preg_match('/data-categories-track[^>]*>(.*?)<\/ul>/s', $oneHtml, $track);
        $this->assertSame(1, substr_count($track[1] ?? '', '<li>'));
        $this->assertStringContainsString('Solo Category', $oneHtml);

        Service::query()->where('slug', 'solo-category')->delete();
        (new CanonicalCategorySeeder)->run();
        Cache::flush();
        $nine = app(CategoryCatalog::class)->forHomepage();
        $this->assertSame(9, $nine->count());

        $nineHtml = view('frontend.home.partials.categories', [
            'homepageCategories' => $nine,
        ])->render();
        preg_match('/data-categories-track[^>]*>(.*?)<\/ul>/s', $nineHtml, $trackNine);
        $this->assertSame(9, substr_count($trackNine[1] ?? '', '<li>'));
        $this->assertStringContainsString('Mental Wellness', $nineHtml);
        $this->assertStringContainsString('Investments', $nineHtml);
        $this->assertStringContainsString(route('categories.show', 'mental-wellness'), $nineHtml);

        Service::query()->create([
            'name' => 'Extra Long Category Name For Overflow',
            'slug' => 'extra-long-category',
            'status' => 'active',
            'show_on_homepage' => true,
            'show_in_explore_tools' => true,
            'display_order' => 99,
            'short_description' => 'Tenth card',
        ]);
        Cache::flush();
        $ten = app(CategoryCatalog::class)->forHomepage();
        $this->assertGreaterThanOrEqual(10, $ten->count());
        $tenHtml = view('frontend.home.partials.categories', [
            'homepageCategories' => $ten,
        ])->render();
        preg_match('/data-categories-track[^>]*>(.*?)<\/ul>/s', $tenHtml, $trackTen);
        $this->assertSame($ten->count(), substr_count($trackTen[1] ?? '', '<li>'));
        $this->assertStringContainsString('Extra Long Category Name For Overflow', $tenHtml);
    }

    public function test_inactive_and_reordered_categories_are_respected(): void
    {
        (new CanonicalCategorySeeder)->run();
        Cache::flush();

        Service::query()->where('slug', 'mental-wellness')->update(['show_on_homepage' => false]);
        Service::query()->where('slug', 'investments')->update(['display_order' => 0]);
        Cache::flush();

        $cats = app(CategoryCatalog::class)->forHomepage();
        $this->assertFalse($cats->contains(fn (Service $c) => $c->slug === 'mental-wellness'));
        $this->assertSame('investments', $cats->first()->slug);

        $html = view('frontend.home.partials.categories', [
            'homepageCategories' => $cats,
        ])->render();
        $this->assertStringNotContainsString('>Mental Wellness<', $html);
        $this->assertStringContainsString(route('categories.show', 'investments'), $html);
    }

    public function test_tools_strip_and_shared_carousel_remain_wired(): void
    {
        $html = view('frontend.home.partials.tools')->render();
        $this->assertStringContainsString('data-tools-carousel', $html);
        $this->assertStringContainsString('data-tools-prev', $html);
        $this->assertStringContainsString('data-tools-next', $html);
        $this->assertStringContainsString('View All Tools', $html);

        $cards = ToolsSpotlight::cards(12);
        if ($cards !== []) {
            preg_match('/data-tools-track[^>]*>(.*?)<\/ul>/s', $html, $track);
            $this->assertSame(count($cards), substr_count($track[1] ?? '', 'class="yk-tool-tile"'));
        }

        $js = file_get_contents(public_path('frontend/assets/js/yuktione-home.js'));
        $this->assertIsString($js);
        $this->assertStringContainsString('ArrowRight', $js);
        $this->assertStringContainsString('ArrowLeft', $js);
        $this->assertStringContainsString("bindHomeCarousel('[data-categories-carousel]'", $js);
        $this->assertStringContainsString("bindHomeCarousel('[data-tools-carousel]'", $js);

        $catCss = file_get_contents(public_path('frontend/assets/css/yuktione-categories.css'));
        $toolCss = file_get_contents(public_path('frontend/assets/css/yuktione-tools.css'));
        $this->assertStringContainsString('.yk-home-cats:not(.is-carousel-scrollable) .yk-home-cats__nav', $catCss);
        $this->assertStringContainsString('.yk-tools-strip:not(.is-carousel-scrollable) .yk-tools-strip__nav', $toolCss);
        $this->assertStringNotContainsString("nth-child(n + 7) {\n        display: none;", $catCss);
    }

    private function buildSchema(): void
    {
        Schema::dropIfExists('category_tool');
        Schema::dropIfExists('tools');
        Schema::dropIfExists('tool_categories');
        Schema::dropIfExists('sub_services');
        Schema::dropIfExists('services');

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('short_description')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('image')->nullable();
            $table->string('icon_name')->nullable();
            $table->string('icon_path')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('show_on_homepage')->default(false);
            $table->boolean('show_in_explore_tools')->default(true);
            $table->json('discovery_needles')->nullable();
            $table->string('tone')->nullable();
            $table->timestamps();
        });

        Schema::create('tool_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
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
            $table->string('status')->default('coming_soon');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('is_implemented')->default(false);
            $table->timestamps();
        });

        Schema::create('category_tool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['service_id', 'tool_id']);
        });
    }
}
