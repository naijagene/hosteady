<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleFact implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public mixed $value,
        public string $source = 'context'
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? $data['key'] ?? ''),
            value: $data['value'] ?? $data['value'] ?? null,
            source: (string) ($data['source'] ?? $data['source'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'source' => $this->source
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
