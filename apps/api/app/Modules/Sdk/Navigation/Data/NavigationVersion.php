<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationVersion implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $navigationDefinitionPublicId,
        public int $versionNumber,
        public string $status,
        public array $structure,
        public array $conditions,
        public ?string $changeSummary,
        public array $metadata,
        public ?string $publishedAt
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            navigationDefinitionPublicId: (string) ($data['navigation_definition_public_id'] ?? $data['navigationDefinitionPublicId'] ?? ''),
            versionNumber: (int) ($data['version_number'] ?? $data['versionNumber'] ?? 0),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            structure: is_array($data['structure'] ?? $data['structure'] ?? null) ? ($data['structure'] ?? $data['structure']) : [],
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
            changeSummary: isset($data['change_summary']) ? (string) $data['change_summary'] : (isset($data['changeSummary']) ? (string) $data['changeSummary'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            publishedAt: isset($data['published_at']) ? (string) $data['published_at'] : (isset($data['publishedAt']) ? (string) $data['publishedAt'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'navigation_definition_public_id' => $this->navigationDefinitionPublicId,
            'version_number' => $this->versionNumber,
            'status' => $this->status,
            'structure' => $this->structure,
            'conditions' => $this->conditions,
            'change_summary' => $this->changeSummary,
            'metadata' => $this->metadata,
            'published_at' => $this->publishedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
