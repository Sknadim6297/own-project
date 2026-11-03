<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NutritionImportBatch extends Model
{
    protected $fillable = [
        'source_key',
        'source_filename',
        'checksum_sha256',
        'worksheet_name',
        'actor',
        'total_rows',
        'valid_rows',
        'rejected_rows',
        'duplicate_candidates',
        'imported_drafts',
        'skipped_existing',
        'conflicts',
        'published_count',
        'status',
        'is_dry_run',
        'summary',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'is_dry_run' => 'boolean',
        'summary' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(NutritionImportRow::class);
    }

    public function foods(): HasMany
    {
        return $this->hasMany(NutritionFood::class);
    }
}
