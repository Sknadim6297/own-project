<?php

/**
 * READY FOR APPROVED PUBLICATION ONLY.
 *
 * Prerequisites (must already be applied):
 * - 2026_11_11_100000_create_tarot_foundation_tables
 * - 2026_11_11_100001_register_tarot_reflection_tool
 * - TarotCardSeeder (exactly 78 unique cards)
 *
 * Do not run until publication is explicitly approved.
 * Rollback is scoped to the Tarot Reflection tool only, and only when it still
 * carries the published signature (route_name=tools.tarot.intro, implemented).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SLUG = 'tarot-reflection';

    private const ROUTE = 'tools.tarot.intro';

    private const EXPECTED_CARDS = 78;

    public function up(): void
    {
        $this->assertPublicationPrerequisites();

        $row = DB::table('tools')->where('slug', self::SLUG)->first();

        $description = $row->description;
        if (! is_string($description) || trim($description) === '') {
            $description = 'Draw your cards. Explore what they may help you reflect on.';
        }

        // Idempotent: safe to re-run when already published with the same signature.
        DB::table('tools')->where('id', $row->id)->update([
            'status' => 'active',
            'is_implemented' => true,
            'route_name' => self::ROUTE,
            'description' => $description,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        // Scoped rollback: only reverse Tarot when it still matches the publish signature.
        // If an admin later changed status/route/implementation, leave their values alone.
        DB::table('tools')
            ->where('slug', self::SLUG)
            ->where('route_name', self::ROUTE)
            ->where('is_implemented', true)
            ->update([
                'status' => 'coming_soon',
                'is_implemented' => false,
                'route_name' => null,
                'updated_at' => now(),
            ]);
    }

    private function assertPublicationPrerequisites(): void
    {
        if (! Schema::hasTable('tools')) {
            throw new \RuntimeException(
                'Cannot publish Tarot Reflection: the tools table is missing.'
            );
        }

        if (! Schema::hasTable('tarot_cards')) {
            throw new \RuntimeException(
                'Cannot publish Tarot Reflection: tarot_cards is missing. Run 2026_11_11_100000_create_tarot_foundation_tables first.'
            );
        }

        $row = DB::table('tools')->where('slug', self::SLUG)->first();
        if ($row === null) {
            throw new \RuntimeException(
                'Cannot publish Tarot Reflection: tools.slug=tarot-reflection is missing. Run 2026_11_11_100001_register_tarot_reflection_tool first.'
            );
        }

        $cardCount = (int) DB::table('tarot_cards')->count();
        $uniqueCount = (int) DB::table('tarot_cards')->distinct()->count('card_id');
        if ($cardCount !== self::EXPECTED_CARDS || $uniqueCount !== self::EXPECTED_CARDS) {
            throw new \RuntimeException(
                'Cannot publish Tarot Reflection: expected '.self::EXPECTED_CARDS
                ." unique tarot_cards, found count={$cardCount} unique={$uniqueCount}. Run TarotCardSeeder."
            );
        }

        if (! Route::has(self::ROUTE)) {
            throw new \RuntimeException(
                'Cannot publish Tarot Reflection: route '.self::ROUTE.' is not registered in the application.'
            );
        }
    }
};
