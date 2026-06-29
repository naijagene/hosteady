<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationTree implements \JsonSerializable
{
    public function __construct(
        public array $nodes,
        public array $warnings
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nodes: is_array($data['nodes'] ?? $data['nodes'] ?? null) ? ($data['nodes'] ?? $data['nodes']) : [],
            warnings: is_array($data['warnings'] ?? $data['warnings'] ?? null) ? ($data['warnings'] ?? $data['warnings']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'nodes' => $this->nodes,
            'warnings' => $this->warnings,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
