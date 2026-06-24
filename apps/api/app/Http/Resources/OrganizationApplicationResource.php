<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\OrganizationApplication
 */
class OrganizationApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'status' => $this->status->value ?? $this->status,
            'installed_version' => $this->installed_version,
            'installed_at' => $this->installed_at?->toIso8601String(),
            'installed_by_membership_public_id' => $this->installedByMembership?->public_id,
            'application' => new ApplicationResource($this->whenLoaded('application')),
        ];
    }
}
