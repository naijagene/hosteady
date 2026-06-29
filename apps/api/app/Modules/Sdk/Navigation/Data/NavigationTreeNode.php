<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationTreeNode implements \JsonSerializable
{
    public function __construct(
        public array $item,
        public array $children,
        public int $depth
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            item: is_array($data['item'] ?? $data['item'] ?? null) ? ($data['item'] ?? $data['item']) : [],
            children: is_array($data['children'] ?? $data['children'] ?? null) ? ($data['children'] ?? $data['children']) : [],
            depth: (int) ($data['depth'] ?? $data['depth'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'item' => $this->item,
            'children' => $this->children,
            'depth' => $this->depth,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
