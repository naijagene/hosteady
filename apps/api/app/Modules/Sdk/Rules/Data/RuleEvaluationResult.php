<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleEvaluationResult implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public bool $matched,
        public bool $allowed = true,
        public array $violations = [],
        public array $traces = [],
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            matched: (bool) ($data['matched'] ?? $data['matched'] ?? false),
            allowed: (bool) ($data['allowed'] ?? $data['allowed'] ?? false),
            violations: is_array($data['violations'] ?? $data['violations'] ?? null) ? ($data['violations'] ?? $data['violations']) : [],
            traces: is_array($data['traces'] ?? $data['traces'] ?? null) ? ($data['traces'] ?? $data['traces']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'matched' => $this->matched,
            'allowed' => $this->allowed,
            'violations' => $this->violations,
            'traces' => $this->traces,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
