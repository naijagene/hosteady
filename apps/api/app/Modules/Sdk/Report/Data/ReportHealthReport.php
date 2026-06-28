<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportHealthReport implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $missingTables
     */
    public function __construct(
        public bool $enabled = true,
        public int $definitions = 0,
        public int $templates = 0,
        public int $runs = 0,
        public int $exports = 0,
        public int $schedules = 0,
        public array $warnings = [],
        public string $status = 'healthy',
        public array $missingTables = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? true),
            definitions: (int) ($data['definitions'] ?? 0),
            templates: (int) ($data['templates'] ?? 0),
            runs: (int) ($data['runs'] ?? 0),
            exports: (int) ($data['exports'] ?? 0),
            schedules: (int) ($data['schedules'] ?? 0),
            warnings: is_array($data['warnings'] ?? null) ? $data['warnings'] : [],
            status: (string) ($data['status'] ?? 'healthy'),
            missingTables: is_array($data['missing_tables'] ?? null) ? $data['missing_tables'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'definitions' => $this->definitions,
            'templates' => $this->templates,
            'runs' => $this->runs,
            'exports' => $this->exports,
            'schedules' => $this->schedules,
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
