<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowDesignerPort;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCloneResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerDiff;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerSnapshot;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowExportResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowImportResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowNodeTemplate;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowPreviewPayload;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class WorkflowDesignerService
{
    public function __construct(
        private readonly WorkflowDesignerPort $designerPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function getCanvas(TenantContext $context, string $definitionPublicId): WorkflowDesignerSnapshot
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertReadPermission($context);

        return $this->designerPort->getCanvas($this->scope($context), $definitionPublicId);
    }

    public function saveCanvas(TenantContext $context, string $definitionPublicId, WorkflowCanvas $canvas): WorkflowDesignerSnapshot
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertManagePermission($context);

        return $this->designerPort->saveCanvas(
            $this->scope($context),
            $definitionPublicId,
            $canvas,
            $context->user->id,
            $context->membership->id,
        );
    }

    /**
     * @return list<WorkflowDesignerSnapshot>
     */
    public function listSnapshots(TenantContext $context, string $definitionPublicId, int $limit = 50): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertReadPermission($context);

        return $this->designerPort->listSnapshots($this->scope($context), $definitionPublicId, $limit);
    }

    public function getSnapshot(TenantContext $context, string $snapshotPublicId): WorkflowDesignerSnapshot
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertReadPermission($context);

        return $this->designerPort->getSnapshot($this->scope($context), $snapshotPublicId);
    }

    public function diffSnapshots(TenantContext $context, string $fromPublicId, string $toPublicId): WorkflowDesignerDiff
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertReadPermission($context);

        return $this->designerPort->diffSnapshots($this->scope($context), $fromPublicId, $toPublicId);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function cloneWorkflow(TenantContext $context, string $definitionPublicId, array $options = []): WorkflowCloneResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertManagePermission($context);

        return $this->designerPort->cloneWorkflow(
            $this->scope($context),
            $definitionPublicId,
            $options,
            $context->user->id,
            $context->membership->id,
        );
    }

    public function exportWorkflow(TenantContext $context, string $definitionPublicId): WorkflowExportResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertExportPermission($context);

        return $this->designerPort->exportWorkflow($this->scope($context), $definitionPublicId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function importWorkflow(TenantContext $context, array $payload): WorkflowImportResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertImportPermission($context);

        return $this->designerPort->importWorkflow(
            $this->scope($context),
            $payload,
            $context->user->id,
            $context->membership->id,
        );
    }

    /**
     * @return list<WorkflowNodeTemplate>
     */
    public function listNodeTemplates(TenantContext $context): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertReadPermission($context);

        return $this->designerPort->listNodeTemplates($this->scope($context));
    }

    public function preview(TenantContext $context, string $definitionPublicId): WorkflowPreviewPayload
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_designer');
        $this->assertReadPermission($context);

        return $this->designerPort->preview($this->scope($context), $definitionPublicId);
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
        if (! $this->authorizationService->allows($context, 'workflow.designer.read')) {
            abort(403, 'You do not have permission to read workflow designer resources.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'workflow.designer.manage')) {
            abort(403, 'You do not have permission to manage workflow designer resources.');
        }
    }

    private function assertImportPermission(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'workflow.designer.import')) {
            abort(403, 'You do not have permission to import workflows.');
        }
    }

    private function assertExportPermission(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'workflow.designer.export')) {
            abort(403, 'You do not have permission to export workflows.');
        }
    }
}
