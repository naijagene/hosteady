<?php

namespace App\Http\Requests\WorkspaceApplication;

use Illuminate\Foundation\Http\FormRequest;

class IndexWorkspaceApplicationSettingsRequest extends FormRequest
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
        ];
    }
}
