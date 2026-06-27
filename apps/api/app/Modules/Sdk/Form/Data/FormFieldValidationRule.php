<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormFieldValidationRule implements \JsonSerializable
{
    public function __construct(
        public string $field,
        public string $rule,
        public ?string $message = null,
        public array $parameters = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            field: (string) ($data['field'] ?? ''),
            rule: (string) ($data['rule'] ?? ''),
            message: isset($data['message']) ? (string) $data['message'] : null,
            parameters: is_array($data['parameters'] ?? null) ? $data['parameters'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'rule' => $this->rule,
            'message' => $this->message,
            'parameters' => $this->parameters,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
