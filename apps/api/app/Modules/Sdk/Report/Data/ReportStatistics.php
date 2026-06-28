<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportStatistics implements \JsonSerializable
{
    /**
     * @param  list<string>  $registeredModules
     */
    public function __construct(
        public int $definitions = 0,
        public int $templates = 0,
        public int $runs = 0,
        public int $exports = 0,
        public int $schedules = 0,
        public array $registeredModules = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definitions: (int) ($data['definitions'] ?? 0),
            templates: (int) ($data['templates'] ?? 0),
            runs: (int) ($data['runs'] ?? 0),
            exports: (int) ($data['exports'] ?? 0),
            schedules: (int) ($data['schedules'] ?? 0),
            registeredModules: is_array($data['registered_modules'] ?? null) ? $data['registered_modules'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'definitions' => $this->definitions,
            'templates' => $this->templates,
            'runs' => $this->runs,
            'exports' => $this->exports,
            'schedules' => $this->schedules,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
