<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YuktiWallet extends Model
{
    protected $table = 'yukti_wallets';

    protected $fillable = [
        'user_id',
        'available_points',
        'pending_points',
    ];

    protected $casts = [
        'available_points' => 'integer',
        'pending_points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
