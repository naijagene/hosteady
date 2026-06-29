<?php

namespace App\Modules\Sdk\Personalization\Data;

readonly class FavoriteItem implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $favoriteType,
        public ?string $subjectPublicId,
        public ?string $label,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            favoriteType: (string) ($data['favorite_type'] ?? $data['subject_type'] ?? $data['favoriteType'] ?? ''),
            subjectPublicId: isset($data['subject_public_id']) ? (string) $data['subject_public_id'] : null,
            label: isset($data['label']) ? (string) $data['label'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'favorite_type' => $this->favoriteType,
            'subject_public_id' => $this->subjectPublicId,
            'label' => $this->label,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
