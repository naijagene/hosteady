<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Contracts\ReportRenderer;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportReference;
use App\Support\Tenant\TenantContext;

class DynamicReportRendererService implements ReportRenderer
{
    public function __construct(
        private readonly DynamicReportDataProviderService $dataProviderService,
        private readonly DynamicReportAggregateService $aggregateService,
        private readonly DynamicReportGroupingService $groupingService,
        private readonly DynamicReportChartService $chartService,
        private readonly DynamicReportAuditRecorder $auditRecorder,
    ) {
    }

    public function render(ReportDefinition $definition, array $context = []): array
    {
        $permissions = is_array($context['permissions'] ?? null) ? $context['permissions'] : $this->defaultPermissions();
        $runtimeContext = $this->buildRuntimeContext($context);
        $dataset = $this->dataProviderService->resolve($definition, $context);

        $aggregateResults = [];
        $aggregateWarnings = [];
        foreach ($definition->aggregates as $aggregate) {
            $result = $this->aggregateService->resolve($aggregate, $dataset->rows);
            $aggregateResults[$aggregate->key] = $result['value'];
            $aggregateWarnings = array_merge($aggregateWarnings, $result['warnings']);
        }

        $groupedRows = $this->groupingService->group($definition->groups, $dataset->rows);
        $chartData = $this->chartService->resolveAll($definition->charts, $dataset->rows);

        $payload = [
            'metadata' => array_merge($definition->metadata, [
                'module_key' => $definition->moduleKey,
                'report_key' => $definition->reportKey,
                'public_id' => $definition->publicId,
                'name' => $definition->name,
                'description' => $definition->description,
                'type' => $definition->type,
                'status' => $definition->status,
                'visibility' => $definition->visibility,
            ]),
            'layout' => $definition->layout?->toArray(),
            'columns' => array_map(fn ($c) => $c->toArray(), $definition->columns),
            'filters' => array_map(fn ($f) => $f->toArray(), $definition->filters),
            'sorts' => array_map(fn ($s) => $s->toArray(), $definition->sorts),
            'groups' => array_map(fn ($g) => $g->toArray(), $definition->groups),
            'aggregates' => $aggregateResults,
            'aggregate_warnings' => $aggregateWarnings,
            'metrics' => array_map(fn ($m) => $m->toArray(), $definition->metrics),
            'charts' => $chartData,
            'dataset' => $dataset->toArray(),
            'grouped_rows' => $groupedRows,
            'actions' => $definition->actions,
            'runtime_context' => $runtimeContext,
            'permissions' => $permissions,
            'entity_reference' => $this->entityReference($definition),
        ];

        $this->auditRecorder->recordRendered($definition);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuntimeContext(array $context): array
    {
        $runtime = [
            'mode' => $context['mode'] ?? 'default',
            'parameters' => is_array($context['parameters'] ?? null) ? $context['parameters'] : [],
        ];

        if (app()->bound(TenantContext::class)) {
            $tenant = app(TenantContext::class);
            $runtime['organization_public_id'] = $tenant->organizationPublicId;
            $runtime['workspace_public_id'] = $tenant->workspacePublicId;
            $runtime['user_public_id'] = $tenant->user->public_id ?? null;
        }

        return array_merge($runtime, is_array($context['runtime'] ?? null) ? $context['runtime'] : []);
    }

    /**
     * @return array<string, bool>
     */
    private function defaultPermissions(): array
    {
        return [
            'read' => true,
            'run' => true,
            'export' => true,
            'manage' => false,
        ];
    }

    private function entityReference(ReportDefinition $definition): ?array
    {
        if ($definition->entityKey === null) {
            return null;
        }

        return (new ReportReference(
            moduleKey: $definition->moduleKey,
            reportKey: $definition->reportKey,
            publicId: $definition->publicId,
            entityKey: $definition->entityKey,
            tableKey: $definition->tableKey,
            dashboardKey: $definition->dashboardKey,
            label: $definition->name,
            organizationId: $definition->organizationId,
            workspaceId: $definition->workspaceId,
        ))->toArray();
    }
}
