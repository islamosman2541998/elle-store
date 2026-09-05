<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ElleCategoriesSeeder::class,
            ElleAttributesSeeder::class,
            ElleProductsSeeder::class,
            ElleSettingsSeeder::class,
        ]);
    }
}
