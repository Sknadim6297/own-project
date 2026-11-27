<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Consultation Category (product terminology).
 * Physical table remains `services` for booking/API compatibility.
 */
class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_description',
        'slug',
        'meta_title',
        'meta_description',
        'image',
        'icon_name',
        'icon_path',
        'status',
        'display_order',
        'show_on_homepage',
        'show_in_explore_tools',
        'discovery_needles',
        'tone',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'show_on_homepage' => 'boolean',
        'show_in_explore_tools' => 'boolean',
        'discovery_needles' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Service $service) {
            if (empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });

        static::updating(function (Service $service) {
            if ($service->isDirty('name') && empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });

        static::saved(fn () => self::clearCatalogCaches());
        static::deleted(fn () => self::clearCatalogCaches());
    }

    public static function clearCatalogCaches(): void
    {
        Cache::forget('yuktione.directory-stats');
        Cache::forget('yuktione.catalog.categories');
        Cache::forget('yuktione.catalog.homepage-categories');
        Cache::forget('yuktione.catalog.explore-tool-categories');
        Cache::forget('yuktione.catalog.category-tool-map');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(ServiceDetails::class, 'service_id');
    }

    /**
     * Child Services (product terminology) — physical sub_services rows.
     */
    public function subServices(): HasMany
    {
        return $this->hasMany(SubService::class)->orderBy('display_order')->orderBy('name');
    }

    public function catalogueServices(): HasMany
    {
        return $this->subServices();
    }

    public function professionalServices(): HasMany
    {
        return $this->hasMany(ProfessionalService::class, 'service_id');
    }

    public function categoryTools(): HasMany
    {
        return $this->hasMany(CategoryTool::class, 'service_id')->orderBy('display_order');
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'category_tool', 'service_id', 'tool_id')
            ->withPivot(['display_order', 'is_visible'])
            ->withTimestamps()
            ->orderByPivot('display_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function scopeHomepage($query)
    {
        return $query->active()->where('show_on_homepage', true)->ordered();
    }

    public function scopeExploreTools($query)
    {
        return $query->active()->where('show_in_explore_tools', true)->ordered();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
