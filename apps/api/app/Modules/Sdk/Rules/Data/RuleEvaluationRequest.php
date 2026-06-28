<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleEvaluationRequest implements \JsonSerializable
{
    public function __construct(
        public array $context = [],
        public string $triggerType = 'manual',
        public array $rulePublicIds = [],
        public array $facts = [],
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            context: is_array($data['context'] ?? $data['context'] ?? null) ? ($data['context'] ?? $data['context']) : [],
            triggerType: (string) ($data['trigger_type'] ?? $data['triggerType'] ?? ''),
            rulePublicIds: is_array($data['rule_public_ids'] ?? $data['rulePublicIds'] ?? null) ? ($data['rule_public_ids'] ?? $data['rulePublicIds']) : [],
            facts: is_array($data['facts'] ?? $data['facts'] ?? null) ? ($data['facts'] ?? $data['facts']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'context' => $this->context,
            'trigger_type' => $this->triggerType,
            'rule_public_ids' => $this->rulePublicIds,
            'facts' => $this->facts,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
