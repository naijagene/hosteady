<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationConnectorDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $connectorKey,
        public string $name,
        public ?string $description,
        public string $connectorType,
        public string $authType,
        public string $status,
        public ?string $moduleKey,
        public array $config,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['PublicId'] ?? ''),
            connectorKey: (string) ($data['connector_key'] ?? $data['ConnectorKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['Name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['Description']) ? (string) $data['Description'] : null),
            connectorType: (string) ($data['connector_type'] ?? $data['ConnectorType'] ?? ''),
            authType: (string) ($data['auth_type'] ?? $data['AuthType'] ?? ''),
            status: (string) ($data['status'] ?? $data['Status'] ?? ''),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['ModuleKey']) ? (string) $data['ModuleKey'] : null),
            config: is_array($data['config'] ?? $data['Config'] ?? null) ? ($data['config'] ?? $data['Config']) : [],
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'connector_key' => $this->connectorKey,
            'name' => $this->name,
            'description' => $this->description,
            'connector_type' => $this->connectorType,
            'auth_type' => $this->authType,
            'status' => $this->status,
            'module_key' => $this->moduleKey,
            'config' => $this->config,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
