<?php

namespace App\Modules\Sdk\Application\Data;

readonly class ApplicationReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $applicationKey,
        public string $name,
        public string $status
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            applicationKey: (string) ($data['application_key'] ?? $data['applicationKey'] ?? ''),
            name: (string) ($data['name'] ?? $data['name'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'application_key' => $this->applicationKey,
            'name' => $this->name,
            'status' => $this->status,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
