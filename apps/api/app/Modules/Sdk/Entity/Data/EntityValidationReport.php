<?php

namespace App\Modules\Sdk\Entity\Data;

readonly class EntityValidationReport implements \JsonSerializable
{
    /**
     * @param  list<EntityValidationIssue>  $issues
     */
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public bool $valid,
        public array $issues = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $issues = [];
        foreach (is_array($data['issues'] ?? null) ? $data['issues'] : [] as $issue) {
            if (is_array($issue)) {
                $issues[] = EntityValidationIssue::fromArray($issue);
            }
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            valid: (bool) ($data['valid'] ?? false),
            issues: $issues,
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'valid' => $this->valid,
            'issues' => array_map(fn (EntityValidationIssue $i) => $i->toArray(), $this->issues),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
