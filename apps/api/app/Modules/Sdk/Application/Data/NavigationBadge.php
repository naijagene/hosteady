<?php

namespace App\Modules\Sdk\Application\Data;

readonly class NavigationBadge implements \JsonSerializable
{
    public function __construct(
        public string $label,
        public string $variant,
        public int $count
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            label: (string) ($data['label'] ?? $data['label'] ?? ''),
            variant: (string) ($data['variant'] ?? $data['variant'] ?? ''),
            count: (int) ($data['count'] ?? $data['count'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'variant' => $this->variant,
            'count' => $this->count,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
