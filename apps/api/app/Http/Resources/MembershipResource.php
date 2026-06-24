<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\OrganizationMembership
 */
class MembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'status' => $this->status->value ?? $this->status,
            'join_method' => $this->join_method->value ?? $this->join_method,
            'default_workspace_public_id' => $this->defaultWorkspace?->public_id,
        ];
    }
}
