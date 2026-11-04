<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\AdminMenu;
use App\Models\AdminMenuPermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
    class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@gmail.com');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');
        $adminName = env('ADMIN_NAME', 'Super Admin');

        // Create/update super admin from .env (plain password; Admin model hashed cast handles hashing)
        $superAdmin = Admin::withTrashed()->where('email', $adminEmail)->first();
        if (!$superAdmin) {
            Admin::create([
                'email' => $adminEmail,
                'name' => $adminName,
                'password' => $adminPassword,
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        } else {
            if ($superAdmin->trashed()) {
                $superAdmin->restore();
            }
            $superAdmin->update([
                'name' => $adminName,
                'password' => $adminPassword,
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        }
        
        // Create a regular admin with limited permissions
        $adminEmail = 'regular@example.com';
        $admin = Admin::withTrashed()->where('email', $adminEmail)->first();
        
        if (!$admin) {
            $admin = Admin::create([
                'email' => $adminEmail,
                'name' => 'Regular Admin',
                'password' => 'password123',
                'role' => 'admin',
                'is_active' => true
            ]);
        } elseif ($admin->trashed()) {
            // Restore the soft-deleted admin
            $admin->restore();
            $admin->update([
                'name' => 'Regular Admin',
                'password' => 'password123',
                'role' => 'admin',
                'is_active' => true
            ]);
        }
        
        // Assign some menu permissions to the regular admin
        // Get some menu IDs (adjust as needed)
        $menuIds = AdminMenu::whereIn('name', [
            'dashboard', 
            'booking',
            'contacts',
            'blog_comments'
        ])->pluck('id')->toArray();
        
        // Assign permissions (check for existing permissions first)
        foreach ($menuIds as $menuId) {
            AdminMenuPermission::firstOrCreate([
                'admin_id' => $admin->id,
                'admin_menu_id' => $menuId
            ]);
        }
    }
}
