<?php

namespace App\Support\Tools;

use Illuminate\Support\Facades\Route;

/**
 * Registry-aware helpers for public Tools navigation.
 */
class ToolsSection
{
    /**
     * True when the current named route belongs to the Tools area (catalogue or any tool).
     */
    public static function isActive(): bool
    {
        $route = Route::current();
        if ($route === null) {
            return false;
        }

        $name = (string) $route->getName();

        return $name === 'tools.index' || str_starts_with($name, 'tools.');
    }
}
