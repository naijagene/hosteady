<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationMappingDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $mappingKey,
        public ?string $moduleKey,
        public array $sourceSchema,
        public array $targetSchema,
        public array $mapping,
        public string $transformType,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['PublicId'] ?? ''),
            mappingKey: (string) ($data['mapping_key'] ?? $data['MappingKey'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['ModuleKey']) ? (string) $data['ModuleKey'] : null),
            sourceSchema: is_array($data['source_schema'] ?? $data['SourceSchema'] ?? null) ? ($data['source_schema'] ?? $data['SourceSchema']) : [],
            targetSchema: is_array($data['target_schema'] ?? $data['TargetSchema'] ?? null) ? ($data['target_schema'] ?? $data['TargetSchema']) : [],
            mapping: is_array($data['mapping'] ?? $data['Mapping'] ?? null) ? ($data['mapping'] ?? $data['Mapping']) : [],
            transformType: (string) ($data['transform_type'] ?? $data['TransformType'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'mapping_key' => $this->mappingKey,
            'module_key' => $this->moduleKey,
            'source_schema' => $this->sourceSchema,
            'target_schema' => $this->targetSchema,
            'mapping' => $this->mapping,
            'transform_type' => $this->transformType,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
