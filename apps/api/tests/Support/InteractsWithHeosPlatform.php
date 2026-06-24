<?php

namespace Tests\Support;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use App\Services\Organization\Data\CreateOrganizationData;
use App\Services\Organization\Data\ProvisionedOrganizationResult;
use App\Services\Organization\OrganizationProvisioningService;
use Database\Seeders\PermissionCatalogSeeder;

trait InteractsWithHeosPlatform
{
    /**
     * @return list<string>
     */
    protected function expectedPermissionKeys(): array
    {
        return [
            'organization.read',
            'organization.update',
            'organization.archive',
            'members.read',
            'members.invite',
            'members.update',
            'members.remove',
            'roles.read',
            'roles.manage',
            'workspace.read',
            'workspace.create',
            'workspace.update',
            'workspace.archive',
            'applications.read',
            'applications.install',
            'applications.configure',
            'applications.uninstall',
        ];
    }

    protected function seedHeosPermissions(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
    }

    protected function assertPermissionCatalogComplete(): void
    {
        $this->assertSame(17, Permission::query()->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createActiveUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['status' => 'active'], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function provisionTestOrganization(User $creator, array $overrides = []): ProvisionedOrganizationResult
    {
        $this->assertPermissionCatalogComplete();

        return app(OrganizationProvisioningService::class)->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: $overrides['name'] ?? 'Test Organization',
            slug: $overrides['slug'] ?? 'test-organization',
            timezone: $overrides['timezone'] ?? 'UTC',
            locale: $overrides['locale'] ?? 'en',
            planTier: $overrides['planTier'] ?? 'free',
            organizationCode: $overrides['organizationCode'] ?? null,
        ));
    }

    protected function findProvisionedOrganization(ProvisionedOrganizationResult $result): Organization
    {
        return Organization::query()
            ->where('public_id', $result->organizationPublicId)
            ->firstOrFail();
    }
}
