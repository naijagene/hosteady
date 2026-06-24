<?php

namespace Tests\Feature\Services\WorkspaceApplication;

use App\Enums\WorkspaceApplicationStatus;
use App\Exceptions\WorkspaceApplication\CoreWorkspaceApplicationProtectedException;
use App\Exceptions\WorkspaceApplication\DuplicateWorkspaceApplicationException;
use App\Exceptions\WorkspaceApplication\OrganizationEntitlementRequiredException;
use App\Models\Application;
use App\Models\OrganizationApplication;
use App\Models\WorkspaceApplication;
use App\Services\Application\ApplicationInstallationService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceApplicationServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private WorkspaceApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WorkspaceApplicationService::class);
    }

    public function test_enables_application_in_workspace(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-enable-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        app()->instance(TenantContext::class, $context);

        $workspaceApplication = $this->service->enable($context, $orgInstall->public_id);

        $this->assertSame(WorkspaceApplicationStatus::Active, $workspaceApplication->status);
        $this->assertSame('1.0.0', $workspaceApplication->enabled_version);
        $this->assertFalse($workspaceApplication->is_bootstrap);
    }

    public function test_enabled_version_is_immutable_on_re_enable(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-version-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        app()->instance(TenantContext::class, $context);

        $workspaceApplication = $this->service->enable($context, $orgInstall->public_id);
        $originalVersion = $workspaceApplication->enabled_version;

        $orgInstall->update(['installed_version' => '9.9.9']);

        $this->service->disable($context, $workspaceApplication->public_id);
        $reEnabled = $this->service->reEnable($context, $workspaceApplication->public_id);

        $this->assertSame($originalVersion, $reEnabled->enabled_version);
        $this->assertSame('9.9.9', $orgInstall->fresh()->installed_version);
    }

    public function test_rejects_enable_when_organization_entitlement_is_disabled(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-disabled-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        app(ApplicationInstallationService::class)->disable($context, $orgInstall->public_id);

        $this->expectException(OrganizationEntitlementRequiredException::class);

        $this->service->enable($context, $orgInstall->public_id);
    }

    public function test_blocks_disable_archive_and_remove_for_core_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-core-org']);
        $context = $this->buildTenantContext($user, $result);

        $coreWorkspaceApplication = WorkspaceApplication::query()
            ->where('workspace_id', $context->workspace->id)
            ->whereHas('application', fn ($query) => $query->where('key', 'core'))
            ->firstOrFail();

        $this->expectException(CoreWorkspaceApplicationProtectedException::class);
        $this->service->disable($context, $coreWorkspaceApplication->public_id);
    }

    public function test_archives_and_re_enables_non_core_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-archive-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        $workspaceApplication = $this->service->enable($context, $orgInstall->public_id);

        $archived = $this->service->archive($context, $workspaceApplication->public_id);
        $this->assertSame(WorkspaceApplicationStatus::Archived, $archived->status);

        $reEnabled = $this->service->reEnable($context, $workspaceApplication->public_id);
        $this->assertSame(WorkspaceApplicationStatus::Active, $reEnabled->status);
    }

    public function test_removes_non_core_workspace_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-remove-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        $workspaceApplication = $this->service->enable($context, $orgInstall->public_id);

        $this->service->remove($context, $workspaceApplication->public_id);

        $this->assertSoftDeleted('workspace_applications', [
            'public_id' => $workspaceApplication->public_id,
        ]);
    }

    public function test_rejects_duplicate_enable(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-duplicate-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        $this->service->enable($context, $orgInstall->public_id);

        $this->expectException(DuplicateWorkspaceApplicationException::class);

        $this->service->enable($context, $orgInstall->public_id);
    }

    public function test_list_available_includes_already_enabled_false(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-available-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        $available = $this->service->listAvailable($context);

        $this->assertCount(1, $available);
        $this->assertFalse($available->first()['already_enabled']);
        $this->assertSame($orgInstall->public_id, $available->first()['organization_application_public_id']);

        $this->service->enable($context, $orgInstall->public_id);

        $this->assertCount(0, $this->service->listAvailable($context));
    }

    public function test_count_active_applications_requires_active_org_and_catalog_states(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-count-org']);
        $context = $this->buildTenantContext($user, $result);

        $this->assertSame(2, $this->service->countActiveApplications($context));

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        $this->service->enable($context, $orgInstall->public_id);

        $this->assertSame(3, $this->service->countActiveApplications($context));
    }

    public function test_cascades_organization_uninstall_to_workspace_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-cascade-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        $workspaceApplication = $this->service->enable($context, $orgInstall->public_id);

        app(ApplicationInstallationService::class)->uninstall($context, $orgInstall->public_id);

        $this->assertSoftDeleted('workspace_applications', [
            'public_id' => $workspaceApplication->public_id,
        ]);
    }

    private function buildTenantContext(
        \App\Models\User $user,
        \App\Services\Organization\Data\ProvisionedOrganizationResult $result,
    ): TenantContext {
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }
}
