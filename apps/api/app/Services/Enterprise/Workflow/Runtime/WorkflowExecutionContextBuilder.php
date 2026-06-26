<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowVariable;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowVariableSnapshot;
use App\Support\Tenant\TenantContext;

class WorkflowExecutionContextBuilder
{
    public function fromTenant(TenantContext $context, ?string $moduleKey = null): WorkflowExecutionContext
    {
        return new WorkflowExecutionContext(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            userPublicId: $context->user->public_id,
            membershipPublicId: $context->membershipPublicId,
            moduleKey: $moduleKey,
            metadata: [
                'organization_name' => $context->organization->name,
                'workspace_name' => $context->workspace->name,
            ],
        );
    }

    public function fromRequest(WorkflowExecutionContext $context): WorkflowExecutionContext
    {
        return $context;
    }
}
