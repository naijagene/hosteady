<?php

namespace App\Services\Report;

use App\Models\ReportActivityLog;
use App\Models\ReportDefinition as ReportDefinitionModel;
use App\Models\ReportExport as ReportExportModel;
use App\Models\ReportRun as ReportRunModel;
use App\Models\ReportSchedule as ReportScheduleModel;
use App\Models\ReportTemplate as ReportTemplateModel;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportExportResult;
use App\Modules\Sdk\Report\Data\ReportRunResult;
use App\Modules\Sdk\Report\Data\ReportScheduleDefinition;
use App\Modules\Sdk\Report\Data\ReportTemplate;

class DynamicReportMapper
{
    public static function toDefinition(ReportDefinitionModel $model): ReportDefinition
    {
        return ReportDefinition::fromArray([
            'public_id' => $model->public_id,
            'organization_id' => $model->organization_id,
            'workspace_id' => $model->workspace_id,
            'module_key' => $model->module_key,
            'entity_key' => $model->entity_key,
            'table_key' => $model->table_key,
            'dashboard_key' => $model->dashboard_key,
            'report_key' => $model->report_key,
            'name' => $model->name,
            'description' => $model->description,
            'type' => $model->type,
            'status' => $model->status,
            'visibility' => $model->visibility,
            'layout_json' => $model->layout_json,
            'columns_json' => $model->columns_json,
            'filters_json' => $model->filters_json,
            'sorts_json' => $model->sorts_json,
            'groups_json' => $model->groups_json,
            'aggregates_json' => $model->aggregates_json,
            'metrics_json' => $model->metrics_json,
            'charts_json' => $model->charts_json,
            'actions_json' => $model->actions_json,
            'metadata' => $model->metadata,
        ]);
    }

    public static function applyDefinition(ReportDefinitionModel $model, ReportDefinition $definition): void
    {
        $model->fill([
            'module_key' => $definition->moduleKey,
            'report_key' => $definition->reportKey,
            'name' => $definition->name,
            'entity_key' => $definition->entityKey,
            'table_key' => $definition->tableKey,
            'dashboard_key' => $definition->dashboardKey,
            'description' => $definition->description,
            'type' => $definition->type,
            'status' => $definition->status,
            'visibility' => $definition->visibility,
            'layout_json' => $definition->layout?->toArray(),
            'columns_json' => array_map(fn ($c) => $c->toArray(), $definition->columns),
            'filters_json' => array_map(fn ($f) => $f->toArray(), $definition->filters),
            'sorts_json' => array_map(fn ($s) => $s->toArray(), $definition->sorts),
            'groups_json' => array_map(fn ($g) => $g->toArray(), $definition->groups),
            'aggregates_json' => array_map(fn ($a) => $a->toArray(), $definition->aggregates),
            'metrics_json' => array_map(fn ($m) => $m->toArray(), $definition->metrics),
            'charts_json' => array_map(fn ($c) => $c->toArray(), $definition->charts),
            'actions_json' => $definition->actions,
            'metadata' => $definition->metadata,
        ]);
    }

    /**
     * @return array{public_id: string}
     */
    public static function toReference(ReportDefinitionModel $model): array
    {
        return [
            'public_id' => $model->public_id,
        ];
    }

    public static function toTemplate(ReportTemplateModel $model): ReportTemplate
    {
        return ReportTemplate::fromArray([
            'public_id' => $model->public_id,
            'module_key' => $model->module_key,
            'template_key' => $model->template_key,
            'name' => $model->name,
            'description' => $model->description,
            'layout' => $model->layout_json,
            'definition' => $model->definition_json,
            'metadata' => $model->metadata,
        ]);
    }

    public static function toRunResult(ReportRunModel $model): ReportRunResult
    {
        return ReportRunResult::fromArray([
            'public_id' => $model->public_id,
            'status' => $model->status,
            'parameters' => $model->parameters_json,
            'result' => $model->result_json,
            'started_at' => $model->started_at?->toIso8601String(),
            'completed_at' => $model->completed_at?->toIso8601String(),
            'duration_ms' => $model->duration_ms,
            'metadata' => $model->metadata,
        ]);
    }

    public static function toExportResult(ReportExportModel $model): ReportExportResult
    {
        return ReportExportResult::fromArray([
            'public_id' => $model->public_id,
            'export_format' => $model->export_format,
            'status' => $model->status,
            'file_reference' => $model->file_reference,
            'metadata' => $model->metadata,
        ]);
    }

    public static function toSchedule(ReportScheduleModel $model, string $moduleKey, string $reportKey): ReportScheduleDefinition
    {
        return ReportScheduleDefinition::fromArray([
            'public_id' => $model->public_id,
            'organization_id' => $model->organization_id,
            'workspace_id' => $model->workspace_id,
            'report_definition_id' => $model->report_definition_id,
            'module_key' => $moduleKey,
            'report_key' => $reportKey,
            'name' => $model->name,
            'cron_expression' => $model->cron_expression,
            'run_at' => $model->run_at?->toIso8601String(),
            'timezone' => $model->timezone,
            'status' => $model->status,
            'export_formats_json' => $model->export_formats_json,
            'recipients_json' => $model->recipients_json,
            'parameters' => $model->parameters_json,
            'metadata' => $model->metadata,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function toActivityReference(ReportActivityLog $model): array
    {
        return [
            'public_id' => $model->public_id,
            'action' => $model->action,
            'before_state' => is_array($model->before_state) ? $model->before_state : [],
            'after_state' => is_array($model->after_state) ? $model->after_state : [],
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
