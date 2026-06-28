<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardLayoutItem implements \JsonSerializable
{
    public function __construct(
        public string $widgetKey,
        public int $x = 0,
        public int $y = 0,
        public int $width = 1,
        public int $height = 1,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            widgetKey: (string) ($data['widget_key'] ?? $data['key'] ?? ''),
            x: (int) ($data['x'] ?? 0),
            y: (int) ($data['y'] ?? 0),
            width: (int) ($data['width'] ?? 1),
            height: (int) ($data['height'] ?? 1),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'widget_key' => $this->widgetKey,
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
