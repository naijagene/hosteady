<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceRuntimeHealthResource;
use App\Models\WorkspaceApplication;
use App\Services\Runtime\RuntimeHealthService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WorkspaceRuntimeHealthController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(RuntimeHealthService $healthService): WorkspaceRuntimeHealthResource
    {
        $this->authorize('viewAny', WorkspaceApplication::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new WorkspaceRuntimeHealthResource(
            $healthService->assess($context),
        );
    }
}
