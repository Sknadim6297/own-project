<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3F — store activation evidence so joined→activated can resume to rewarded
 * after the reward engine is enabled, without re-deriving unsafe client state.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referral_attributions')) {
            return;
        }

        Schema::table('referral_attributions', function (Blueprint $table) {
            if (! Schema::hasColumn('referral_attributions', 'activation_meta')) {
                $table->json('activation_meta')->nullable()->after('rewarded_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('referral_attributions')) {
            return;
        }

        Schema::table('referral_attributions', function (Blueprint $table) {
            if (Schema::hasColumn('referral_attributions', 'activation_meta')) {
                $table->dropColumn('activation_meta');
            }
        });
    }
};
