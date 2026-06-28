<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleViolation implements \JsonSerializable
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $field,
        public string $severity = 'warning',
        public ?string $rulePublicId,
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) ($data['code'] ?? $data['code'] ?? ''),
            message: (string) ($data['message'] ?? $data['message'] ?? ''),
            field: isset($data['field']) ? (string) $data['field'] : (isset($data['field']) ? (string) $data['field'] : null),
            severity: (string) ($data['severity'] ?? $data['severity'] ?? ''),
            rulePublicId: isset($data['rule_public_id']) ? (string) $data['rule_public_id'] : (isset($data['rulePublicId']) ? (string) $data['rulePublicId'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'field' => $this->field,
            'severity' => $this->severity,
            'rule_public_id' => $this->rulePublicId,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
