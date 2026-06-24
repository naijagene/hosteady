<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\AuditEventIndexRequest;
use App\Http\Requests\Audit\AuditSummaryRequest;
use App\Http\Resources\AuditEventResource;
use App\Http\Resources\AuditFeedSummaryResource;
use App\Models\AuditLog;
use App\Services\Audit\ActivityFeedService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class AuditEventController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ActivityFeedService $activityFeedService,
    ) {
    }

    public function index(AuditEventIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $page = $this->activityFeedService->listEvents($context, $request->validated());

        if ($page->usesOffsetPagination && $page->offsetPaginator !== null) {
            return AuditEventResource::collection($page->offsetPaginator)
                ->response();
        }

        return response()->json([
            'data' => AuditEventResource::collection($page->items),
            'meta' => [
                'path' => $request->path(),
                'per_page' => $page->perPage,
                'next_cursor' => $page->nextCursor,
                'prev_cursor' => $page->prevCursor,
                'has_more' => $page->hasMore,
            ],
        ]);
    }

    public function show(string $eventPublicId): AuditEventResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $event = $this->activityFeedService->findEvent($context, $eventPublicId);

        $this->authorize('view', $event);

        return new AuditEventResource($event);
    }

    public function summary(AuditSummaryRequest $request): AuditFeedSummaryResource
    {
        $this->authorize('viewAny', AuditLog::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return new AuditFeedSummaryResource(
            $this->activityFeedService->summarize($context, $request->validated()),
        );
    }
}
