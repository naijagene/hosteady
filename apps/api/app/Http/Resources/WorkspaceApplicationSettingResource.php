<?php

namespace App\Http\Resources;

use App\Services\WorkspaceApplication\WorkspaceSettingMasker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WorkspaceApplicationSetting
 */
class WorkspaceApplicationSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $masker = app(WorkspaceSettingMasker::class);

        return [
            'public_id' => $this->public_id,
            'setting_key' => $this->setting_key,
            'value' => $masker->maskValue($this->setting_value, $this->is_sensitive),
            'value_redacted' => $masker->isRedacted($this->is_sensitive),
            'type' => $this->setting_type->value ?? $this->setting_type,
            'version' => $this->version,
            'is_sensitive' => $this->is_sensitive,
        ];
    }
}
