<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardHealthReport implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $missingTables
     */
    public function __construct(
        public bool $enabled = true,
        public int $definitions = 0,
        public int $widgets = 0,
        public int $views = 0,
        public array $warnings = [],
        public string $status = 'healthy',
        public array $missingTables = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'definitions' => $this->definitions,
            'widgets' => $this->widgets,
            'views' => $this->views,
            'warnings' => $this->warnings,
            'status' => $this->status,
            'missing_tables' => $this->missingTables,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
