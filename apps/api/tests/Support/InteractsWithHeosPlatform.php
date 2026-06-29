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
            'workspace.applications.read',
            'workspace.applications.enable',
            'workspace.applications.configure',
            'workspace.applications.manage',
            'applications.read',
            'applications.install',
            'applications.configure',
            'applications.uninstall',
            'audit.read',
            'notifications.read',
            'notifications.send',
            'notifications.manage',
            'notifications.templates',
            'notifications.preferences',
            'notifications.broadcast',
            'notifications.schedule',
            'notifications.digest',
            'reference.read',
            'files.read',
            'files.upload',
            'files.manage',
            'jobs.read',
            'jobs.dispatch',
            'jobs.manage',
            'scheduler.read',
            'scheduler.manage',
            'search.read',
            'search.manage',
            'workflow.read',
            'workflow.manage',
            'workflow.publish',
            'workflow.execute',
            'workflow.runtime.read',
            'task.read',
            'task.manage',
            'approval.read',
            'approval.decide',
            'workflow.automation.read',
            'workflow.automation.manage',
            'workflow.designer.read',
            'workflow.designer.manage',
            'workflow.designer.import',
            'workflow.designer.export',
            'workflow.marketplace.read',
            'workflow.marketplace.install',
            'workflow.marketplace.publish',
            'workflow.marketplace.manage',
            'workflow.marketplace.export',
            'business.modules.read',
            'business.modules.install',
            'business.modules.manage',
            'business.modules.develop',
            'entities.read',
            'entities.manage',
            'entities.comment',
            'entities.tag',
            'forms.read',
            'forms.manage',
            'forms.submit',
            'forms.draft',
            'tables.read',
            'tables.manage',
            'tables.query',
            'tables.export',
            'dashboards.read',
            'dashboards.manage',
            'dashboards.render',
            'dashboards.export',
            'reports.read',
            'reports.manage',
            'reports.run',
            'reports.export',
            'reports.schedule',
            'data.records.read',
            'data.records.create',
            'data.records.update',
            'data.records.delete',
            'data.records.restore',
            'data.records.link',
            'data.records.manage',
            'documents.read',
            'documents.upload',
            'documents.update',
            'documents.delete',
            'documents.version',
            'documents.attach',
            'documents.manage',
            'rules.read',
            'rules.manage',
            'rules.evaluate',
            'rules.execute',
            'rules.admin',
            'integrations.read',
            'integrations.manage',
            'integrations.publish',
            'integrations.dispatch',
            'integrations.replay',
            'integrations.admin',
            'application.read',
            'application.manage',
            'navigation.read',
            'navigation.manage',
            'navigation.publish',
            'navigation.personalize',
            'ui.read',
            'ui.manage',
            'ui.render',
            'ui.personalize',
        ];
    }

    protected function seedHeosPermissions(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
    }

    protected function seedApplicationCatalog(): void
    {
        $this->seed(\Database\Seeders\ApplicationCatalogSeeder::class);
    }

    protected function seedHeosPlatform(): void
    {
        $this->seedHeosPermissions();
        $this->seedApplicationCatalog();
    }

    protected function assertPermissionCatalogComplete(): void
    {
        $this->assertSame(121, Permission::query()->count());
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

    protected function buildTenantContext(User $user, ProvisionedOrganizationResult $result): \App\Support\Tenant\TenantContext
    {
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return \App\Support\Tenant\TenantContext::fromModels($user, $organization, $membership, $workspace);
    }
}
