<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowCanvasNode implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?string $label = null,
        public float $x = 0,
        public float $y = 0,
        public float $width = 120,
        public float $height = 60,
        public array $config = [],
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            type: (string) $payload['type'],
            label: isset($payload['label']) ? (string) $payload['label'] : null,
            x: (float) ($payload['x'] ?? 0),
            y: (float) ($payload['y'] ?? 0),
            width: (float) ($payload['width'] ?? 120),
            height: (float) ($payload['height'] ?? 60),
            config: is_array($payload['config'] ?? null) ? $payload['config'] : [],
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $this->label,
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'config' => $this->config,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
