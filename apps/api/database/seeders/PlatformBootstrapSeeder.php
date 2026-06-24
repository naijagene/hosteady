<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlatformBootstrapSeeder extends Seeder
{
    /**
     * Seed global platform catalog data.
     *
     * Does not create organizations, workspaces, memberships, or organization roles.
     */
    public function run(): void
    {
        $this->call([
            PermissionCatalogSeeder::class,
            ApplicationCatalogSeeder::class,
        ]);
    }
}
