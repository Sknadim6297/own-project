<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Admin;
use App\Models\Professional;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Refuse an unsafe production debug posture (including stale config:cache).
        if ($this->app->environment('production') && config('app.debug') === true) {
            throw new \RuntimeException(
                'Unsafe configuration: APP_DEBUG must be false when APP_ENV is production. Clear config cache after correcting .env (php artisan config:clear).'
            );
        }

        // Configure morph map for polymorphic relationships
        Relation::morphMap([
            'admin' => Admin::class,
            'professional' => Professional::class,
            'user' => User::class,
            'customer' => User::class, // Add customer as alias for User
        ]);
    }
}
