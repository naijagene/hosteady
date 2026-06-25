<?php

namespace App\Http\Resources;

use App\Services\Authorization\TenantAuthorizationService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantContextResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TenantContext $context */
        $context = $this->resource;

        $authorizationService = app(TenantAuthorizationService::class);

        $runtimeSummary = app(WorkspaceRuntimeProvider::class)->resolveSummary($context);

        return [
            'user' => new UserResource($context->user),
            'organization' => new OrganizationResource($context->organization),
            'membership' => new MembershipResource($context->membership),
            'workspace' => new WorkspaceResource($context->workspace),
            'permissions' => $authorizationService->permissionsFor($context),
            'runtime_summary' => [
                'active_application_count' => $runtimeSummary->activeApplicationCount,
                'runtime_version' => $runtimeSummary->runtimeVersion,
                'settings_version' => $runtimeSummary->settingsVersion,
            ],
        ];
    }
}
