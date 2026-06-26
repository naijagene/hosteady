<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowCanvasViewport implements \JsonSerializable
{
    public function __construct(
        public float $x = 0,
        public float $y = 0,
        public float $zoom = 1,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            x: (float) ($payload['x'] ?? 0),
            y: (float) ($payload['y'] ?? 0),
            zoom: (float) ($payload['zoom'] ?? 1),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'zoom' => $this->zoom,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
