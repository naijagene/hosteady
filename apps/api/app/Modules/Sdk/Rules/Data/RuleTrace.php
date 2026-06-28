<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleTrace implements \JsonSerializable
{
    public function __construct(
        public string $rulePublicId,
        public bool $matched,
        public array $conditions = [],
        public array $actions = [],
        public int $durationMs = 0
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            rulePublicId: (string) ($data['rule_public_id'] ?? $data['rulePublicId'] ?? ''),
            matched: (bool) ($data['matched'] ?? $data['matched'] ?? false),
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
            actions: is_array($data['actions'] ?? $data['actions'] ?? null) ? ($data['actions'] ?? $data['actions']) : [],
            durationMs: (int) ($data['duration_ms'] ?? $data['durationMs'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'rule_public_id' => $this->rulePublicId,
            'matched' => $this->matched,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'duration_ms' => $this->durationMs
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
