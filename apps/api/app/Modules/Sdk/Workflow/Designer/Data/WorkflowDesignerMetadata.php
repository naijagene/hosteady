<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowDesignerMetadata implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $layout
     */
    public function __construct(
        public array $layout = [],
        public ?string $designerVersion = null,
        public ?string $lastSavedAt = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            layout: is_array($payload['layout'] ?? null) ? $payload['layout'] : [],
            designerVersion: isset($payload['designer_version']) ? (string) $payload['designer_version'] : null,
            lastSavedAt: isset($payload['last_saved_at']) ? (string) $payload['last_saved_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'layout' => $this->layout,
            'designer_version' => $this->designerVersion,
            'last_saved_at' => $this->lastSavedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
