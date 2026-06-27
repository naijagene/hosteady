<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormDraftReference implements \JsonSerializable
{
    public function __construct(
        public string $formKey,
        public string $draftId,
        public ?string $publicId = null,
        public ?string $moduleKey = null,
        public ?string $expiresAt = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            formKey: (string) ($data['form_key'] ?? ''),
            draftId: (string) ($data['draft_id'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : null,
            expiresAt: isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'form_key' => $this->formKey,
            'draft_id' => $this->draftId,
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'expires_at' => $this->expiresAt,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
