<?php

namespace App\Modules\Sdk\Entity\Data;

readonly class EntityMutationResult implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public string $operation,
        public bool $success,
        public ?string $entityPublicId = null,
        public array $attributes = [],
        public array $warnings = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            operation: (string) ($data['operation'] ?? ''),
            success: (bool) ($data['success'] ?? false),
            entityPublicId: isset($data['entity_public_id']) ? (string) $data['entity_public_id'] : null,
            attributes: is_array($data['attributes'] ?? null) ? $data['attributes'] : [],
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'operation' => $this->operation,
            'success' => $this->success,
            'entity_public_id' => $this->entityPublicId,
            'attributes' => $this->attributes,
            'warnings' => $this->warnings,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
