<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationPersonalization implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public ?string $navigationDefinitionPublicId,
        public ?string $membershipPublicId,
        public array $personalization,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            navigationDefinitionPublicId: isset($data['navigation_definition_public_id']) ? (string) $data['navigation_definition_public_id'] : (isset($data['navigationDefinitionPublicId']) ? (string) $data['navigationDefinitionPublicId'] : null),
            membershipPublicId: isset($data['membership_public_id']) ? (string) $data['membership_public_id'] : (isset($data['membershipPublicId']) ? (string) $data['membershipPublicId'] : null),
            personalization: is_array($data['personalization'] ?? $data['personalization'] ?? null) ? ($data['personalization'] ?? $data['personalization']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'navigation_definition_public_id' => $this->navigationDefinitionPublicId,
            'membership_public_id' => $this->membershipPublicId,
            'personalization' => $this->personalization,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
