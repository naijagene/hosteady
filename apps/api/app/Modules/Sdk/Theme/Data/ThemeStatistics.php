<?php

namespace App\Modules\Sdk\Theme\Data;

readonly class ThemeStatistics implements \JsonSerializable
{
    public function __construct(
        public int $definitions,
        public int $versions,
        public int $brandProfiles,
        public int $publishedDefinitions,
        public int $registeredModules
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definitions: (int) ($data['definitions'] ?? $data['definitions'] ?? 0),
            versions: (int) ($data['versions'] ?? $data['versions'] ?? 0),
            brandProfiles: (int) ($data['brand_profiles'] ?? $data['brandProfiles'] ?? 0),
            publishedDefinitions: (int) ($data['published_definitions'] ?? $data['publishedDefinitions'] ?? 0),
            registeredModules: (int) ($data['registered_modules'] ?? $data['registeredModules'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'definitions' => $this->definitions,
            'versions' => $this->versions,
            'brand_profiles' => $this->brandProfiles,
            'published_definitions' => $this->publishedDefinitions,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
