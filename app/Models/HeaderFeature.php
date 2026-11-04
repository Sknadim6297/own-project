<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeaderFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'header_id',
        'feature_heading',
        'order'
    ];

    /**
     * Get the header that owns the feature
     */
    public function header()
    {
        return $this->belongsTo(Header::class);
    }
}
