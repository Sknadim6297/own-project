<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PfmCalculation extends Model
{
    protected $fillable = [
        'user_id',
        'tool_key',
        'title',
        'inputs',
        'outputs',
        'summary',
        'content_version',
        'completion_key',
        'is_draft',
    ];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
        'is_draft' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
