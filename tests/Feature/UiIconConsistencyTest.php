<?php

namespace Tests\Feature;

use App\Support\Icons\YuktioneIconRegistry;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Detects remaining application-controlled UI emoji outside documented allowlists.
 */
class UiIconConsistencyTest extends TestCase
{
    /** Relative paths intentionally allowed to contain emoji (legacy maps / docs). */
    private const ALLOWLIST_PATHS = [
        'app/Support/Icons/YuktioneIconRegistry.php',
        'scripts/emoji-audit.php',
        'tests/Feature/UiIconConsistencyTest.php',
        'docs/GLOBAL_ICON_OPTIMIZATION_REPORT.md',
    ];

    private const SCAN_DIRS = [
        'resources/views',
        'public/frontend/assets/js',
        'public/frontend/assets/css',
        'app/Support',
        'app/Services',
        'app/Helpers',
        'database/seeders',
        'resources/js',
    ];

    public function test_icon_registry_contains_required_lucide_names(): void
    {
        $required = [
            'moon-star', 'sparkles', 'brain', 'book-open', 'heart-handshake', 'wallet',
            'shield-check', 'target', 'droplets', 'activity', 'users', 'smile', 'meh',
            'frown', 'angry', 'laugh', 'save', 'user', 'lightbulb',
        ];

        foreach ($required as $name) {
            $this->assertTrue(YuktioneIconRegistry::has($name), "Missing icon: {$name}");
            $this->assertNotEmpty(YuktioneIconRegistry::paths($name));
        }
    }

    public function test_yk_icon_component_and_stylesheet_exist(): void
    {
        $this->assertFileExists(resource_path('views/components/yk-icon.blade.php'));
        $this->assertFileExists(public_path('frontend/assets/css/yuktione-icons.css'));
    }

    public function test_no_unapproved_ui_emojis_in_scanned_sources(): void
    {
        $emoji = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';
        $violations = [];

        foreach (self::SCAN_DIRS as $dir) {
            $path = base_path($dir);
            if (! is_dir($path)) {
                continue;
            }

            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (in_array($rel, self::ALLOWLIST_PATHS, true)) {
                    continue;
                }

                $name = $file->getFilename();
                if (! preg_match('/\.(php|js|css|json|blade\.php)$/i', $name) && ! str_ends_with($name, '.blade.php')) {
                    continue;
                }

                $contents = @file_get_contents($file->getPathname());
                if ($contents === false || $contents === '') {
                    continue;
                }

                $lines = preg_split("/\r\n|\n|\r/", $contents) ?: [];
                foreach ($lines as $i => $line) {
                    if (! preg_match_all($emoji, $line, $matches)) {
                        continue;
                    }
                    foreach ($matches[0] as $glyph) {
                        $violations[] = sprintf('%s:%d %s | %s', $rel, $i + 1, $glyph, mb_substr(trim($line), 0, 120));
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Unapproved UI emoji remaining:\n".implode("\n", array_slice($violations, 0, 40))
        );
    }

    public function test_from_emoji_maps_common_ui_glyphs(): void
    {
        $this->assertSame('moon-star', YuktioneIconRegistry::fromEmoji('🌙'));
        $this->assertSame('sparkles', YuktioneIconRegistry::fromEmoji('🔮'));
        $this->assertSame('heart', YuktioneIconRegistry::fromEmoji('❤️'));
        $this->assertSame('smile', YuktioneIconRegistry::fromEmoji('😊'));
    }
}
