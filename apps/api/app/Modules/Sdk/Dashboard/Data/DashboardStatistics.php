<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardStatistics implements \JsonSerializable
{
    /**
     * @param  list<string>  $registeredModules
     */
    public function __construct(
        public int $definitions = 0,
        public int $widgets = 0,
        public int $views = 0,
        public array $registeredModules = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definitions: (int) ($data['definitions'] ?? 0),
            widgets: (int) ($data['widgets'] ?? 0),
            views: (int) ($data['views'] ?? 0),
            registeredModules: is_array($data['registered_modules'] ?? null) ? $data['registered_modules'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'definitions' => $this->definitions,
            'widgets' => $this->widgets,
            'views' => $this->views,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
