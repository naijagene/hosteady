<?php

namespace App\Modules\Sdk\Dashboard\Data;

use App\Modules\Sdk\Dashboard\Enums\DashboardRefreshMode;

readonly class DashboardWidget implements \JsonSerializable
{
    public function __construct(
        public string $widgetKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $dashboardDefinitionId = null,
        public ?string $description = null,
        public string $widgetType = 'kpi_card',
        public ?string $chartType = null,
        public ?string $dataSourceType = null,
        public array $dataSourceConfig = [],
        public ?DashboardMetric $metric = null,
        public array $filters = [],
        public ?DashboardLayoutItem $layout = null,
        public array $actions = [],
        public string $refreshMode = DashboardRefreshMode::OnLoad->value,
        public array $metadata = [],
        public int $sortOrder = 0,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $filters = [];
        foreach (is_array($data['filters'] ?? null) ? $data['filters'] : [] as $filter) {
            if (is_array($filter)) {
                $filters[] = DashboardFilter::fromArray($filter);
            }
        }

        $actions = [];
        foreach (is_array($data['actions'] ?? null) ? $data['actions'] : [] as $action) {
            if (is_array($action)) {
                $actions[] = DashboardAction::fromArray($action);
            }
        }

        $metric = null;
        if (is_array($data['metric'] ?? null)) {
            $metric = DashboardMetric::fromArray($data['metric']);
        } elseif (is_array($data['metric_json'] ?? null)) {
            $metric = DashboardMetric::fromArray($data['metric_json']);
        }

        $layout = null;
        if (is_array($data['layout'] ?? null)) {
            $layout = DashboardLayoutItem::fromArray($data['layout']);
        } elseif (is_array($data['layout_json'] ?? null)) {
            $layout = DashboardLayoutItem::fromArray($data['layout_json']);
        }

        return new self(
            widgetKey: (string) ($data['widget_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? $data['label'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            dashboardDefinitionId: isset($data['dashboard_definition_id']) ? (string) $data['dashboard_definition_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            widgetType: (string) ($data['widget_type'] ?? $data['type'] ?? 'kpi_card'),
            chartType: isset($data['chart_type']) ? (string) $data['chart_type'] : null,
            dataSourceType: isset($data['data_source_type']) ? (string) $data['data_source_type'] : null,
            dataSourceConfig: is_array($data['data_source_config'] ?? null) ? $data['data_source_config'] : [],
            metric: $metric,
            filters: $filters,
            layout: $layout,
            actions: $actions,
            refreshMode: (string) ($data['refresh_mode'] ?? DashboardRefreshMode::OnLoad->value),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            sortOrder: (int) ($data['sort_order'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'dashboard_definition_id' => $this->dashboardDefinitionId,
            'widget_key' => $this->widgetKey,
            'name' => $this->name,
            'description' => $this->description,
            'widget_type' => $this->widgetType,
            'chart_type' => $this->chartType,
            'data_source_type' => $this->dataSourceType,
            'data_source_config' => $this->dataSourceConfig,
            'metric' => $this->metric?->toArray(),
            'filters' => array_map(fn (DashboardFilter $f) => $f->toArray(), $this->filters),
            'layout' => $this->layout?->toArray(),
            'actions' => array_map(fn (DashboardAction $a) => $a->toArray(), $this->actions),
            'refresh_mode' => $this->refreshMode,
            'metadata' => $this->metadata,
            'sort_order' => $this->sortOrder,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
