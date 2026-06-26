<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowValidationIssue implements \JsonSerializable
{
    public function __construct(
        public string $code,
        public string $message,
        public string $severity,
        public ?string $path = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            code: (string) $payload['code'],
            message: (string) $payload['message'],
            severity: (string) $payload['severity'],
            path: isset($payload['path']) ? (string) $payload['path'] : null,
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
            'path' => $this->path,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
