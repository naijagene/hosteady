<?php

namespace App\Modules\Sdk\Development\Data;

use App\Modules\Sdk\Development\Enums\BusinessModuleValidationSeverity;

readonly class BusinessModuleValidationIssue implements \JsonSerializable
{
    public function __construct(
        public string $code,
        public string $message,
        public string $severity,
        public ?string $field = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) ($data['code'] ?? ''),
            message: (string) ($data['message'] ?? ''),
            severity: (string) ($data['severity'] ?? BusinessModuleValidationSeverity::Error->value),
            field: isset($data['field']) ? (string) $data['field'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
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
