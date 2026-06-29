<?php

namespace App\Modules\Sdk\Personalization\Data;

readonly class PreferenceItem implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $preferenceKey,
        public string $preferenceType,
        public mixed $value,
        public string $scope,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            preferenceKey: (string) ($data['preference_key'] ?? $data['preferenceKey'] ?? ''),
            preferenceType: (string) ($data['preference_type'] ?? $data['preferenceType'] ?? 'string'),
            value: $data['value'] ?? $data['preference_value'] ?? null,
            scope: (string) ($data['scope'] ?? 'membership'),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'preference_key' => $this->preferenceKey,
            'preference_type' => $this->preferenceType,
            'value' => $this->value,
            'scope' => $this->scope,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
