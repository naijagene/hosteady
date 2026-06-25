<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WorkspaceApplicationSettingHistory
 */
class WorkspaceApplicationSettingHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'setting_key' => $this->setting_key,
            'version' => $this->version,
            'change_type' => $this->change_type->value ?? $this->change_type,
            'before_value' => $this->before_value,
            'after_value' => $this->after_value,
            'changed_by_membership_public_id' => $this->changedByMembership?->public_id,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
