<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleDefinition implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $ruleSetPublicId,
        public string $name,
        public ?string $description,
        public string $type = 'validation',
        public string $scope = 'organization',
        public string $status = 'draft',
        public string $triggerType = 'manual',
        public int $priority = 100,
        public array $conditions = [],
        public array $actions = [],
        public ?string $moduleKey,
        public ?string $entityKey,
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            ruleSetPublicId: (string) ($data['rule_set_public_id'] ?? $data['ruleSetPublicId'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : (isset($data['description']) ? (string) $data['description'] : null),
            type: (string) ($data['type'] ?? $data['type'] ?? ''),
            scope: (string) ($data['scope'] ?? $data['scope'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            triggerType: (string) ($data['trigger_type'] ?? $data['triggerType'] ?? ''),
            priority: (int) ($data['priority'] ?? $data['priority'] ?? 0),
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
            actions: is_array($data['actions'] ?? $data['actions'] ?? null) ? ($data['actions'] ?? $data['actions']) : [],
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : (isset($data['entityKey']) ? (string) $data['entityKey'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'rule_set_public_id' => $this->ruleSetPublicId,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'scope' => $this->scope,
            'status' => $this->status,
            'trigger_type' => $this->triggerType,
            'priority' => $this->priority,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
