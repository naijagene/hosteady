<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordValidationReport implements \JsonSerializable
{
    /**
     * @param  list<EntityRecordValidationIssue>  $issues
     */
    public function __construct(
        public string $moduleKey,
        public string $entityKey,
        public bool $valid,
        public array $issues = [],
        public ?string $recordPublicId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $issues = [];
        foreach (is_array($data['issues'] ?? null) ? $data['issues'] : [] as $issue) {
            if (is_array($issue)) {
                $issues[] = EntityRecordValidationIssue::fromArray($issue);
            }
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            entityKey: (string) ($data['entity_key'] ?? ''),
            valid: (bool) ($data['valid'] ?? false),
            issues: $issues,
            recordPublicId: isset($data['record_public_id']) ? (string) $data['record_public_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'valid' => $this->valid,
            'issues' => array_map(fn (EntityRecordValidationIssue $issue) => $issue->toArray(), $this->issues),
            'record_public_id' => $this->recordPublicId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
