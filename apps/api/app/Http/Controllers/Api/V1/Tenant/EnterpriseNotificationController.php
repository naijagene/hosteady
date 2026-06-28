<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationPreferenceResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\NotificationTemplateResource;
use App\Models\EnterpriseNotification;
use App\Modules\Sdk\Notification\Data\NotificationDigest;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Modules\Sdk\Notification\Data\NotificationPreference;
use App\Modules\Sdk\Notification\Data\NotificationSchedule;
use App\Modules\Sdk\Notification\Data\NotificationTemplate;
use App\Modules\Sdk\Notification\Enums\NotificationScope;
use App\Modules\Sdk\Notification\Exceptions\NotificationNotFoundException;
use App\Services\Enterprise\Notification\NotificationQueryService;
use App\Services\Enterprise\Notification\NotificationService;
use App\Services\Notification\NotificationDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseNotificationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly NotificationDevelopmentService $developmentService,
        private readonly NotificationQueryService $notificationQueryService,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);

        $enterpriseNotifications = $this->developmentService->list($context, $limit);
        $platformNotifications = $this->notificationQueryService->listForMembership(
            $context,
            unreadOnly: true,
            limit: $limit,
        );

        $existingPublicIds = collect($enterpriseNotifications)
            ->map(static fn ($notification) => $notification->publicId)
            ->flip();

        $merged = collect($enterpriseNotifications);

        foreach ($platformNotifications as $platformNotification) {
            if (! $existingPublicIds->has($platformNotification->public_id)) {
                $merged->push($platformNotification);
            }
        }

        $sorted = $merged
            ->sortByDesc(static function ($notification) {
                if ($notification instanceof \App\Modules\Sdk\Notification\Data\NotificationReference) {
                    return $notification->createdAt ?? '';
                }

                return $notification->created_at?->toIso8601String() ?? '';
            })
            ->values()
            ->take($limit);

        return NotificationResource::collection($sorted);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'scope' => ['nullable', 'string', 'in:user,users,role,workspace,organization,broadcast'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'template_key' => ['nullable', 'string', 'max:128'],
            'merge_data' => ['nullable', 'array'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'max:32'],
            'recipient_membership_public_id' => ['nullable', 'string', 'max:128'],
            'recipient_membership_public_ids' => ['nullable', 'array'],
            'recipient_membership_public_ids.*' => ['string', 'max:128'],
            'role_public_id' => ['nullable', 'string', 'max:128'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (($validated['scope'] ?? 'user') === NotificationScope::Broadcast->value) {
            $this->authorize('broadcast', EnterpriseNotification::class);
        }

        $notification = $this->developmentService->store($context, NotificationMessage::fromArray(array_merge([
            'scope' => 'user',
            'priority' => 'normal',
            'channels' => ['in_app'],
            'merge_data' => [],
            'metadata' => [],
        ], $validated)));

        return (new NotificationResource($notification))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $notificationPublicId): NotificationResource
    {
        $this->authorize('view', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        return new NotificationResource(
            $this->developmentService->show($context, $notificationPublicId),
        );
    }

    public function markRead(string $notificationPublicId): NotificationResource
    {
        $this->authorize('update', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        try {
            $notification = $this->developmentService->markRead($context, $notificationPublicId);
        } catch (NotificationNotFoundException) {
            $notification = $this->notificationService->markRead($context, $notificationPublicId);
        }

        return new NotificationResource($notification);
    }

    public function markUnread(string $notificationPublicId): NotificationResource
    {
        $this->authorize('update', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        return new NotificationResource(
            $this->developmentService->markUnread($context, $notificationPublicId),
        );
    }

    public function destroy(string $notificationPublicId): NotificationResource
    {
        $this->authorize('delete', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        return new NotificationResource(
            $this->developmentService->destroy($context, $notificationPublicId),
        );
    }

    public function templates(Request $request): AnonymousResourceCollection
    {
        $this->authorize('templates', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return NotificationTemplateResource::collection(
            $this->developmentService->listTemplates($context, (int) ($validated['limit'] ?? 50)),
        );
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $this->authorize('templates', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'module_key' => ['required', 'string', 'max:64'],
            'type' => ['required', 'string', 'max:128'],
            'template_type' => ['nullable', 'string', 'in:system,module,custom,digest'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'max:32'],
            'variables' => ['nullable', 'array'],
            'scope' => ['nullable', 'string', 'in:user,users,role,workspace,organization,broadcast'],
        ]);

        $template = $this->developmentService->storeTemplate($context, new NotificationTemplate(
            publicId: '',
            moduleKey: $validated['module_key'],
            type: $validated['type'],
            templateType: $validated['template_type'] ?? 'module',
            subject: $validated['subject'] ?? null,
            body: $validated['body'],
            channels: $validated['channels'] ?? [],
            variables: $validated['variables'] ?? [],
            scope: $validated['scope'] ?? 'organization',
        ));

        return (new NotificationTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function preferences(): AnonymousResourceCollection
    {
        $this->authorize('preferences', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        return NotificationPreferenceResource::collection(
            $this->developmentService->showPreferences($context),
        );
    }

    public function updatePreferences(Request $request): NotificationPreferenceResource
    {
        $this->authorize('preferences', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'public_id' => ['nullable', 'string', 'max:128'],
            'channel' => ['required', 'string', 'max:32'],
            'type' => ['required', 'string', 'max:128'],
            'enabled' => ['nullable', 'boolean'],
            'preferred_channels' => ['nullable', 'array'],
            'preferred_channels.*' => ['string', 'max:32'],
            'digest_frequency' => ['nullable', 'string', 'max:32'],
            'quiet_hours' => ['nullable', 'array'],
        ]);

        return new NotificationPreferenceResource(
            $this->developmentService->updatePreferences($context, NotificationPreference::fromArray(array_merge([
                'enabled' => true,
                'preferred_channels' => [],
                'quiet_hours' => [],
            ], $validated))),
        );
    }

    public function digests(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $digests = $this->developmentService->listDigests($context, (int) ($validated['limit'] ?? 50));

        return JsonResource::collection(
            collect($digests)->map(static fn (NotificationDigest $digest): array => $digest->toArray())->values(),
        );
    }

    public function storeDigest(Request $request): JsonResponse
    {
        $this->authorize('manage', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'frequency' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'max:32'],
            'notification_count' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);

        $digest = $this->developmentService->storeDigest($context, NotificationDigest::fromArray($validated));

        return response()->json(['data' => $digest->toArray()], 201);
    }

    public function schedules(): AnonymousResourceCollection
    {
        $this->authorize('manage', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $schedules = $this->developmentService->listSchedules($context);

        return JsonResource::collection(
            collect($schedules)->map(static fn (NotificationSchedule $schedule): array => $schedule->toArray())->values(),
        );
    }

    public function storeSchedule(Request $request): JsonResponse
    {
        $this->authorize('manage', EnterpriseNotification::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'cron_expression' => ['required', 'string', 'max:128'],
            'template_key' => ['nullable', 'string', 'max:128'],
            'status' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
        ]);

        $schedule = $this->developmentService->storeSchedule($context, NotificationSchedule::fromArray($validated));

        return response()->json(['data' => $schedule->toArray()], 201);
    }
}
