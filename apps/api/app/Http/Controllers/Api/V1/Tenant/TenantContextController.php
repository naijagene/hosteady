<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantContextResource;
use App\Models\Organization;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TenantContextController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): TenantContextResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $this->authorize('view', $context->organization);

        return new TenantContextResource($context);
    }
}
