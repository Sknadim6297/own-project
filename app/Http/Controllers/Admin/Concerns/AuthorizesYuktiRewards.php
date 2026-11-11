<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Admin;

trait AuthorizesYuktiRewards
{
    protected function authorizeYuktiRewards(string ...$menuNames): void
    {
        $admin = auth('admin')->user();
        abort_unless($admin instanceof Admin, 403);

        if ($admin->role === 'super_admin') {
            return;
        }

        $allowed = array_merge(['yukti_points'], $menuNames);
        $names = $admin->menus()->pluck('name');

        abort_unless($names->intersect($allowed)->isNotEmpty(), 403);
    }
}
