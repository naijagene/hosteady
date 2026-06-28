<?php

namespace App\Modules\Sdk\Dashboard\Data;

use App\Modules\Sdk\Dashboard\Enums\DashboardStatus;
use App\Modules\Sdk\Dashboard\Enums\DashboardType;
use App\Modules\Sdk\Dashboard\Enums\DashboardVisibility;

readonly class DashboardDefinition implements \JsonSerializable
{
    /**
     * @param  list<DashboardWidget>  $widgets
     * @param  list<DashboardFilter>  $filters
     * @param  list<DashboardAction>  $actions
     */
    public function __construct(
        public string $moduleKey,
        public string $dashboardKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?string $entityKey = null,
        public ?string $description = null,
        public string $type = DashboardType::Entity->value,
        public string $status = DashboardStatus::Registered->value,
        public string $visibility = DashboardVisibility::Organization->value,
        public ?DashboardLayout $layout = null,
        public array $widgets = [],
        public array $filters = [],
        public array $actions = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $widgets = [];
        foreach (is_array($data['widgets'] ?? null) ? $data['widgets'] : [] as $widget) {
            if (is_array($widget)) {
                $widgets[] = DashboardWidget::fromArray($widget);
            }
        }

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

        $layout = null;
        if (is_array($data['layout'] ?? null)) {
            $layout = DashboardLayout::fromArray($data['layout']);
        } elseif (is_array($data['layout_json'] ?? null)) {
            $layout = DashboardLayout::fromArray($data['layout_json']);
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            dashboardKey: (string) ($data['dashboard_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? $data['label'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            type: (string) ($data['type'] ?? DashboardType::Entity->value),
            status: (string) ($data['status'] ?? DashboardStatus::Registered->value),
            visibility: (string) ($data['visibility'] ?? DashboardVisibility::Organization->value),
            layout: $layout,
            widgets: $widgets,
            filters: $filters,
            actions: $actions,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'dashboard_key' => $this->dashboardKey,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'layout' => $this->layout?->toArray(),
            'widgets' => array_map(fn (DashboardWidget $w) => $w->toArray(), $this->widgets),
            'filters' => array_map(fn (DashboardFilter $f) => $f->toArray(), $this->filters),
            'actions' => array_map(fn (DashboardAction $a) => $a->toArray(), $this->actions),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
