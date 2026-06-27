<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowDependency implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $type,
        public ?string $versionConstraint = null,
        public bool $required = true,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            type: (string) ($data['type'] ?? 'capability'),
            versionConstraint: isset($data['version']) ? (string) $data['version'] : ($data['version_constraint'] ?? null),
            required: (bool) ($data['required'] ?? true),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'version' => $this->versionConstraint,
            'required' => $this->required,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
