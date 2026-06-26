<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Models\WorkflowCanvasSnapshot;
use App\Models\WorkflowDefinition;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowTemplateProvider;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowPreviewPayload;

class WorkflowDesignerPreviewService
{
    public function __construct(
        private readonly WorkflowTemplateProvider $templateProvider,
        private readonly WorkflowCanvasNormalizationService $normalizer,
        private readonly WorkflowDesignerAuditRecorder $auditRecorder,
    ) {
    }

    public function preview(EnterpriseScope $scope, WorkflowDefinition $definition): WorkflowPreviewPayload
    {
        $draftVersion = $definition->versions()
            ->where('status', 'draft')
            ->orderByDesc('version_number')
            ->first();

        $version = $draftVersion ?? $definition->currentVersion;
        $definitionJson = $version?->definition_json ?? [];

        $latestSnapshot = WorkflowCanvasSnapshot::query()
            ->where('workflow_definition_id', $definition->id)
            ->orderByDesc('created_at')
            ->first();

        $canvas = $latestSnapshot !== null
            ? WorkflowCanvas::fromArray($latestSnapshot->canvas_json)
            : new WorkflowCanvas();

        $definitionNodes = is_array($definitionJson['nodes'] ?? null) ? $definitionJson['nodes'] : [];
        $normalized = $this->normalizer->normalize($canvas, $definitionNodes);

        $this->auditRecorder->recordPreviewGenerated($definition);

        return new WorkflowPreviewPayload(
            workflowDefinitionPublicId: $definition->public_id,
            definition: $definitionJson,
            canvas: $normalized['canvas']->toArray(),
            templates: $this->templateProvider->listTemplates($scope),
            issues: $normalized['warnings'],
        );
    }
}
