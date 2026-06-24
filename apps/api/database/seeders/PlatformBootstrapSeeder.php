<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlatformBootstrapSeeder extends Seeder
{
    /**
     * Seed global platform permissions only.
     *
     * Does not create organizations, workspaces, memberships, or organization roles.
     */
    public function run(): void
    {
        $this->call(PermissionCatalogSeeder::class);
    }
}
