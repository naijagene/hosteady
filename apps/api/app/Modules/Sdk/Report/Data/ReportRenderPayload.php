<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportRenderPayload implements \JsonSerializable
{
    public function __construct(
        public array $metadata = [],
        public ?ReportLayout $layout = null,
        public ?ReportDataset $dataset = null,
        public array $columns = [],
        public array $filters = [],
        public array $sorts = [],
        public array $groups = [],
        public array $aggregates = [],
        public array $metrics = [],
        public array $charts = [],
        public array $actions = [],
        public ?ReportRenderContext $runtimeContext = null,
        public array $permissions = [],
        public ?array $entityReference = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            layout: is_array($data['layout'] ?? null) ? ReportLayout::fromArray($data['layout']) : null,
            dataset: is_array($data['dataset'] ?? null) ? ReportDataset::fromArray($data['dataset']) : null,
            columns: is_array($data['columns'] ?? null) ? $data['columns'] : [],
            filters: is_array($data['filters'] ?? null) ? $data['filters'] : [],
            sorts: is_array($data['sorts'] ?? null) ? $data['sorts'] : [],
            groups: is_array($data['groups'] ?? null) ? $data['groups'] : [],
            aggregates: is_array($data['aggregates'] ?? null) ? $data['aggregates'] : [],
            metrics: is_array($data['metrics'] ?? null) ? $data['metrics'] : [],
            charts: is_array($data['charts'] ?? null) ? $data['charts'] : [],
            actions: is_array($data['actions'] ?? null) ? $data['actions'] : [],
            runtimeContext: is_array($data['runtime_context'] ?? null) ? ReportRenderContext::fromArray($data['runtime_context']) : null,
            permissions: is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
            entityReference: is_array($data['entity_reference'] ?? null) ? $data['entity_reference'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata,
            'layout' => $this->layout?->toArray(),
            'dataset' => $this->dataset?->toArray(),
            'columns' => $this->columns,
            'filters' => $this->filters,
            'sorts' => $this->sorts,
            'groups' => $this->groups,
            'aggregates' => $this->aggregates,
            'metrics' => $this->metrics,
            'charts' => $this->charts,
            'actions' => $this->actions,
            'runtime_context' => $this->runtimeContext?->toArray(),
            'permissions' => $this->permissions,
            'entity_reference' => $this->entityReference,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
