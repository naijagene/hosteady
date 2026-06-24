<?php

namespace Tests\Feature\Services\Application;

use App\Enums\OrganizationApplicationStatus;
use App\Exceptions\Application\ApplicationAlreadyInstalledException;
use App\Exceptions\Application\CoreApplicationProtectedException;
use App\Exceptions\Application\InvalidApplicationTransitionException;
use App\Exceptions\Application\OrganizationApplicationNotFoundException;
use App\Models\Application;
use App\Models\OrganizationApplication;
use App\Services\Application\ApplicationInstallationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ApplicationInstallationServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private ApplicationInstallationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ApplicationInstallationService::class);
    }

    public function test_installs_demo_application_and_records_installer_membership(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'install-demo-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $installation = $this->service->install($context, $demo->public_id);

        $this->assertSame(OrganizationApplicationStatus::Active, $installation->status);
        $this->assertSame('1.0.0', $installation->installed_version);
        $this->assertNotNull($installation->installed_at);
        $this->assertSame($result->membershipPublicId, $installation->installedByMembership->public_id);
        $this->assertSame($demo->public_id, $installation->application->public_id);
    }

    public function test_lists_installed_applications_excluding_uninstalled(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'list-installed-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $installation = $this->service->install($context, $demo->public_id);
        $this->service->uninstall($context, $installation->public_id);

        $installed = $this->service->listInstalled($context);

        $this->assertCount(2, $installed);
    }

    public function test_prevents_duplicate_install(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'duplicate-install-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $this->service->install($context, $demo->public_id);

        $this->expectException(ApplicationAlreadyInstalledException::class);

        $this->service->install($context, $demo->public_id);
    }

    public function test_disables_and_enables_non_core_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'toggle-demo-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $installation = $this->service->install($context, $demo->public_id);

        $disabled = $this->service->disable($context, $installation->public_id);
        $this->assertSame(OrganizationApplicationStatus::Disabled, $disabled->status);

        $enabled = $this->service->enable($context, $installation->public_id);
        $this->assertSame(OrganizationApplicationStatus::Active, $enabled->status);
    }

    public function test_uninstalls_non_core_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'uninstall-demo-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $installation = $this->service->install($context, $demo->public_id);

        $this->service->uninstall($context, $installation->public_id);

        $this->assertSoftDeleted('organization_applications', [
            'public_id' => $installation->public_id,
        ]);

        $record = OrganizationApplication::withTrashed()
            ->where('public_id', $installation->public_id)
            ->firstOrFail();

        $this->assertSame(OrganizationApplicationStatus::Uninstalled, $record->status);
    }

    public function test_blocks_disable_for_core_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'core-disable-org']);
        $context = $this->buildTenantContext($user, $result);
        $organization = $this->findProvisionedOrganization($result);
        $installation = OrganizationApplication::query()
            ->where('organization_id', $organization->id)
            ->whereHas('application', fn ($query) => $query->where('key', 'core'))
            ->firstOrFail();

        $this->expectException(CoreApplicationProtectedException::class);

        $this->service->disable($context, $installation->public_id);
    }

    public function test_blocks_uninstall_for_core_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'core-uninstall-org']);
        $context = $this->buildTenantContext($user, $result);
        $organization = $this->findProvisionedOrganization($result);
        $installation = OrganizationApplication::query()
            ->where('organization_id', $organization->id)
            ->whereHas('application', fn ($query) => $query->where('key', 'core'))
            ->firstOrFail();

        $this->expectException(CoreApplicationProtectedException::class);

        $this->service->uninstall($context, $installation->public_id);
    }

    public function test_rejects_invalid_enable_transition(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'invalid-enable-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $installation = $this->service->install($context, $demo->public_id);

        $this->expectException(InvalidApplicationTransitionException::class);

        $this->service->enable($context, $installation->public_id);
    }

    public function test_throws_when_installation_not_found(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'missing-installation-org']);
        $context = $this->buildTenantContext($user, $result);

        $this->expectException(OrganizationApplicationNotFoundException::class);

        $this->service->disable($context, '01999999-9999-7999-8999-999999999999');
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
