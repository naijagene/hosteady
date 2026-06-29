<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationStatistics implements \JsonSerializable
{
    public function __construct(
        public int $definitions,
        public int $versions,
        public int $items,
        public int $personalizations,
        public int $registeredModules
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definitions: (int) ($data['definitions'] ?? $data['definitions'] ?? 0),
            versions: (int) ($data['versions'] ?? $data['versions'] ?? 0),
            items: (int) ($data['items'] ?? $data['items'] ?? 0),
            personalizations: (int) ($data['personalizations'] ?? $data['personalizations'] ?? 0),
            registeredModules: (int) ($data['registered_modules'] ?? $data['registeredModules'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'definitions' => $this->definitions,
            'versions' => $this->versions,
            'items' => $this->items,
            'personalizations' => $this->personalizations,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
