<?php

namespace App\Http\Requests\WorkspaceApplication;

use App\Enums\WorkspaceSettingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceApplicationSettingsRequest extends FormRequest
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
            'settings' => ['required', 'array', 'min:1'],
            'settings.*' => ['required', 'array'],
            'settings.*.value' => ['present'],
            'settings.*.type' => ['required', 'string', Rule::enum(WorkspaceSettingType::class)],
            'settings.*.is_sensitive' => ['sometimes', 'boolean'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
