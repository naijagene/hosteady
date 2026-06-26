<?php

namespace App\Services\Enterprise\Jobs;

use App\Modules\Sdk\Enterprise\Contracts\PlatformJobPort;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformJobDispatchRequest;
use App\Modules\Sdk\Enterprise\Data\PlatformJobReference;
use App\Modules\Sdk\Enterprise\Data\PlatformJobResult;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class PlatformJobService
{
    public function __construct(
        private readonly PlatformJobPort $platformJobPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    public function dispatch(TenantContext $context, PlatformJobDispatchRequest $request): PlatformJobResult
    {
        $this->runtimeBridge->requireCapability($context, 'jobs');
        $this->assertDispatchPermission($context);

        return $this->platformJobPort->dispatch(new PlatformJobDispatchRequest(
            scope: $this->scopeFromContext($context, $request->scope->moduleKey),
            jobType: $request->jobType,
            displayName: $request->displayName,
            payload: $request->payload,
            entityReference: $request->entityReference,
            priority: $request->priority,
            queueName: $request->queueName,
            maxAttempts: $request->maxAttempts,
            correlationId: $request->correlationId,
            createdMembershipPublicId: $context->membershipPublicId,
            scheduledTaskPublicId: $request->scheduledTaskPublicId,
        ));
    }

    public function cancel(TenantContext $context, string $jobPublicId): PlatformJobReference
    {
        $this->runtimeBridge->requireCapability($context, 'jobs');
        $this->assertManagePermission($context);

        return $this->platformJobPort->cancel($this->scopeFromContext($context), $jobPublicId);
    }

    public function find(TenantContext $context, string $jobPublicId): ?PlatformJobReference
    {
        $this->runtimeBridge->requireCapability($context, 'jobs');
        $this->assertReadPermission($context);

        return $this->platformJobPort->find($this->scopeFromContext($context), $jobPublicId);
    }

    /**
     * @return list<PlatformJobReference>
     */
    public function list(TenantContext $context, ?string $moduleKey = null, int $limit = 50): array
    {
        $this->runtimeBridge->requireCapability($context, 'jobs');
        $this->assertReadPermission($context);

        return $this->platformJobPort->list($this->scopeFromContext($context, $moduleKey), $limit);
    }

    private function scopeFromContext(TenantContext $context, ?string $moduleKey = null): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            moduleKey: $moduleKey,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'jobs.read')) {
            abort(403, 'You are not allowed to read jobs.');
        }
    }

    private function assertDispatchPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'jobs.dispatch')) {
            abort(403, 'You are not allowed to dispatch jobs.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'jobs.manage') && ! $this->allows($context, 'jobs.dispatch')) {
            abort(403, 'You are not allowed to manage jobs.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
