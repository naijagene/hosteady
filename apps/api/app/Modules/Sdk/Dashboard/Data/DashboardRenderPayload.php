<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardRenderPayload implements \JsonSerializable
{
    /**
     * @param  list<DashboardWidgetData>  $widgetData
     */
    public function __construct(
        public array $metadata = [],
        public ?DashboardLayout $layout = null,
        public array $widgets = [],
        public array $widgetData = [],
        public array $filters = [],
        public array $actions = [],
        public array $runtimeContext = [],
        public array $permissions = [],
        public ?DashboardReference $entityReference = null,
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

        $widgetData = [];
        foreach (is_array($data['widget_data'] ?? null) ? $data['widget_data'] : [] as $item) {
            if (is_array($item)) {
                $widgetData[] = DashboardWidgetData::fromArray($item);
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
        }

        $entityReference = null;
        if (is_array($data['entity_reference'] ?? null)) {
            $entityReference = DashboardReference::fromArray($data['entity_reference']);
        }

        return new self(
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            layout: $layout,
            widgets: $widgets,
            widgetData: $widgetData,
            filters: $filters,
            actions: $actions,
            runtimeContext: is_array($data['runtime_context'] ?? null) ? $data['runtime_context'] : [],
            permissions: is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
            entityReference: $entityReference,
        );
    }

    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata,
            'layout' => $this->layout?->toArray(),
            'widgets' => array_map(fn (DashboardWidget $w) => $w->toArray(), $this->widgets),
            'widget_data' => array_map(fn (DashboardWidgetData $d) => $d->toArray(), $this->widgetData),
            'filters' => array_map(fn (DashboardFilter $f) => $f->toArray(), $this->filters),
            'actions' => array_map(fn (DashboardAction $a) => $a->toArray(), $this->actions),
            'runtime_context' => $this->runtimeContext,
            'permissions' => $this->permissions,
            'entity_reference' => $this->entityReference?->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
