<?php

namespace App\Services\Integration;

use App\Models\IntegrationActivityLog;
use App\Models\IntegrationConnector as IntegrationConnectorModel;
use App\Models\IntegrationDeadLetter as IntegrationDeadLetterModel;
use App\Models\IntegrationDispatch as IntegrationDispatchModel;
use App\Models\IntegrationEndpoint as IntegrationEndpointModel;
use App\Models\IntegrationEvent as IntegrationEventModel;
use App\Models\IntegrationEventSubscription as IntegrationEventSubscriptionModel;
use App\Models\IntegrationMapping as IntegrationMappingModel;
use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationCredentialReference;
use App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchResult;
use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEvent;
use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
use App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;

class IntegrationMapper
{
    public static function toEvent(IntegrationEventModel $model): IntegrationEvent
    {
        return new IntegrationEvent(
            publicId: $model->public_id,
            eventName: $model->event_name,
            eventVersion: $model->event_version,
            direction: self::enumValue($model->direction, 'internal'),
            sourceType: self::enumValue($model->source_type, 'system'),
            sourceModuleKey: $model->source_module_key,
            sourceEntityKey: $model->source_entity_key,
            sourcePublicId: $model->source_public_id,
            correlationId: $model->correlation_id,
            idempotencyKey: $model->idempotency_key,
            status: self::enumValue($model->status, 'published'),
            payload: is_array($model->payload_json) ? $model->payload_json : [],
            headers: is_array($model->headers_json) ? $model->headers_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
            occurredAt: $model->occurred_at?->toIso8601String(),
            publishedAt: $model->published_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
        );
    }

    public static function toSubscription(IntegrationEventSubscriptionModel $model): IntegrationEventSubscription
    {
        return new IntegrationEventSubscription(
            publicId: $model->public_id,
            subscriptionKey: $model->subscription_key,
            eventPattern: $model->event_pattern,
            endpointKey: $model->endpoint_key,
            status: (string) $model->status,
            moduleKey: $model->module_key,
            filters: is_array($model->filters_json) ? $model->filters_json : [],
            transform: is_array($model->transform_json) ? $model->transform_json : [],
            retryPolicy: is_array($model->retry_policy_json) ? $model->retry_policy_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toConnector(IntegrationConnectorModel $model): IntegrationConnectorDefinition
    {
        return new IntegrationConnectorDefinition(
            publicId: $model->public_id,
            connectorKey: $model->connector_key,
            name: $model->name,
            description: $model->description,
            connectorType: self::enumValue($model->connector_type, 'webhook'),
            authType: self::enumValue($model->auth_type, 'none'),
            status: (string) $model->status,
            moduleKey: $model->module_key,
            config: is_array($model->config_json) ? $model->config_json : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toEndpoint(IntegrationEndpointModel $model, ?string $connectorPublicId = null): IntegrationEndpointDefinition
    {
        return new IntegrationEndpointDefinition(
            publicId: $model->public_id,
            connectorPublicId: $connectorPublicId,
            endpointKey: $model->endpoint_key,
            name: $model->name,
            endpointType: self::enumValue($model->endpoint_type, 'webhook'),
            direction: self::enumValue($model->direction, 'outbound'),
            status: (string) $model->status,
            urlTemplate: $model->url_template,
            method: $model->method,
            headers: is_array($model->headers_json) ? $model->headers_json : [],
            bodyTemplate: is_array($model->body_template_json) ? $model->body_template_json : [],
            authReference: is_array($model->auth_reference) ? $model->auth_reference : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toMapping(IntegrationMappingModel $model): IntegrationMappingDefinition
    {
        return new IntegrationMappingDefinition(
            publicId: $model->public_id,
            mappingKey: $model->mapping_key,
            moduleKey: $model->module_key,
            sourceSchema: is_array($model->source_schema) ? $model->source_schema : [],
            targetSchema: is_array($model->target_schema) ? $model->target_schema : [],
            mapping: is_array($model->mapping_json) ? $model->mapping_json : [],
            transformType: self::enumValue($model->transform_type, 'pass_through'),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toDispatchResult(IntegrationDispatchModel $model): IntegrationDispatchResult
    {
        return new IntegrationDispatchResult(
            publicId: $model->public_id,
            status: self::enumValue($model->status, 'pending'),
            attempt: (int) $model->attempt,
            maxAttempts: (int) $model->max_attempts,
            request: is_array($model->request_json) ? $model->request_json : [],
            response: is_array($model->response_json) ? $model->response_json : [],
            errorMessage: $model->error_message,
            correlationId: is_array($model->metadata) ? ($model->metadata['correlation_id'] ?? null) : null,
            dispatchedAt: $model->dispatched_at?->toIso8601String(),
            completedAt: $model->completed_at?->toIso8601String(),
        );
    }

    public static function toDeadLetter(IntegrationDeadLetterModel $model): IntegrationDeadLetterRecord
    {
        return new IntegrationDeadLetterRecord(
            publicId: $model->public_id,
            status: self::enumValue($model->status, 'open'),
            reason: $model->reason,
            eventPublicId: $model->event?->public_id,
            dispatchPublicId: $model->dispatch?->public_id,
            payload: is_array($model->payload_json) ? $model->payload_json : [],
            errorMessage: $model->error_message,
            metadata: is_array($model->metadata) ? $model->metadata : [],
            createdAt: $model->created_at?->toIso8601String(),
            resolvedAt: $model->resolved_at?->toIso8601String(),
        );
    }

    public static function toCredentialReference(\App\Models\IntegrationCredential $model): IntegrationCredentialReference
    {
        return new IntegrationCredentialReference(
            publicId: $model->public_id,
            connectorKey: $model->connector_key,
            credentialKey: $model->credential_key,
            authType: self::enumValue($model->auth_type, 'none'),
            metadata: is_array($model->metadata) ? $model->metadata : [],
            rotatedAt: $model->rotated_at?->toIso8601String(),
        );
    }

    /**
     * @param  Builder<IntegrationEventModel|IntegrationConnectorModel|IntegrationEndpointModel|IntegrationEventSubscriptionModel|IntegrationMappingModel>  $query
     */
    public static function applyWorkspaceScope(Builder $query, ?string $workspaceId): Builder
    {
        if ($workspaceId === null) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($workspaceId) {
            $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
        });
    }

    public static function applyOrganizationScope(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public static function matchesEventPattern(string $pattern, string $eventName): bool
    {
        $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/';

        return (bool) preg_match($regex, $eventName);
    }

    public static function enumValue(mixed $value, string $default): string
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
