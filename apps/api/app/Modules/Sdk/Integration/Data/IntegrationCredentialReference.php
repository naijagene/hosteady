<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationCredentialReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $connectorKey,
        public string $credentialKey,
        public string $authType,
        public array $metadata,
        public ?string $rotatedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['PublicId'] ?? ''),
            connectorKey: (string) ($data['connector_key'] ?? $data['ConnectorKey'] ?? ''),
            credentialKey: (string) ($data['credential_key'] ?? $data['CredentialKey'] ?? ''),
            authType: (string) ($data['auth_type'] ?? $data['AuthType'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
            rotatedAt: isset($data['rotated_at']) ? (string) $data['rotated_at'] : (isset($data['RotatedAt']) ? (string) $data['RotatedAt'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'connector_key' => $this->connectorKey,
            'credential_key' => $this->credentialKey,
            'auth_type' => $this->authType,
            'metadata' => $this->metadata,
            'rotated_at' => $this->rotatedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
