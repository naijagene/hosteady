<?php

namespace App\Services\Integration;

use App\Models\IntegrationEventSubscription;
use App\Modules\Sdk\Integration\Contracts\IntegrationEventSubscriber;
use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription as IntegrationEventSubscriptionDto;
use Illuminate\Support\Str;

class IntegrationEventSubscriptionService implements IntegrationEventSubscriber
{
    public function __construct(
        private readonly IntegrationValidationService $validationService,
        private readonly IntegrationAuditRecorder $auditRecorder,
    ) {
    }

    public function listSubscriptions(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        $query = IntegrationEventSubscription::query()
            ->where('status', 'enabled')
            ->orderByDesc('created_at')
            ->limit($limit);

        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (IntegrationEventSubscription $model) => IntegrationMapper::toSubscription($model))->all();
    }

    public function subscribe(
        string $organizationId,
        ?string $workspaceId,
        IntegrationEventSubscriptionDto $subscription,
    ): IntegrationEventSubscriptionDto {
        $this->validationService->validateSubscription($subscription);

        $model = IntegrationEventSubscription::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'module_key' => $subscription->moduleKey,
            'subscription_key' => $subscription->subscriptionKey,
            'event_pattern' => $subscription->eventPattern,
            'endpoint_key' => $subscription->endpointKey,
            'status' => $subscription->status !== '' ? $subscription->status : 'enabled',
            'filters_json' => $subscription->filters,
            'transform_json' => $subscription->transform,
            'retry_policy_json' => $subscription->retryPolicy,
            'metadata' => $subscription->metadata,
        ]);

        $created = IntegrationMapper::toSubscription($model);
        $this->auditRecorder->recordSubscriptionCreated($created);

        return $created;
    }

    /** @return list<IntegrationEventSubscription> */
    public function matching(string $organizationId, ?string $workspaceId, string $eventName): array
    {
        return collect($this->listSubscriptions($organizationId, $workspaceId, 100))
            ->filter(fn (IntegrationEventSubscriptionDto $subscription) => IntegrationMapper::matchesEventPattern(
                $subscription->eventPattern,
                $eventName,
            ))
            ->values()
            ->all();
    }
}
