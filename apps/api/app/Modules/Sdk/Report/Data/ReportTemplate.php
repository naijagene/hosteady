<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportTemplate implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $templateKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $description = null,
        public ?ReportLayout $layout = null,
        public array $definition = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $layout = null;
        if (is_array($data['layout'] ?? null)) {
            $layout = ReportLayout::fromArray($data['layout']);
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            templateKey: (string) ($data['template_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            layout: $layout,
            definition: is_array($data['definition'] ?? null) ? $data['definition'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'template_key' => $this->templateKey,
            'name' => $this->name,
            'description' => $this->description,
            'layout' => $this->layout?->toArray(),
            'definition' => $this->definition,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
