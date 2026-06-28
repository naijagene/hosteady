<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardView implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public ?string $publicId = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?string $dashboardDefinitionId = null,
        public ?DashboardLayout $layout = null,
        public array $filters = [],
        public bool $isDefault = false,
        public array $metadata = [],
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

        $layout = null;
        if (is_array($data['layout'] ?? null)) {
            $layout = DashboardLayout::fromArray($data['layout']);
        } elseif (is_array($data['layout_json'] ?? null)) {
            $layout = DashboardLayout::fromArray($data['layout_json']);
        }

        return new self(
            name: (string) ($data['name'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            dashboardDefinitionId: isset($data['dashboard_definition_id']) ? (string) $data['dashboard_definition_id'] : null,
            layout: $layout,
            filters: $filters,
            isDefault: (bool) ($data['is_default'] ?? false),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'dashboard_definition_id' => $this->dashboardDefinitionId,
            'name' => $this->name,
            'layout' => $this->layout?->toArray(),
            'filters' => array_map(fn (DashboardFilter $f) => $f->toArray(), $this->filters),
            'is_default' => $this->isDefault,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
