<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\SystemRoleProvisioner;
use Illuminate\Database\Seeder;

class SystemRoleSeeder extends Seeder
{
    /**
     * Dev/demo only: provision system roles for the first organization.
     *
     * Delegates to SystemRoleProvisioner. Not part of platform bootstrap.
     */
    public function run(): void
    {
        $organization = Organization::query()->whereNull('deleted_at')->first();

        if ($organization === null) {
            $this->command?->warn('SystemRoleSeeder skipped: no organization found.');

            return;
        }

        $actorUserId = User::query()->whereNull('deleted_at')->value('id');

        if ($actorUserId === null) {
            $this->command?->warn('SystemRoleSeeder skipped: no user found.');

            return;
        }

        app(SystemRoleProvisioner::class)->provisionForOrganization($organization, (int) $actorUserId);

        $this->command?->info("System roles provisioned for organization [{$organization->public_id}].");
    }
}
