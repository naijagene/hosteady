<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableStatistics implements \JsonSerializable
{
    /**
     * @param  list<string>  $registeredModules
     */
    public function __construct(
        public int $definitions = 0,
        public int $views = 0,
        public array $registeredModules = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definitions: (int) ($data['definitions'] ?? 0),
            views: (int) ($data['views'] ?? 0),
            registeredModules: is_array($data['registered_modules'] ?? null)
                ? array_values(array_map('strval', $data['registered_modules']))
                : [],
        );
    }

    public function toArray(): array
    {
        return [
            'definitions' => $this->definitions,
            'views' => $this->views,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
