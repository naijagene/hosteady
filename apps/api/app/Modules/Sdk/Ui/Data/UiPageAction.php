<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiPageAction implements \JsonSerializable
{
    public function __construct(
        public string $actionKey,
        public string $actionType,
        public string $label,
        public array $config,
        public array $conditions
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            actionKey: (string) ($data['action_key'] ?? $data['actionKey'] ?? ''),
            actionType: (string) ($data['action_type'] ?? $data['actionType'] ?? ''),
            label: (string) ($data['label'] ?? $data['label'] ?? ''),
            config: is_array($data['config'] ?? $data['config'] ?? null) ? ($data['config'] ?? $data['config']) : [],
            conditions: is_array($data['conditions'] ?? $data['conditions'] ?? null) ? ($data['conditions'] ?? $data['conditions']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'action_key' => $this->actionKey,
            'action_type' => $this->actionType,
            'label' => $this->label,
            'config' => $this->config,
            'conditions' => $this->conditions,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
