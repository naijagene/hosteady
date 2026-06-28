<?php

namespace App\Modules\Sdk\Report\Data;

use App\Modules\Sdk\Report\Enums\ReportStatus;
use App\Modules\Sdk\Report\Enums\ReportType;
use App\Modules\Sdk\Report\Enums\ReportVisibility;

readonly class ReportDefinition implements \JsonSerializable
{
    /**
     * @param  list<ReportColumn>  $columns
     * @param  list<ReportFilter>  $filters
     * @param  list<ReportSort>  $sorts
     * @param  list<ReportGroup>  $groups
     * @param  list<ReportAggregate>  $aggregates
     * @param  list<ReportMetric>  $metrics
     * @param  list<ReportChart>  $charts
     * @param  list<array<string, mixed>>  $actions
     */
    public function __construct(
        public string $moduleKey,
        public string $reportKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?string $entityKey = null,
        public ?string $tableKey = null,
        public ?string $dashboardKey = null,
        public ?string $description = null,
        public string $type = ReportType::Entity->value,
        public string $status = ReportStatus::Registered->value,
        public string $visibility = ReportVisibility::Organization->value,
        public ?ReportLayout $layout = null,
        public array $columns = [],
        public array $filters = [],
        public array $sorts = [],
        public array $groups = [],
        public array $aggregates = [],
        public array $metrics = [],
        public array $charts = [],
        public array $actions = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $layout = null;
        if (is_array($data['layout'] ?? null)) {
            $layout = ReportLayout::fromArray($data['layout']);
        } elseif (is_array($data['layout_json'] ?? null)) {
            $layout = ReportLayout::fromArray($data['layout_json']);
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            reportKey: (string) ($data['report_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? $data['label'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : null,
            tableKey: isset($data['table_key']) ? (string) $data['table_key'] : null,
            dashboardKey: isset($data['dashboard_key']) ? (string) $data['dashboard_key'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            type: (string) ($data['type'] ?? ReportType::Entity->value),
            status: (string) ($data['status'] ?? ReportStatus::Registered->value),
            visibility: (string) ($data['visibility'] ?? ReportVisibility::Organization->value),
            layout: $layout,
            columns: self::mapItems($data['columns'] ?? $data['columns_json'] ?? [], ReportColumn::class),
            filters: self::mapItems($data['filters'] ?? $data['filters_json'] ?? [], ReportFilter::class),
            sorts: self::mapItems($data['sorts'] ?? $data['sorts_json'] ?? [], ReportSort::class),
            groups: self::mapItems($data['groups'] ?? $data['groups_json'] ?? [], ReportGroup::class),
            aggregates: self::mapItems($data['aggregates'] ?? $data['aggregates_json'] ?? [], ReportAggregate::class),
            metrics: self::mapItems($data['metrics'] ?? $data['metrics_json'] ?? [], ReportMetric::class),
            charts: self::mapItems($data['charts'] ?? $data['charts_json'] ?? [], ReportChart::class),
            actions: is_array($data['actions'] ?? $data['actions_json'] ?? null) ? ($data['actions'] ?? $data['actions_json']) : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @param  class-string  $class
     * @return list<mixed>
     */
    private static function mapItems(mixed $raw, string $class): array
    {
        $items = [];
        foreach (is_array($raw) ? $raw : [] as $item) {
            if (is_array($item)) {
                $items[] = $class::fromArray($item);
            }
        }

        return $items;
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'table_key' => $this->tableKey,
            'dashboard_key' => $this->dashboardKey,
            'report_key' => $this->reportKey,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'layout' => $this->layout?->toArray(),
            'columns' => array_map(fn (ReportColumn $c) => $c->toArray(), $this->columns),
            'filters' => array_map(fn (ReportFilter $f) => $f->toArray(), $this->filters),
            'sorts' => array_map(fn (ReportSort $s) => $s->toArray(), $this->sorts),
            'groups' => array_map(fn (ReportGroup $g) => $g->toArray(), $this->groups),
            'aggregates' => array_map(fn (ReportAggregate $a) => $a->toArray(), $this->aggregates),
            'metrics' => array_map(fn (ReportMetric $m) => $m->toArray(), $this->metrics),
            'charts' => array_map(fn (ReportChart $c) => $c->toArray(), $this->charts),
            'actions' => $this->actions,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
