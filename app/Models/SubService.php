<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Consultation Service under a Category (product terminology).
 * Physical table remains `sub_services` for booking/API compatibility.
 */
class SubService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'image',
        'status',
        'display_order',
    ];

    protected $casts = [
        'status' => 'integer',
        'display_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (SubService $subService) {
            if (empty($subService->slug) && filled($subService->name)) {
                $subService->slug = self::uniqueSlug(Str::slug($subService->name));
            }
        });

        static::updating(function (SubService $subService) {
            if ($subService->isDirty('name') && empty($subService->slug)) {
                $subService->slug = self::uniqueSlug(Str::slug($subService->name), $subService->id);
            }
        });

        static::saved(fn () => Service::clearCatalogCaches());
        static::deleted(fn () => Service::clearCatalogCaches());
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base !== '' ? $base : 'service';
        $candidate = $slug;
        $suffix = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function category(): BelongsTo
    {
        return $this->service();
    }

    public function getStatusTextAttribute(): string
    {
        return $this->status == 1 ? 'active' : 'inactive';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
