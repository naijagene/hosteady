<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Form\Data\FormSubmissionResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof FormSubmissionResult) {
            return $this->resource->toArray();
        }

        /** @var array<string, mixed> $submission */
        $submission = $this->resource;

        return [
            'public_id' => $submission['public_id'] ?? $submission['submission_id'] ?? null,
            'module_key' => $submission['module_key'] ?? null,
            'form_key' => $submission['form_key'] ?? null,
            'entity_key' => $submission['entity_key'] ?? null,
            'entity_public_id' => $submission['entity_public_id'] ?? null,
            'status' => $submission['status'] ?? null,
            'submission_data' => $submission['submission_data'] ?? $submission['values'] ?? [],
            'validation_report' => $submission['validation_report'] ?? null,
            'submitted_at' => $submission['submitted_at'] ?? null,
            'metadata' => $submission['metadata'] ?? [],
        ];
    }
}
