<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Header extends Model
{
    use HasFactory;

    protected $fillable = [
        'tagline',
        'status'
    ];

    /**
     * Get the features for the header
     */
    public function features()
    {
        return $this->hasMany(HeaderFeature::class)->orderBy('order');
    }
}
