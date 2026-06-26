<?php

namespace App\Services\Enterprise\Scheduler;

use App\Modules\Sdk\Enterprise\Contracts\SchedulerPort;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskReference;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRequest;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRunReference;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class SchedulerService
{
    public function __construct(
        private readonly SchedulerPort $schedulerPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    public function create(TenantContext $context, ScheduledTaskRequest $request): ScheduledTaskReference
    {
        $this->runtimeBridge->requireCapability($context, 'scheduler');
        $this->assertManagePermission($context);

        return $this->schedulerPort->create(new ScheduledTaskRequest(
            scope: $this->scopeFromContext($context, $request->scope->moduleKey),
            taskType: $request->taskType,
            displayName: $request->displayName,
            description: $request->description,
            cronExpression: $request->cronExpression,
            runAt: $request->runAt,
            timezone: $request->timezone,
            payload: $request->payload,
            entityReference: $request->entityReference,
            enabled: $request->enabled,
            createdMembershipPublicId: $context->membershipPublicId,
        ));
    }

    public function pause(TenantContext $context, string $taskPublicId): ScheduledTaskReference
    {
        $this->runtimeBridge->requireCapability($context, 'scheduler');
        $this->assertManagePermission($context);

        return $this->schedulerPort->pause($this->scopeFromContext($context), $taskPublicId);
    }

    public function resume(TenantContext $context, string $taskPublicId): ScheduledTaskReference
    {
        $this->runtimeBridge->requireCapability($context, 'scheduler');
        $this->assertManagePermission($context);

        return $this->schedulerPort->resume($this->scopeFromContext($context), $taskPublicId);
    }

    public function cancel(TenantContext $context, string $taskPublicId): void
    {
        $this->runtimeBridge->requireCapability($context, 'scheduler');
        $this->assertManagePermission($context);

        $this->schedulerPort->cancel($this->scopeFromContext($context), $taskPublicId);
    }

    public function find(TenantContext $context, string $taskPublicId): ?ScheduledTaskReference
    {
        $this->runtimeBridge->requireCapability($context, 'scheduler');
        $this->assertReadPermission($context);

        return $this->schedulerPort->find($this->scopeFromContext($context), $taskPublicId);
    }

    /**
     * @return list<ScheduledTaskReference>
     */
    public function list(TenantContext $context, int $limit = 50): array
    {
        $this->runtimeBridge->requireCapability($context, 'scheduler');
        $this->assertReadPermission($context);

        return $this->schedulerPort->list($this->scopeFromContext($context), $limit);
    }

    /**
     * @return list<ScheduledTaskRunReference>
     */
    public function listRuns(TenantContext $context, string $taskPublicId, int $limit = 50): array
    {
        $this->runtimeBridge->requireCapability($context, 'scheduler');
        $this->assertReadPermission($context);

        return $this->schedulerPort->listRuns($this->scopeFromContext($context), $taskPublicId, $limit);
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
        if (! $this->allows($context, 'scheduler.read') && ! $this->allows($context, 'jobs.read')) {
            abort(403, 'You are not allowed to read scheduled tasks.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'scheduler.manage')) {
            abort(403, 'You are not allowed to manage scheduled tasks.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
