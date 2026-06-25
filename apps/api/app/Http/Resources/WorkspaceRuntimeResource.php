<?php

namespace App\Http\Resources;

use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkspaceRuntimeContext
 */
class WorkspaceRuntimeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkspaceRuntimeContext $runtime */
        $runtime = $this->resource;

        return [
            'organization' => $runtime->organization->toArray(),
            'workspace' => $runtime->workspace->toArray(),
            'membership' => $runtime->membership->toArray(),
            'active_applications' => ResolvedWorkspaceApplicationResource::collection($runtime->activeApplications),
            'active_application' => $runtime->activeApplication === null
                ? null
                : new ResolvedWorkspaceApplicationResource($runtime->activeApplication),
            'runtime_version' => $runtime->runtimeVersion,
            'settings_version' => $runtime->settingsVersion,
            'runtime_metadata' => $runtime->runtimeMetadata,
            'capabilities' => $runtime->capabilities,
        ];
    }
}
