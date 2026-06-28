<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordValidationIssue implements \JsonSerializable
{
    public function __construct(
        public string $code,
        public string $message,
        public string $severity = 'error',
        public ?string $field = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) ($data['code'] ?? ''),
            message: (string) ($data['message'] ?? ''),
            severity: (string) ($data['severity'] ?? 'error'),
            field: isset($data['field']) ? (string) $data['field'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
            'field' => $this->field,
        ], fn ($value) => $value !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
