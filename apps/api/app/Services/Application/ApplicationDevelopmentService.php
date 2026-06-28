<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationRuntimeCache as ApplicationRuntimeCacheModel;
use App\Modules\Sdk\Application\Contracts\ApplicationRuntime;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Modules\Sdk\Application\Data\ApplicationHealthReport;
use App\Modules\Sdk\Application\Data\ApplicationRuntimeMetadata;
use App\Modules\Sdk\Application\Data\ApplicationStatistics;
use App\Modules\Sdk\Application\Data\ApplicationWorkspace;
use App\Modules\Sdk\Application\Data\NavigationMenu;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApplicationDevelopmentService
{
    public function __construct(
        private readonly ApplicationRuntime $runtimeService,
        private readonly ApplicationRuntimeRegistryService $registryService,
        private readonly WorkspaceManagerService $workspaceManager,
        private readonly MenuBuilderService $menuBuilder,
        private readonly ApplicationHealthService $healthService,
        private readonly ApplicationStatisticsService $statisticsService,
        private readonly ApplicationPermissionBridge $permissionBridge,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /** @return list<ApplicationDefinition> */
    public function listApplications(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->list($context->organization->id, $context->workspace?->id);
    }

    public function findApplication(TenantContext $context, string $publicId): ApplicationDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->findByPublicId($context->organization->id, $context->workspace?->id, $publicId);
    }

    public function register(TenantContext $context, ApplicationDefinition $definition): ApplicationDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->register($context->organization->id, $context->workspace?->id, $definition);
    }

    public function enable(TenantContext $context, string $publicId): ApplicationDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->enable($context->organization->id, $context->workspace?->id, $publicId);
    }

    public function disable(TenantContext $context, string $publicId): ApplicationDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->disable($context->organization->id, $context->workspace?->id, $publicId);
    }

    /** @return list<NavigationMenu> */
    public function navigation(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertNavigationRead($context);

        return $this->menuBuilder->menus($context);
    }

    /** @return list<ApplicationWorkspace> */
    public function workspaces(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertWorkspaceRead($context);

        return $this->workspaceManager->workspaces($context);
    }

    public function runtimeMetadata(TenantContext $context): ApplicationRuntimeMetadata
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->runtimeService->load($context);
    }

    public function health(TenantContext $context): ApplicationHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): ApplicationStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    public function cacheRuntime(TenantContext $context, array $payload, int $ttlSeconds = 300): void
    {
        $existing = ApplicationRuntimeCacheModel::query()
            ->where('organization_id', $context->organization->id)
            ->where('workspace_id', $context->workspace?->id)
            ->where('cache_key', 'runtime.metadata')
            ->first();

        if ($existing !== null) {
            $existing->fill([
                'payload_json' => $payload,
                'expires_at' => now()->addSeconds($ttlSeconds),
            ])->save();

            return;
        }

        ApplicationRuntimeCacheModel::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'cache_key' => 'runtime.metadata',
            'payload_json' => $payload,
            'expires_at' => now()->addSeconds($ttlSeconds),
            'metadata' => [],
            'created_at' => now(),
        ]);
    }

    private function requireCapability(TenantContext $context): void
    {
        if (! (bool) config('heos.enterprise.application_runtime.enabled', true)) {
            throw new HttpException(503, 'Application runtime is disabled.');
        }

        $this->runtimeBridge->requireCapability($context, 'application_runtime');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read applications.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionBridge->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage applications.');
        }
    }

    private function assertNavigationRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canReadNavigation($context)) {
            throw new HttpException(403, 'You do not have permission to read navigation.');
        }
    }

    private function assertWorkspaceRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canReadWorkspace($context)) {
            throw new HttpException(403, 'You do not have permission to read application workspaces.');
        }
    }
}
