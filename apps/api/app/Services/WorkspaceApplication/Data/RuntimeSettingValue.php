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
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'type' => $this->type,
            'version' => $this->version,
            'is_sensitive' => $this->isSensitive,
            'value_redacted' => $this->valueRedacted,
        ];
    }
}
