<?php

namespace App\Http\Requests\WorkspaceApplication;

use Illuminate\Foundation\Http\FormRequest;

class WorkspaceApplicationSettingsHistoryRequest extends FormRequest
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
            'workspace_application_public_id' => ['required', 'uuid'],
            'setting_key' => ['sometimes', 'nullable', 'string', 'max:128'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
