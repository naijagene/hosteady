<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationProcessingResult implements \JsonSerializable
{
    public function __construct(
        public string $eventPublicId,
        public array $dispatches,
        public array $warnings,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventPublicId: (string) ($data['event_public_id'] ?? $data['EventPublicId'] ?? ''),
            dispatches: is_array($data['dispatches'] ?? $data['Dispatches'] ?? null) ? ($data['dispatches'] ?? $data['Dispatches']) : [],
            warnings: is_array($data['warnings'] ?? $data['Warnings'] ?? null) ? ($data['warnings'] ?? $data['Warnings']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'event_public_id' => $this->eventPublicId,
            'dispatches' => $this->dispatches,
            'warnings' => $this->warnings,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
