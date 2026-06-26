<?php

namespace App\Modules\Sdk\Workflow\Designer\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCloneResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerDiff;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerSnapshot;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowExportResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowImportResult;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowNodeTemplate;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowPreviewPayload;

interface WorkflowDesignerPort
{
    public function getCanvas(EnterpriseScope $scope, string $definitionPublicId): WorkflowDesignerSnapshot;

    public function saveCanvas(
        EnterpriseScope $scope,
        string $definitionPublicId,
        WorkflowCanvas $canvas,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDesignerSnapshot;

    /**
     * @return list<WorkflowDesignerSnapshot>
     */
    public function listSnapshots(EnterpriseScope $scope, string $definitionPublicId, int $limit = 50): array;

    public function getSnapshot(EnterpriseScope $scope, string $snapshotPublicId): WorkflowDesignerSnapshot;

    public function diffSnapshots(
        EnterpriseScope $scope,
        string $fromPublicId,
        string $toPublicId,
    ): WorkflowDesignerDiff;

    /**
     * @param  array<string, mixed>  $options
     */
    public function cloneWorkflow(
        EnterpriseScope $scope,
        string $definitionPublicId,
        array $options = [],
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowCloneResult;

    public function exportWorkflow(EnterpriseScope $scope, string $definitionPublicId): WorkflowExportResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function importWorkflow(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowImportResult;

    /**
     * @return list<WorkflowNodeTemplate>
     */
    public function listNodeTemplates(EnterpriseScope $scope): array;

    public function preview(EnterpriseScope $scope, string $definitionPublicId): WorkflowPreviewPayload;

    /**
     * @return array{canvases: int, templates: int, snapshots: int}
     */
    public function statistics(EnterpriseScope $scope, ?string $organizationId = null, ?string $workspaceId = null): array;
}
