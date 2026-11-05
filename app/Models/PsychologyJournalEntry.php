<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychologyJournalEntry extends Model
{
    protected $fillable = [
        'completion_key',
        'prompt_key',
        'title',
        'body',
        'written_at',
    ];

    protected $casts = [
        'written_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
