<?php

namespace App\Modules\Sdk\Development\Data;

readonly class BusinessModuleValidationReport implements \JsonSerializable
{
    /**
     * @param  list<BusinessModuleValidationIssue>  $issues
     */
    public function __construct(
        public string $moduleKey,
        public bool $valid,
        public array $issues = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $issues = [];
        foreach (is_array($data['issues'] ?? null) ? $data['issues'] : [] as $issue) {
            if (is_array($issue)) {
                $issues[] = BusinessModuleValidationIssue::fromArray($issue);
            }
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            valid: (bool) ($data['valid'] ?? false),
            issues: $issues,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'valid' => $this->valid,
            'issues' => array_map(fn (BusinessModuleValidationIssue $i) => $i->toArray(), $this->issues),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
