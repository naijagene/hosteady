<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class RuntimeSettingValue
{
    public function __construct(
        public mixed $value,
        public string $type,
        public int $version,
        public bool $isSensitive,
        public bool $valueRedacted,
        public bool $isDefault = false,
        public ?string $definitionPublicId = null,
        public ?string $label = null,
        public ?string $category = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'value' => $this->value,
            'type' => $this->type,
            'version' => $this->version,
            'is_sensitive' => $this->isSensitive,
            'value_redacted' => $this->valueRedacted,
            'is_default' => $this->isDefault,
        ];

        if ($this->definitionPublicId !== null) {
            $payload['definition_public_id'] = $this->definitionPublicId;
        }

        if ($this->label !== null) {
            $payload['label'] = $this->label;
        }

        if ($this->category !== null) {
            $payload['category'] = $this->category;
        }

        return $payload;
    }
}
