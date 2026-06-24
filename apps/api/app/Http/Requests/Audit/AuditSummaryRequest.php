<?php

namespace App\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;

class AuditSummaryRequest extends FormRequest
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
            'occurred_from' => ['sometimes', 'date'],
            'occurred_to' => ['sometimes', 'date', 'after_or_equal:occurred_from'],
            'workspace_public_id' => ['sometimes', 'uuid'],
            'request_id' => ['sometimes', 'uuid'],
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

            $fromDate = \Illuminate\Support\Carbon::parse($from);
            $toDate = \Illuminate\Support\Carbon::parse($to);

            if ($fromDate->diffInDays($toDate) > 366) {
                $validator->errors()->add(
                    'occurred_to',
                    'Date range cannot exceed 366 days.',
                );
            }
        });
    }
}
