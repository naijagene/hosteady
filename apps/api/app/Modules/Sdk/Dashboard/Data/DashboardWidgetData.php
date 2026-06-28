<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardWidgetData implements \JsonSerializable
{
    public function __construct(
        public string $widgetKey,
        public mixed $value = null,
        public ?DashboardChart $chart = null,
        public array $rows = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $chart = null;
        if (is_array($data['chart'] ?? null)) {
            $chart = DashboardChart::fromArray($data['chart']);
        }

        return new self(
            widgetKey: (string) ($data['widget_key'] ?? $data['key'] ?? ''),
            value: $data['value'] ?? null,
            chart: $chart,
            rows: is_array($data['rows'] ?? null) ? $data['rows'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'widget_key' => $this->widgetKey,
            'value' => $this->value,
            'chart' => $this->chart?->toArray(),
            'rows' => $this->rows,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
