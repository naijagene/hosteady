<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleStatistics implements \JsonSerializable
{
    public function __construct(
        public int $ruleSets,
        public int $ruleDefinitions,
        public int $evaluations,
        public int $executions,
        public int $violations
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            ruleSets: (int) ($data['rule_sets'] ?? $data['ruleSets'] ?? 0),
            ruleDefinitions: (int) ($data['rule_definitions'] ?? $data['ruleDefinitions'] ?? 0),
            evaluations: (int) ($data['evaluations'] ?? $data['evaluations'] ?? 0),
            executions: (int) ($data['executions'] ?? $data['executions'] ?? 0),
            violations: (int) ($data['violations'] ?? $data['violations'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'rule_sets' => $this->ruleSets,
            'rule_definitions' => $this->ruleDefinitions,
            'evaluations' => $this->evaluations,
            'executions' => $this->executions,
            'violations' => $this->violations
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
