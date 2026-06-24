<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\AuditEventIndexRequest;
use App\Http\Resources\AuditEventResource;
use App\Models\AuditLog;
use App\Services\Audit\ActivityFeedService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditEventController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ActivityFeedService $activityFeedService,
    ) {
    }

    public function index(AuditEventIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AuditLog::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return AuditEventResource::collection(
            $this->activityFeedService->listEvents($context, $request->validated()),
        );
    }

    public function show(string $eventPublicId): AuditEventResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $event = $this->activityFeedService->findEvent($context, $eventPublicId);

        $this->authorize('view', $event);

        return new AuditEventResource($event);
    }
}
