<?php

namespace App\Services\Enterprise\Workflow;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Contracts\WorkflowPort;
use App\Modules\Sdk\Workflow\Data\WorkflowCategoryReference;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class WorkflowCategoryService
{
    public function __construct(
        private readonly WorkflowPort $workflowPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /**
     * @return list<WorkflowCategoryReference>
     */
    public function list(TenantContext $context): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertReadPermission($context);

        return $this->workflowPort->listCategories($this->scope($context));
    }

    public function create(
        TenantContext $context,
        string $categoryKey,
        string $name,
        ?string $description = null,
        ?string $moduleKey = null,
        ?array $metadata = null,
    ): WorkflowCategoryReference {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertManagePermission($context);

        return $this->workflowPort->createCategory(
            $this->scope($context, $moduleKey),
            $categoryKey,
            $name,
            $description,
            $moduleKey,
            $metadata,
            $context->user->id,
            $context->membership->id,
        );
    }

    private function scope(TenantContext $context, ?string $moduleKey = null): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            moduleKey: $moduleKey,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.read')) {
            abort(403, 'You are not allowed to read workflow categories.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.manage')) {
            abort(403, 'You are not allowed to manage workflow categories.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
