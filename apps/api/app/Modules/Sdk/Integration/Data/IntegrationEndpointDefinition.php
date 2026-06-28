<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationEndpointDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public ?string $connectorPublicId,
        public string $endpointKey,
        public string $name,
        public string $endpointType,
        public string $direction,
        public string $status,
        public ?string $urlTemplate,
        public ?string $method,
        public array $headers,
        public array $bodyTemplate,
        public array $authReference,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['PublicId'] ?? ''),
            connectorPublicId: isset($data['connector_public_id']) ? (string) $data['connector_public_id'] : (isset($data['ConnectorPublicId']) ? (string) $data['ConnectorPublicId'] : null),
            endpointKey: (string) ($data['endpoint_key'] ?? $data['EndpointKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['Name'] ?? ''),
            endpointType: (string) ($data['endpoint_type'] ?? $data['EndpointType'] ?? ''),
            direction: (string) ($data['direction'] ?? $data['Direction'] ?? ''),
            status: (string) ($data['status'] ?? $data['Status'] ?? ''),
            urlTemplate: isset($data['url_template']) ? (string) $data['url_template'] : (isset($data['UrlTemplate']) ? (string) $data['UrlTemplate'] : null),
            method: isset($data['method']) ? (string) $data['method'] : (isset($data['Method']) ? (string) $data['Method'] : null),
            headers: is_array($data['headers'] ?? $data['Headers'] ?? null) ? ($data['headers'] ?? $data['Headers']) : [],
            bodyTemplate: is_array($data['body_template'] ?? $data['BodyTemplate'] ?? null) ? ($data['body_template'] ?? $data['BodyTemplate']) : [],
            authReference: is_array($data['auth_reference'] ?? $data['AuthReference'] ?? null) ? ($data['auth_reference'] ?? $data['AuthReference']) : [],
            metadata: is_array($data['metadata'] ?? $data['Metadata'] ?? null) ? ($data['metadata'] ?? $data['Metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'connector_public_id' => $this->connectorPublicId,
            'endpoint_key' => $this->endpointKey,
            'name' => $this->name,
            'endpoint_type' => $this->endpointType,
            'direction' => $this->direction,
            'status' => $this->status,
            'url_template' => $this->urlTemplate,
            'method' => $this->method,
            'headers' => $this->headers,
            'body_template' => $this->bodyTemplate,
            'auth_reference' => $this->authReference,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
