<?php

namespace App\Http\Requests\Audit;

use App\Enums\AuditCategory;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditEventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['category', 'severity', 'action'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([
                    $field => array_values(array_filter(array_map(
                        trim(...),
                        explode(',', $this->input($field)),
                    ))),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
            'category' => ['sometimes', 'array'],
            'category.*' => ['string', Rule::enum(AuditCategory::class)],
            'severity' => ['sometimes', 'array'],
            'severity.*' => ['string', 'in:info,warning,critical'],
            'action' => ['sometimes', 'array'],
            'action.*' => ['string', 'max:64'],
            'actor_user_public_id' => ['sometimes', 'uuid'],
            'actor_membership_public_id' => ['sometimes', 'uuid'],
            'entity_type' => ['sometimes', 'string', Rule::enum(AuditEntityType::class)],
            'entity_public_id' => ['sometimes', 'uuid'],
            'workspace_public_id' => ['sometimes', 'uuid'],
            'request_id' => ['sometimes', 'uuid'],
            'retention_class' => ['sometimes', 'array'],
            'retention_class.*' => ['string', Rule::enum(AuditRetentionClass::class)],
            'occurred_from' => ['sometimes', 'date'],
            'occurred_to' => ['sometimes', 'date', 'after_or_equal:occurred_from'],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'in:occurred_at_desc,occurred_at_asc'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $from = $this->input('occurred_from');
            $to = $this->input('occurred_to');

            if ($from === null || $to === null) {
                return;
            }

            if ($this->filled('entity_public_id')) {
                return;
            }

            $fromDate = \Illuminate\Support\Carbon::parse($from);
            $toDate = \Illuminate\Support\Carbon::parse($to);

            if ($fromDate->diffInDays($toDate) > 366) {
                $validator->errors()->add(
                    'occurred_to',
                    'Date range cannot exceed 366 days without entity_public_id.',
                );
            }
        });
    }
}
