<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableWorkspaceApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->resource;

        return [
            'organization_application_public_id' => $payload['organization_application_public_id'],
            'status' => $payload['status'],
            'installed_version' => $payload['installed_version'],
            'already_enabled' => false,
            'application' => new ApplicationResource($payload['application']),
        ];
    }
}
