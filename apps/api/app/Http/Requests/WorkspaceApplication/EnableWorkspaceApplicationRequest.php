<?php

namespace App\Http\Requests\WorkspaceApplication;

use Illuminate\Foundation\Http\FormRequest;

class EnableWorkspaceApplicationRequest extends FormRequest
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
            'organization_application_public_id' => ['required', 'uuid'],
        ];
    }
}
