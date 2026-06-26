<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlatformNotificationResource;
use App\Models\PlatformNotification;
use App\Services\Enterprise\Notification\NotificationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PlatformNotificationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', PlatformNotification::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return PlatformNotificationResource::collection(
            $this->notificationService->listUnread($context, limit: 50),
        );
    }

    public function markRead(string $notificationPublicId): PlatformNotificationResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $notification = $this->notificationService->markRead($context, $notificationPublicId);

        $this->authorize('update', $notification);

        return new PlatformNotificationResource($notification);
    }
}
