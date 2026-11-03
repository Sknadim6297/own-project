<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nutrition_import_batches')) {
            Schema::create('nutrition_import_batches', function (Blueprint $table) {
                $table->id();
                $table->string('source_key', 40);
                $table->string('source_filename');
                $table->string('checksum_sha256', 64);
                $table->string('worksheet_name')->nullable();
                $table->string('actor', 120)->default('system');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('valid_rows')->default(0);
                $table->unsignedInteger('rejected_rows')->default(0);
                $table->unsignedInteger('duplicate_candidates')->default(0);
                $table->unsignedInteger('imported_drafts')->default(0);
                $table->unsignedInteger('skipped_existing')->default(0);
                $table->unsignedInteger('conflicts')->default(0);
                $table->unsignedInteger('published_count')->default(0);
                $table->string('status', 30)->default('pending');
                $table->boolean('is_dry_run')->default(false);
                $table->json('summary')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->index(['source_key', 'checksum_sha256']);
                $table->index(['source_key', 'status']);

            });
        }

        if (! Schema::hasTable('nutrition_import_rows')) {
            Schema::create('nutrition_import_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nutrition_import_batch_id')->constrained('nutrition_import_batches')->cascadeOnDelete();
                $table->unsignedInteger('worksheet_row');
                $table->string('import_identity', 64);
                $table->string('status', 30);
                $table->string('name')->nullable();
                $table->string('brand')->nullable();
                $table->string('variant')->nullable();
                $table->json('payload')->nullable();
                $table->json('warnings')->nullable();
                $table->text('message')->nullable();
                $table->foreignId('nutrition_food_id')->nullable()->constrained('nutrition_foods')->nullOnDelete();
                $table->foreignId('duplicate_of_food_id')->nullable()->constrained('nutrition_foods')->nullOnDelete();
                $table->timestamps();

                $table->unique(['nutrition_import_batch_id', 'worksheet_row'], 'nutrition_import_rows_batch_row_unique');
                $table->index('import_identity');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('nutrition_food_review_events')) {
            Schema::create('nutrition_food_review_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nutrition_food_id')->constrained('nutrition_foods')->cascadeOnDelete();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('action', 40);
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30)->nullable();
                $table->boolean('from_published')->nullable();
                $table->boolean('to_published')->nullable();
                $table->string('source_basis')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['nutrition_food_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('nutrition_duplicate_reviews')) {
            Schema::create('nutrition_duplicate_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nutrition_food_id')->constrained('nutrition_foods')->cascadeOnDelete();
                $table->foreignId('candidate_food_id')->constrained('nutrition_foods')->cascadeOnDelete();
                $table->string('match_reason', 120);
                $table->decimal('score', 5, 2)->default(0);
                $table->string('decision', 30)->default('pending');
                $table->unsignedBigInteger('decided_by_admin_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['nutrition_food_id', 'candidate_food_id'], 'nutrition_duplicate_pair_unique');
            });
        }

        Schema::table('nutrition_foods', function (Blueprint $table) {
            if (! Schema::hasColumn('nutrition_foods', 'sugar_g')) {
                $table->decimal('sugar_g', 10, 4)->nullable()->after('fibre_g');
            }
            if (! Schema::hasColumn('nutrition_foods', 'sodium_mg')) {
                $table->decimal('sodium_mg', 10, 4)->nullable()->after('sugar_g');
            }
            if (! Schema::hasColumn('nutrition_foods', 'saturated_fat_g')) {
                $table->decimal('saturated_fat_g', 10, 4)->nullable()->after('sodium_mg');
            }
            if (! Schema::hasColumn('nutrition_foods', 'import_identity')) {
                $table->string('import_identity', 64)->nullable()->after('import_row_ref');
                $table->unique('import_identity');
            }
            if (! Schema::hasColumn('nutrition_foods', 'nutrition_import_batch_id')) {
                $table->foreignId('nutrition_import_batch_id')->nullable()->after('import_identity')
                    ->constrained('nutrition_import_batches')->nullOnDelete();
            }
            if (! Schema::hasColumn('nutrition_foods', 'normalized_name')) {
                $table->string('normalized_name')->nullable()->after('name');
                $table->index('normalized_name');
            }
            if (! Schema::hasColumn('nutrition_foods', 'nutrient_completeness')) {
                $table->string('nutrient_completeness', 30)->default('incomplete')->after('verification_status');
            }
            if (! Schema::hasColumn('nutrition_foods', 'source_verified_at')) {
                $table->timestamp('source_verified_at')->nullable()->after('nutrient_completeness');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nutrition_foods', function (Blueprint $table) {
            foreach (['source_verified_at', 'nutrient_completeness', 'normalized_name', 'nutrition_import_batch_id', 'import_identity', 'saturated_fat_g', 'sodium_mg', 'sugar_g'] as $column) {
                if (Schema::hasColumn('nutrition_foods', $column)) {
                    if ($column === 'nutrition_import_batch_id') {
                        $table->dropConstrainedForeignId('nutrition_import_batch_id');
                    } elseif ($column === 'import_identity') {
                        $table->dropUnique(['import_identity']);
                        $table->dropColumn('import_identity');
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        Schema::dropIfExists('nutrition_duplicate_reviews');
        Schema::dropIfExists('nutrition_food_review_events');
        Schema::dropIfExists('nutrition_import_rows');
        Schema::dropIfExists('nutrition_import_batches');
    }
};
