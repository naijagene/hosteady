<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WorkspaceApplication
 */
class WorkspaceApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'status' => $this->status->value ?? $this->status,
            'enabled_version' => $this->enabled_version,
            'is_bootstrap' => $this->is_bootstrap,
            'enabled_at' => $this->enabled_at?->toIso8601String(),
            'enabled_by_membership_public_id' => $this->enabledByMembership?->public_id,
            'organization_application_public_id' => $this->organizationApplication?->public_id,
            'application' => new ApplicationResource($this->whenLoaded('application')),
        ];
    }
}
