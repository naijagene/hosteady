<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormHealthReport implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $missingTables
     */
    public function __construct(
        public bool $enabled,
        public int $definitions,
        public int $submissions,
        public int $drafts,
        public array $warnings = [],
        public string $status = 'healthy',
        public array $missingTables = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            definitions: (int) ($data['definitions'] ?? 0),
            submissions: (int) ($data['submissions'] ?? 0),
            drafts: (int) ($data['drafts'] ?? 0),
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
            status: (string) ($data['status'] ?? 'healthy'),
            missingTables: is_array($data['missing_tables'] ?? null) ? array_values(array_map('strval', $data['missing_tables'])) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'definitions' => $this->definitions,
            'submissions' => $this->submissions,
            'drafts' => $this->drafts,
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
