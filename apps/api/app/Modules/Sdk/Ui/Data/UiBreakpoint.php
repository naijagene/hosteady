<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiBreakpoint implements \JsonSerializable
{
    public function __construct(
        public string $size,
        public int $minWidth,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            size: (string) ($data['size'] ?? $data['size'] ?? ''),
            minWidth: (int) ($data['min_width'] ?? $data['minWidth'] ?? 0),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'size' => $this->size,
            'min_width' => $this->minWidth,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
