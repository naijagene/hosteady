<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class InstallApplicationRequest extends FormRequest
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
            'application_public_id' => ['required', 'uuid'],
        ];
    }
}
