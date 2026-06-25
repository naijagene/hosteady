<?php

namespace App\Http\Requests\WorkspaceApplication;

use Illuminate\Foundation\Http\FormRequest;

class ResetWorkspaceApplicationSettingsRequest extends FormRequest
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
            'keys' => ['sometimes', 'array'],
            'keys.*' => ['string', 'max:128'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
