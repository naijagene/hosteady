<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowVersionData implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $definitionJson
     * @param  array<string, mixed>|null  $validationReport
     */
    public function __construct(
        public string $publicId,
        public int $versionNumber,
        public string $status,
        public array $definitionJson,
        public ?array $validationReport = null,
        public ?string $publishedAt = null,
        public ?string $archivedAt = null,
        public ?string $createdAt = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            publicId: (string) $payload['public_id'],
            versionNumber: (int) $payload['version_number'],
            status: (string) $payload['status'],
            definitionJson: is_array($payload['definition_json'] ?? null) ? $payload['definition_json'] : [],
            validationReport: is_array($payload['validation_report'] ?? null) ? $payload['validation_report'] : null,
            publishedAt: isset($payload['published_at']) ? (string) $payload['published_at'] : null,
            archivedAt: isset($payload['archived_at']) ? (string) $payload['archived_at'] : null,
            createdAt: isset($payload['created_at']) ? (string) $payload['created_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'version_number' => $this->versionNumber,
            'status' => $this->status,
            'definition_json' => $this->definitionJson,
            'validation_report' => $this->validationReport,
            'published_at' => $this->publishedAt,
            'archived_at' => $this->archivedAt,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
