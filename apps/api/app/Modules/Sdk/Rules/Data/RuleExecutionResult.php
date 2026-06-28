<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleExecutionResult implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $status = 'completed',
        public array $matchedRules = [],
        public array $actionsApplied = [],
        public array $warnings = [],
        public array $violations = [],
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            matchedRules: is_array($data['matched_rules'] ?? $data['matchedRules'] ?? null) ? ($data['matched_rules'] ?? $data['matchedRules']) : [],
            actionsApplied: is_array($data['actions_applied'] ?? $data['actionsApplied'] ?? null) ? ($data['actions_applied'] ?? $data['actionsApplied']) : [],
            warnings: is_array($data['warnings'] ?? $data['warnings'] ?? null) ? ($data['warnings'] ?? $data['warnings']) : [],
            violations: is_array($data['violations'] ?? $data['violations'] ?? null) ? ($data['violations'] ?? $data['violations']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'status' => $this->status,
            'matched_rules' => $this->matchedRules,
            'actions_applied' => $this->actionsApplied,
            'warnings' => $this->warnings,
            'violations' => $this->violations,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
