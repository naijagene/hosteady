<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferenceCatalogResource;
use App\Http\Resources\ReferenceItemResource;
use App\Services\Enterprise\ReferenceData\ReferenceDataService;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ReferenceDataController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ReferenceDataService $referenceDataService,
        private readonly TenantAuthorizationService $tenantAuthorizationService,
    ) {
    }

    public function catalog(string $catalogKey): JsonResponse
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        abort_unless($this->tenantAuthorizationService->allows($context, 'reference.read'), 403);

        $catalog = $this->referenceDataService->catalog($context, $catalogKey);

        if ($catalog === null) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'catalog' => new ReferenceCatalogResource($catalog),
                'items' => ReferenceItemResource::collection(
                    $this->referenceDataService->listItems($context, $catalogKey),
                ),
            ],
        ]);
    }

    public function item(string $catalogKey, string $code): ReferenceItemResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        abort_unless($this->tenantAuthorizationService->allows($context, 'reference.read'), 403);

        $item = $this->referenceDataService->findItem($context, $catalogKey, $code);

        if ($item === null) {
            abort(404);
        }

        return new ReferenceItemResource($item);
    }
}
