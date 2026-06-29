<?php

namespace App\Modules\Sdk\Personalization\Data;

readonly class RecentItem implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $itemType,
        public ?string $subjectPublicId,
        public ?string $label,
        public ?string $visitedAt,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            itemType: (string) ($data['item_type'] ?? $data['subject_type'] ?? $data['itemType'] ?? ''),
            subjectPublicId: isset($data['subject_public_id']) ? (string) $data['subject_public_id'] : null,
            label: isset($data['label']) ? (string) ($data['label'] ?? $data['title'] ?? null) : (isset($data['title']) ? (string) $data['title'] : null),
            visitedAt: isset($data['visited_at']) ? (string) $data['visited_at'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'item_type' => $this->itemType,
            'subject_public_id' => $this->subjectPublicId,
            'label' => $this->label,
            'visited_at' => $this->visitedAt,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
