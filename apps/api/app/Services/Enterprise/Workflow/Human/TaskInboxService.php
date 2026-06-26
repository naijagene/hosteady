<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Contracts\HumanTaskPort;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskReference;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class TaskInboxService
{
    public function __construct(
        private readonly HumanTaskPort $humanTaskPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /**
     * @return list<HumanTaskReference>
     */
    public function inbox(TenantContext $context, string $inboxType, int $limit = 50): array
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertReadPermission($context);

        return $this->humanTaskPort->inbox(
            $this->scope($context),
            $inboxType,
            $context->membership->id,
            $limit,
        );
    }

    private function scope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! app(\App\Services\Authorization\TenantAuthorizationService::class)->allows($context, 'task.read')) {
            abort(403, 'You are not allowed to read tasks.');
        }
    }
}
