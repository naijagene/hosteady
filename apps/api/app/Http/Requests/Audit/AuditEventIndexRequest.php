<?php

namespace App\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;

class AuditEventIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'category' => ['sometimes', 'string'],
            'action' => ['sometimes', 'string', 'max:64'],
            'severity' => ['sometimes', 'string', 'in:info,warning,critical'],
            'actor_user_public_id' => ['sometimes', 'uuid'],
            'entity_type' => ['sometimes', 'string', 'max:64'],
            'entity_public_id' => ['sometimes', 'uuid'],
            'occurred_from' => ['sometimes', 'date'],
            'occurred_to' => ['sometimes', 'date'],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
