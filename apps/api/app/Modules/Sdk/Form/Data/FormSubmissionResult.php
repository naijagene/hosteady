<?php

namespace App\Modules\Sdk\Form\Data;

use App\Modules\Sdk\Form\Enums\FormSubmissionStatus;

readonly class FormSubmissionResult implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $moduleKey,
        public string $formKey,
        public bool $success,
        public string $status = FormSubmissionStatus::Pending->value,
        public ?string $submissionId = null,
        public ?string $entityPublicId = null,
        public array $values = [],
        public array $warnings = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            formKey: (string) ($data['form_key'] ?? ''),
            success: (bool) ($data['success'] ?? false),
            status: (string) ($data['status'] ?? FormSubmissionStatus::Pending->value),
            submissionId: isset($data['submission_id']) ? (string) $data['submission_id'] : null,
            entityPublicId: isset($data['entity_public_id']) ? (string) $data['entity_public_id'] : null,
            values: is_array($data['values'] ?? null) ? $data['values'] : [],
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'form_key' => $this->formKey,
            'success' => $this->success,
            'status' => $this->status,
            'submission_id' => $this->submissionId,
            'entity_public_id' => $this->entityPublicId,
            'values' => $this->values,
            'warnings' => $this->warnings,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
