<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationMembershipSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->resource['organization']->public_id,
            'name' => $this->resource['organization']->name,
            'slug' => $this->resource['organization']->slug,
            'status' => $this->resource['organization']->status->value,
            'organization_code' => $this->resource['organization']->organization_code,
            'membership' => new MembershipResource($this->resource['membership']),
        ];
    }
}
