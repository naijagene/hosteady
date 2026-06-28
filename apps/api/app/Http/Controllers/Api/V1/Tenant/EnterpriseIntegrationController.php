<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\IntegrationConnectorResource;
use App\Http\Resources\IntegrationDeadLetterResource;
use App\Http\Resources\IntegrationDispatchResource;
use App\Http\Resources\IntegrationEndpointResource;
use App\Http\Resources\IntegrationEventResource;
use App\Http\Resources\IntegrationHealthResource;
use App\Http\Resources\IntegrationStatisticsResource;
use App\Http\Resources\IntegrationSubscriptionResource;
use App\Models\IntegrationEvent;
use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchRequest;
use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
use App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationReplayRequest;
use App\Services\Integration\IntegrationDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseIntegrationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly IntegrationDevelopmentService $developmentService,
    ) {
    }

    public function indexEvents(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', IntegrationEvent::class);
        $context = app(TenantContext::class);

        return IntegrationEventResource::collection(
            $this->developmentService->listEvents($context, (int) ($request->integer('limit') ?: 50)),
        );
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $this->authorize('publish', IntegrationEvent::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_version' => ['nullable', 'string', 'max:32'],
            'direction' => ['nullable', 'string'],
            'source_type' => ['nullable', 'string'],
            'source_module_key' => ['nullable', 'string', 'max:64'],
            'source_entity_key' => ['nullable', 'string', 'max:128'],
            'source_public_id' => ['nullable', 'string'],
            'correlation_id' => ['nullable', 'string', 'max:128'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
            'headers' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'force_republish' => ['nullable', 'boolean'],
        ]);

        $event = $this->developmentService->publish($context, IntegrationEventEnvelope::fromArray(array_merge([
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
            'headers' => [],
            'metadata' => [],
            'force_republish' => false,
        ], $validated)));

        return (new IntegrationEventResource($event))->response()->setStatusCode(201);
    }

    public function replayEvent(Request $request, string $eventPublicId): IntegrationEventResource
    {
        $this->authorize('replay', IntegrationEvent::class);
        $context = app(TenantContext::class);

        $validated = $request->validate([
            'metadata' => ['nullable', 'array'],
        ]);

        $result = $this->developmentService->replay($context, IntegrationReplayRequest::fromArray([
            'event_public_id' => $eventPublicId,
            'metadata' => $validated['metadata'] ?? [],
        ]));

        return new IntegrationEventResource(
            \App\Modules\Sdk\Integration\Data\IntegrationEvent::fromArray([
                'public_id' => $result->replayEventPublicId,
                'event_name' => 'replay',
                'direction' => 'internal',
                'source_type' => 'platform',
                'status' => $result->status,
                'payload' => [],
                'headers' => [],
                'metadata' => $result->metadata,
            ]),
        );
    }

    public function indexConnectors(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', IntegrationEvent::class);

        return IntegrationConnectorResource::collection(
            $this->developmentService->listConnectors(app(TenantContext::class), (int) ($request->integer('limit') ?: 50)),
        );
    }

    public function storeConnector(Request $request): JsonResponse
    {
        $this->authorize('create', IntegrationEvent::class);
        $validated = $request->validate([
            'connector_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'connector_type' => ['nullable', 'string'],
            'auth_type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'config' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $created = $this->developmentService->createConnector(
            app(TenantContext::class),
            IntegrationConnectorDefinition::fromArray($validated),
        );

        return (new IntegrationConnectorResource($created))->response()->setStatusCode(201);
    }

    public function indexEndpoints(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', IntegrationEvent::class);

        return IntegrationEndpointResource::collection(
            $this->developmentService->listEndpoints(app(TenantContext::class), (int) ($request->integer('limit') ?: 50)),
        );
    }

    public function storeEndpoint(Request $request): JsonResponse
    {
        $this->authorize('create', IntegrationEvent::class);
        $validated = $request->validate([
            'connector_public_id' => ['nullable', 'string'],
            'endpoint_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'endpoint_type' => ['nullable', 'string'],
            'direction' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'url_template' => ['nullable', 'string', 'max:512'],
            'method' => ['nullable', 'string', 'max:16'],
            'headers' => ['nullable', 'array'],
            'body_template' => ['nullable', 'array'],
            'auth_reference' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $created = $this->developmentService->createEndpoint(
            app(TenantContext::class),
            IntegrationEndpointDefinition::fromArray($validated),
        );

        return (new IntegrationEndpointResource($created))->response()->setStatusCode(201);
    }

    public function indexSubscriptions(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', IntegrationEvent::class);

        return IntegrationSubscriptionResource::collection(
            $this->developmentService->listSubscriptions(app(TenantContext::class), (int) ($request->integer('limit') ?: 50)),
        );
    }

    public function storeSubscription(Request $request): JsonResponse
    {
        $this->authorize('create', IntegrationEvent::class);
        $validated = $request->validate([
            'subscription_key' => ['required', 'string', 'max:128'],
            'event_pattern' => ['required', 'string', 'max:255'],
            'endpoint_key' => ['nullable', 'string', 'max:128'],
            'status' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'filters' => ['nullable', 'array'],
            'transform' => ['nullable', 'array'],
            'retry_policy' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $created = $this->developmentService->subscribe(
            app(TenantContext::class),
            IntegrationEventSubscription::fromArray($validated),
        );

        return (new IntegrationSubscriptionResource($created))->response()->setStatusCode(201);
    }

    public function storeDispatch(Request $request): IntegrationDispatchResource
    {
        $this->authorize('dispatch', IntegrationEvent::class);
        $validated = $request->validate([
            'event_public_id' => ['required', 'string'],
            'endpoint_public_id' => ['nullable', 'string'],
            'subscription_key' => ['nullable', 'string', 'max:128'],
            'metadata' => ['nullable', 'array'],
        ]);

        $result = $this->developmentService->dispatch(
            app(TenantContext::class),
            IntegrationDispatchRequest::fromArray($validated),
        );

        return new IntegrationDispatchResource($result);
    }

    public function resolveDeadLetter(string $deadLetterPublicId): IntegrationDeadLetterResource
    {
        $this->authorize('admin', IntegrationEvent::class);

        return new IntegrationDeadLetterResource(
            $this->developmentService->resolveDeadLetter(app(TenantContext::class), $deadLetterPublicId),
        );
    }

    public function health(): IntegrationHealthResource
    {
        $this->authorize('viewAny', IntegrationEvent::class);

        return new IntegrationHealthResource($this->developmentService->health(app(TenantContext::class)));
    }

    public function statistics(): IntegrationStatisticsResource
    {
        $this->authorize('viewAny', IntegrationEvent::class);

        return new IntegrationStatisticsResource($this->developmentService->statistics(app(TenantContext::class)));
    }
}
