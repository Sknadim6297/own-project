<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychologyGratitudeEntry extends Model
{
    protected $fillable = [
        'completion_key',
        'something_good_today',
        'someone_i_appreciate',
        'something_about_myself',
        'written_at',
    ];

    protected $casts = [
        'written_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public function answers(): array
    {
        $answers = [];
        foreach ([
            'something-good-today' => $this->something_good_today,
            'someone-i-appreciate' => $this->someone_i_appreciate,
            'something-about-myself' => $this->something_about_myself,
        ] as $key => $value) {
            if (is_string($value) && $value !== '') {
                $answers[$key] = $value;
            }
        }

        return $answers;
    }
}
