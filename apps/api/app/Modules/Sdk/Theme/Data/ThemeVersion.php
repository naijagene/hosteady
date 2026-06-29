<?php

namespace App\Modules\Sdk\Theme\Data;

readonly class ThemeVersion implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $themeDefinitionPublicId,
        public int $versionNumber,
        public string $status,
        public array $snapshot,
        public ?string $changeSummary,
        public array $metadata,
        public ?string $publishedAt
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            themeDefinitionPublicId: (string) ($data['theme_definition_public_id'] ?? $data['themeDefinitionPublicId'] ?? ''),
            versionNumber: (int) ($data['version_number'] ?? $data['versionNumber'] ?? 0),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            snapshot: is_array($data['snapshot'] ?? $data['snapshot'] ?? null) ? ($data['snapshot'] ?? $data['snapshot']) : [],
            changeSummary: isset($data['change_summary']) ? (string) $data['change_summary'] : (isset($data['changeSummary']) ? (string) $data['changeSummary'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            publishedAt: isset($data['published_at']) ? (string) $data['published_at'] : (isset($data['publishedAt']) ? (string) $data['publishedAt'] : null),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'theme_definition_public_id' => $this->themeDefinitionPublicId,
            'version_number' => $this->versionNumber,
            'status' => $this->status,
            'snapshot' => $this->snapshot,
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
