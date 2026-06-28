<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationTransformDefinition implements \JsonSerializable
{
    public function __construct(
        public string $transformType,
        public array $config,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            transformType: (string) ($data['transform_type'] ?? $data['TransformType'] ?? ''),
            config: is_array($data['config'] ?? $data['Config'] ?? null) ? ($data['config'] ?? $data['Config']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'transform_type' => $this->transformType,
            'config' => $this->config,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
