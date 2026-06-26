<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowNodeTemplate implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $defaultConfig
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $publicId,
        public string $nodeType,
        public string $label,
        public float $defaultWidth = 120,
        public float $defaultHeight = 60,
        public ?string $category = null,
        public array $defaultConfig = [],
        public array $metadata = [],
        public bool $isSystem = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'node_type' => $this->nodeType,
            'label' => $this->label,
            'category' => $this->category,
            'default_width' => $this->defaultWidth,
            'default_height' => $this->defaultHeight,
            'default_config' => $this->defaultConfig,
            'metadata' => $this->metadata,
            'is_system' => $this->isSystem,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
