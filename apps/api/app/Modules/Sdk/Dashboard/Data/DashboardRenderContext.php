<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardRenderContext implements \JsonSerializable
{
    public function __construct(
        public ?string $viewId = null,
        public string $mode = 'default',
        public array $filters = [],
        public array $runtime = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            viewId: isset($data['view_id']) ? (string) $data['view_id'] : null,
            mode: (string) ($data['mode'] ?? 'default'),
            filters: is_array($data['filters'] ?? null) ? $data['filters'] : [],
            runtime: is_array($data['runtime'] ?? null) ? $data['runtime'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'view_id' => $this->viewId,
            'mode' => $this->mode,
            'filters' => $this->filters,
            'runtime' => $this->runtime,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
