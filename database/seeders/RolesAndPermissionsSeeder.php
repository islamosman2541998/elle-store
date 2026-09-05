<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */
        $roles = [
            'super-admin',
            'admin',
            'manager',
            'orders-manager',
            'content-manager',
            'marketing-manager',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */
        $permissions = [
            // Dashboard
            'view dashboard',

            // Catalog
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            'view brands',
            'create brands',
            'edit brands',
            'delete brands',

            'view products',
            'create products',
            'edit products',
            'delete products',

            // Customers
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',

            // Orders
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'change order status',

            // Payments
            'view payments',
            'confirm payments',
            'refund payments',

            // Shipping
            'view shipping',
            'create shipping',
            'edit shipping',
            'delete shipping',

            // Coupons & Offers
            'view coupons',
            'create coupons',
            'edit coupons',
            'delete coupons',

            // Content
            'view pages',
            'create pages',
            'edit pages',
            'delete pages',

            'view banners',
            'create banners',
            'edit banners',
            'delete banners',

            // Settings
            'view settings',
            'edit settings',

            // Ads & Tracking
            'view ads settings',
            'edit ads settings',
            'view tracking events',

            // Reports
            'view reports',
            'export reports',

            // Users & Roles
            'view users',
            'create users',
            'edit users',
            'delete users',

            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Assign permissions to roles
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::where('name', 'super-admin')->first();
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::where('name', 'admin')->first();
        $admin->syncPermissions([
            'view dashboard',

            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            'view brands',
            'create brands',
            'edit brands',
            'delete brands',

            'view products',
            'create products',
            'edit products',
            'delete products',

            'view customers',
            'create customers',
            'edit customers',
            'delete customers',

            'view orders',
            'create orders',
            'edit orders',
            'change order status',

            'view payments',
            'confirm payments',

            'view shipping',
            'create shipping',
            'edit shipping',

            'view coupons',
            'create coupons',
            'edit coupons',

            'view pages',
            'create pages',
            'edit pages',

            'view banners',
            'create banners',
            'edit banners',

            'view settings',
            'edit settings',

            'view ads settings',
            'edit ads settings',
            'view tracking events',

            'view reports',
            'export reports',
        ]);

        $manager = Role::where('name', 'manager')->first();
        $manager->syncPermissions([
            'view dashboard',
            'view categories',
            'view brands',
            'view products',
            'view customers',
            'view orders',
            'edit orders',
            'change order status',
            'view payments',
            'view shipping',
            'view coupons',
            'view reports',
        ]);

        $ordersManager = Role::where('name', 'orders-manager')->first();
        $ordersManager->syncPermissions([
            'view dashboard',
            'view customers',
            'view orders',
            'edit orders',
            'change order status',
            'view payments',
            'confirm payments',
            'view shipping',
        ]);

        $contentManager = Role::where('name', 'content-manager')->first();
        $contentManager->syncPermissions([
            'view dashboard',

            'view categories',
            'create categories',
            'edit categories',

            'view brands',
            'create brands',
            'edit brands',

            'view products',
            'create products',
            'edit products',

            'view pages',
            'create pages',
            'edit pages',

            'view banners',
            'create banners',
            'edit banners',
        ]);

        $marketingManager = Role::where('name', 'marketing-manager')->first();
        $marketingManager->syncPermissions([
            'view dashboard',

            'view coupons',
            'create coupons',
            'edit coupons',

            'view banners',
            'create banners',
            'edit banners',

            'view ads settings',
            'edit ads settings',
            'view tracking events',

            'view reports',
            'export reports',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Give first user super-admin role
        |--------------------------------------------------------------------------
        */

        $user = User::first();

        if ($user) {
            $user->assignRole('super-admin');
        }
    }
}