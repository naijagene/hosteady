<?php

namespace App\Modules\Sdk\Form\Data;

use App\Modules\Sdk\Form\Enums\FormValidationSeverity;

readonly class FormValidationIssue implements \JsonSerializable
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
            severity: (string) ($data['severity'] ?? FormValidationSeverity::Error->value),
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
