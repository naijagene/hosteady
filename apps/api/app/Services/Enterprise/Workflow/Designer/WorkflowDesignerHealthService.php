<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Models\WorkflowCanvasSnapshot;
use App\Models\WorkflowNodeTemplate;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowTemplateProvider;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowExportFormat;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowImportFormat;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class WorkflowDesignerHealthService
{
    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly WorkflowTemplateProvider $templateProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.workflow_designer.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            ['workflow_canvas_snapshots', 'workflow_node_templates'],
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->fallbackAssessment($enabled),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $warnings = [];
        $status = 'healthy';

        $organizationId = $context?->organization->id;
        $workspaceId = $context?->workspace->id;

        $canvasQuery = WorkflowCanvasSnapshot::query();

        if ($organizationId !== null) {
            $canvasQuery->where('organization_id', $organizationId);
        }

        if ($workspaceId !== null) {
            $canvasQuery->where('workspace_id', $workspaceId);
        }

        $canvases = (clone $canvasQuery)->count();
        $snapshots = $canvases;
        $this->templateProvider->ensureSystemTemplates();
        $templates = WorkflowNodeTemplate::query()->count();

        if (! $enabled) {
            $warnings[] = 'Workflow designer is disabled in configuration.';
            $status = 'warning';
        }

        if ($templates === 0) {
            $warnings[] = 'No workflow node templates are available.';
            $status = 'warning';
        }

        return [
            'enabled' => $enabled,
            'canvases' => $canvases,
            'templates' => $templates,
            'snapshots' => $snapshots,
            'supported_import_formats' => WorkflowImportFormat::values(),
            'supported_export_formats' => WorkflowExportFormat::values(),
            'warnings' => $warnings,
            'status' => $status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackAssessment(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'canvases' => 0,
            'templates' => 0,
            'snapshots' => 0,
            'supported_import_formats' => WorkflowImportFormat::values(),
            'supported_export_formats' => WorkflowExportFormat::values(),
            'warnings' => [],
            'status' => 'healthy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $assessment = $this->assess($context);

        return [
            'enabled' => $assessment['enabled'],
            'canvases' => $assessment['canvases'],
            'templates' => $assessment['templates'],
            'supported_import_formats' => $assessment['supported_import_formats'],
            'supported_export_formats' => $assessment['supported_export_formats'],
        ];
    }

    /**
     * @return array{canvases: int, templates: int, snapshots: int}
     */
    public function statistics(
        EnterpriseScope $scope,
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): array {
        $canvasQuery = WorkflowCanvasSnapshot::query();

        if ($organizationId !== null) {
            $canvasQuery->where('organization_id', $organizationId);
        }

        if ($workspaceId !== null) {
            $canvasQuery->where('workspace_id', $workspaceId);
        }

        $canvases = (clone $canvasQuery)->count();

        return [
            'canvases' => $canvases,
            'templates' => WorkflowNodeTemplate::query()->count(),
            'snapshots' => $canvases,
        ];
    }
}
