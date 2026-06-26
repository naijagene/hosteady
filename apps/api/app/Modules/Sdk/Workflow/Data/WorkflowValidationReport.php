<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowValidationReport implements \JsonSerializable
{
    /**
     * @param  list<WorkflowValidationIssue>  $issues
     */
    public function __construct(
        public bool $valid,
        public array $issues = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $issues = array_map(
            fn (array $issue) => WorkflowValidationIssue::fromArray($issue),
            is_array($payload['issues'] ?? null) ? $payload['issues'] : [],
        );

        return new self(
            valid: (bool) ($payload['valid'] ?? false),
            issues: $issues,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'issues' => array_map(fn (WorkflowValidationIssue $issue) => $issue->toArray(), $this->issues),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
