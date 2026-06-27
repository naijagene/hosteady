<?php

namespace App\Modules\Sdk\Entity\Data;

use App\Modules\Sdk\Entity\Enums\EntityValidationSeverity;

readonly class EntityValidationIssue implements \JsonSerializable
{
    public function __construct(
        public string $code,
        public string $message,
        public string $severity,
        public ?string $field = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) ($data['code'] ?? ''),
            message: (string) ($data['message'] ?? ''),
            severity: (string) ($data['severity'] ?? EntityValidationSeverity::Error->value),
            field: isset($data['field']) ? (string) $data['field'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
            'field' => $this->field,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
