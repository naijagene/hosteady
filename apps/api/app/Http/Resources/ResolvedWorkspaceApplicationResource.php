<?php

namespace App\Http\Resources;

use App\Services\WorkspaceApplication\Data\ResolvedWorkspaceApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ResolvedWorkspaceApplication
 */
class ResolvedWorkspaceApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ResolvedWorkspaceApplication $application */
        $application = $this->resource;

        return $application->toArray();
    }
}
