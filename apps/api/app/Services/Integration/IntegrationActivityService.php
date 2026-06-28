<?php

namespace App\Services\Integration;

use App\Models\IntegrationActivityLog;
use App\Models\IntegrationConnector;
use App\Models\IntegrationEndpoint;
use App\Models\IntegrationEvent;
use Illuminate\Support\Str;

class IntegrationActivityService
{
    public function logEvent(IntegrationEvent $model, string $action, ?array $before = null, ?array $after = null): void
    {
        $this->create([
            'organization_id' => $model->organization_id,
            'workspace_id' => $model->workspace_id,
            'integration_event_id' => $model->id,
            'action' => 'event.'.$action,
            'before_state' => $before,
            'after_state' => $after,
        ]);
    }

    public function logConnector(IntegrationConnector $model, string $action, ?array $before = null, ?array $after = null): void
    {
        $this->create([
            'organization_id' => $model->organization_id,
            'workspace_id' => $model->workspace_id,
            'integration_connector_id' => $model->id,
            'action' => 'connector.'.$action,
            'before_state' => $before,
            'after_state' => $after,
        ]);
    }

    public function logEndpoint(IntegrationEndpoint $model, string $action, ?array $before = null, ?array $after = null): void
    {
        $this->create([
            'organization_id' => $model->organization_id,
            'workspace_id' => $model->workspace_id,
            'integration_endpoint_id' => $model->id,
            'action' => 'endpoint.'.$action,
            'before_state' => $before,
            'after_state' => $after,
        ]);
    }

    private function create(array $attributes): void
    {
        $context = app()->bound(\App\Support\Tenant\TenantContext::class)
            ? app(\App\Support\Tenant\TenantContext::class)
            : null;

        IntegrationActivityLog::query()->create(array_merge([
            'id' => (string) Str::uuid7(),
            'actor_user_id' => $context?->user->id,
            'actor_membership_id' => $context?->membership->id,
            'created_at' => now(),
        ], $attributes));
    }
}
