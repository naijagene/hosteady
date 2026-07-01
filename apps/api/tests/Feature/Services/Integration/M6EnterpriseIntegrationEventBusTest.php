<?php

namespace Tests\Feature\Services\Integration;

use App\Models\Permission;
use App\Models\Role;
use App\Modules\Sdk\Integration\Contracts\IntegrationEventBus;
use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
use App\Modules\Sdk\Integration\Data\IntegrationReplayRequest;
use App\Services\Integration\EnterpriseIntegrationEventBusService;
use App\Services\Integration\IntegrationDevelopmentService;
use App\Services\Integration\IntegrationMapper;
use App\Services\Integration\IntegrationMapperService;
use App\Services\Integration\IntegrationWebhookVerifierService;
use App\Services\Module\ModuleDoctorService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M6EnterpriseIntegrationEventBusTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_event_status_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationEventStatus $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationEventStatus::cases());
        $this->assertContains('pending', $cases);
        $this->assertContains('published', $cases);
        $this->assertContains('routed', $cases);
        $this->assertContains('replayed', $cases);
        $this->assertContains('failed', $cases);
        $this->assertContains('dead_lettered', $cases);
    }

    public function test_event_direction_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationEventDirection $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationEventDirection::cases());
        $this->assertContains('inbound', $cases);
        $this->assertContains('outbound', $cases);
        $this->assertContains('internal', $cases);
    }

    public function test_event_source_type_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationEventSourceType $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationEventSourceType::cases());
        $this->assertContains('module', $cases);
        $this->assertContains('workflow', $cases);
        $this->assertContains('rule', $cases);
        $this->assertContains('notification', $cases);
        $this->assertContains('document', $cases);
        $this->assertContains('data', $cases);
        $this->assertContains('integration', $cases);
        $this->assertContains('system', $cases);
    }

    public function test_endpoint_type_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationEndpointType $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationEndpointType::cases());
        $this->assertContains('internal_handler', $cases);
        $this->assertContains('outbound_webhook', $cases);
        $this->assertContains('inbound_webhook', $cases);
        $this->assertContains('notification_channel', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_connector_type_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationConnectorType $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationConnectorType::cases());
        $this->assertContains('internal', $cases);
        $this->assertContains('webhook', $cases);
        $this->assertContains('email', $cases);
        $this->assertContains('sms', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_auth_type_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationAuthType $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationAuthType::cases());
        $this->assertContains('none', $cases);
        $this->assertContains('shared_secret', $cases);
        $this->assertContains('api_key', $cases);
        $this->assertContains('oauth2', $cases);
        $this->assertContains('hmac_sha256', $cases);
        $this->assertContains('basic', $cases);
    }

    public function test_transform_type_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationTransformType $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationTransformType::cases());
        $this->assertContains('pass_through', $cases);
        $this->assertContains('field_mapping', $cases);
        $this->assertContains('template_mapping', $cases);
        $this->assertContains('static_mapping', $cases);
    }

    public function test_delivery_status_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationDeliveryStatus $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationDeliveryStatus::cases());
        $this->assertContains('pending', $cases);
        $this->assertContains('simulating', $cases);
        $this->assertContains('completed', $cases);
        $this->assertContains('failed', $cases);
        $this->assertContains('dead_lettered', $cases);
    }

    public function test_retry_status_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationRetryStatus $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationRetryStatus::cases());
        $this->assertContains('pending', $cases);
        $this->assertContains('scheduled', $cases);
        $this->assertContains('exhausted', $cases);
    }

    public function test_dead_letter_status_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Integration\Enums\IntegrationDeadLetterStatus $case) => $case->value, \App\Modules\Sdk\Integration\Enums\IntegrationDeadLetterStatus::cases());
        $this->assertContains('open', $cases);
        $this->assertContains('resolved', $cases);
    }

    public function test_integration_event_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationEvent::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationEvent::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_event_envelope_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_event_subscription_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationEventSubscription::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationEventSubscription::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_connector_definition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_endpoint_definition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_credential_reference_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationCredentialReference::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationCredentialReference::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_mapping_definition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_transform_definition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationTransformDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationTransformDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_dispatch_request_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationDispatchRequest::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationDispatchRequest::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_dispatch_result_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationDispatchResult::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationDispatchResult::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_processing_result_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationProcessingResult::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationProcessingResult::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_replay_request_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationReplayRequest::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationReplayRequest::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_replay_result_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationReplayResult::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationReplayResult::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_dead_letter_record_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_statistics_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationStatistics::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationStatistics::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_health_report_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Integration\Data\IntegrationHealthReport::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Integration\Data\IntegrationHealthReport::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_integration_event_envelope_supports_camel_case_keys(): void
    {
        $envelope = IntegrationEventEnvelope::fromArray([
            'eventName' => 'demo.created',
            'direction' => 'internal',
            'sourceType' => 'platform',
            'payload' => ['id' => 1],
        ]);

        $this->assertSame('demo.created', $envelope->eventName);
    }

    public function test_integration_event_bus_contract_bound(): void
    {
        $this->assertInstanceOf(EnterpriseIntegrationEventBusService::class, app(IntegrationEventBus::class));
    }

    public function test_integrations_config_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.integrations.enabled', true));
    }

    public function test_permission_catalog_has_integration_permissions(): void
    {
        $this->seedHeosPlatform();
        $this->assertSame(139, Permission::query()->count());
        foreach (['integrations.read', 'integrations.manage', 'integrations.publish', 'integrations.dispatch', 'integrations.replay', 'integrations.admin'] as $key) {
            $this->assertNotNull(Permission::query()->where('key', $key)->first());
        }
    }

    public function test_module_doctor_includes_integrations(): void
    {
        $this->seedHeosPlatform();
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('integrations', $report->platformSummary['enterprise']);
    }

    public function test_publish_event_persists_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $event = app(IntegrationDevelopmentService::class)->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'demo.record.created',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => ['name' => 'Ada'],
        ]));
        $this->assertSame('demo.record.created', $event->eventName);
        $this->assertNotEmpty($event->publicId);
    }

    public function test_publish_event_is_idempotent(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $envelope = IntegrationEventEnvelope::fromArray([
            'event_name' => 'demo.idempotent',
            'direction' => 'internal',
            'source_type' => 'platform',
            'idempotency_key' => 'idem-001',
            'payload' => [],
        ]);
        $first = $service->publish($context, $envelope);
        $second = $service->publish($context, $envelope);
        $this->assertSame($first->publicId, $second->publicId);
    }

    public function test_list_events_returns_published_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $published = $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'demo.listed',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        $events = $service->listEvents($context);
        $this->assertTrue(collect($events)->contains(fn ($event) => $event->publicId === $published->publicId));
    }

    public function test_create_connector(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $connector = app(IntegrationDevelopmentService::class)->createConnector($context, IntegrationConnectorDefinition::fromArray([
            'connector_key' => 'demo_webhook',
            'name' => 'Demo Webhook',
            'connector_type' => 'webhook',
            'auth_type' => 'none',
        ]));
        $this->assertSame('demo_webhook', $connector->connectorKey);
    }

    public function test_create_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $connector = $service->createConnector($context, IntegrationConnectorDefinition::fromArray([
            'connector_key' => 'demo_hook',
            'name' => 'Demo Hook',
            'connector_type' => 'webhook',
        ]));
        $endpoint = $service->createEndpoint($context, IntegrationEndpointDefinition::fromArray([
            'connector_public_id' => $connector->publicId,
            'endpoint_key' => 'notify',
            'name' => 'Notify Endpoint',
            'endpoint_type' => 'webhook',
            'url_template' => 'https://example.test/hook',
        ]));
        $this->assertSame('notify', $endpoint->endpointKey);
    }

    public function test_subscribe_and_process_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $service->createEndpoint($context, IntegrationEndpointDefinition::fromArray([
            'endpoint_key' => 'demo_endpoint',
            'name' => 'Demo Endpoint',
            'endpoint_type' => 'webhook',
            'url_template' => 'https://example.test/events',
        ]));
        $service->subscribe($context, IntegrationEventSubscription::fromArray([
            'subscription_key' => 'demo_sub',
            'event_pattern' => 'demo.*',
            'endpoint_key' => 'demo_endpoint',
        ]));
        $event = $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'demo.processed',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => ['ok' => true],
        ]));
        $stats = $service->statistics($context);
        $this->assertGreaterThanOrEqual(1, $stats->events);
        $this->assertGreaterThanOrEqual(1, $stats->dispatches);
        $this->assertSame('demo.processed', $event->eventName);
    }

    public function test_replay_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $original = $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'demo.replay',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => ['value' => 1],
        ]));
        $result = $service->replay($context, IntegrationReplayRequest::fromArray([
            'event_public_id' => $original->publicId,
            'metadata' => ['reason' => 'test'],
        ]));
        $this->assertSame($original->publicId, $result->eventPublicId);
        $this->assertNotSame($original->publicId, $result->replayEventPublicId);
    }

    public function test_statistics_after_publish(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'demo.stats',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        $this->assertGreaterThanOrEqual(1, $service->statistics($context)->events);
    }

    public function test_health_report_enabled(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $report = app(IntegrationDevelopmentService::class)->health($context);
        $this->assertTrue($report->enabled);
        $this->assertTrue($report->healthy);
    }

    public function test_mapper_matches_wildcard_pattern(): void
    {
        $this->assertTrue(IntegrationMapper::matchesEventPattern('demo.*', 'demo.created'));
        $this->assertFalse(IntegrationMapper::matchesEventPattern('demo.*', 'other.created'));
    }

    public function test_mapper_service_field_mapping(): void
    {
        $mapper = app(IntegrationMapperService::class);
        $mapped = $mapper->map(['first_name' => 'Ada'], ['name' => 'first_name'], 'field_mapping');
        $this->assertSame('Ada', $mapped['name']);
    }

    public function test_webhook_verifier_none_auth(): void
    {
        $verifier = app(IntegrationWebhookVerifierService::class);
        $this->assertTrue($verifier->verify('none', [], '{}', []));
    }

    public function test_webhook_verifier_shared_secret(): void
    {
        $verifier = app(IntegrationWebhookVerifierService::class);
        $this->assertTrue($verifier->verify('shared_secret', ['x-heos-secret' => 'abc'], '{}', ['secret' => 'abc']));
        $this->assertFalse($verifier->verify('shared_secret', ['x-heos-secret' => 'wrong'], '{}', ['secret' => 'abc']));
    }

    public function test_viewer_cannot_publish_events(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        app()->instance(TenantContext::class, $viewer);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(IntegrationDevelopmentService::class)->publish($viewer, IntegrationEventEnvelope::fromArray([
            'event_name' => 'denied',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
    }

    public function test_member_can_read_integrations(): void
    {
        $owner = $this->tenantContext();
        $member = $this->memberContext($owner);
        app()->instance(TenantContext::class, $member);
        $this->assertTrue(app(\App\Services\Integration\IntegrationPermissionService::class)->canRead($member));
    }

    public function test_entity_bridge_does_not_throw_without_context(): void
    {
        $bridge = app(\App\Services\Integration\IntegrationEntityBridge::class);
        $bridge->publishRecordEventBestEffort(
            $this->tenantContext(),
            'created',
            new \App\Modules\Sdk\DataRepository\Data\EntityRecordReference(
                publicId: '01900000-0000-7000-8000-000000000901',
                moduleKey: 'demo.core',
                entityKey: 'customer',
                status: 'active',
            ),
        );
        $this->assertTrue(true);
    }

    public function test_rules_bridge_emit_event_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(\App\Services\Integration\IntegrationRulesBridge::class)->emitEventBestEffort(
            $context,
            'rule.demo.triggered',
            ['flag' => 'yes'],
            'demo.core',
        );
        $events = app(IntegrationDevelopmentService::class)->listEvents($context);
        $this->assertNotEmpty($events);
    }

    public function test_event_bus_bridge_does_not_break_platform_bus(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(\App\Services\Integration\IntegrationEventBusBridge::class)->forwardToPlatformEventBusBestEffort(
            $context,
            \App\Modules\Sdk\Integration\Data\IntegrationEvent::fromArray([
                'public_id' => '01900000-0000-7000-8000-000000000902',
                'event_name' => 'integration.demo',
                'direction' => 'internal',
                'source_type' => 'platform',
                'status' => 'published',
                'payload' => [],
                'headers' => [],
                'metadata' => [],
            ]),
        );
        $this->assertTrue(true);
    }

    public function test_correlation_id_preserved_in_dispatch(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $service->createEndpoint($context, IntegrationEndpointDefinition::fromArray([
            'endpoint_key' => 'corr_endpoint',
            'name' => 'Correlation Endpoint',
            'endpoint_type' => 'outbound_webhook',
        ]));
        $service->subscribe($context, IntegrationEventSubscription::fromArray([
            'subscription_key' => 'corr_sub',
            'event_pattern' => 'corr.*',
            'endpoint_key' => 'corr_endpoint',
        ]));
        $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'corr.test',
            'direction' => 'internal',
            'source_type' => 'platform',
            'correlation_id' => 'corr-abc-123',
            'payload' => [],
        ]));
        $dispatch = \App\Models\IntegrationDispatch::query()->latest('created_at')->firstOrFail();
        $this->assertSame('corr-abc-123', $dispatch->metadata['correlation_id'] ?? null);
    }

    public function test_exact_subscription_does_not_match_other_events(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $service->subscribe($context, IntegrationEventSubscription::fromArray([
            'subscription_key' => 'exact_sub',
            'event_pattern' => 'exact.match',
        ]));
        $before = $service->statistics($context)->dispatches;
        $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'exact.other',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        $this->assertSame($before, $service->statistics($context)->dispatches);
    }

    public function test_wildcard_subscription_routes_document_events(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $service->subscribe($context, IntegrationEventSubscription::fromArray([
            'subscription_key' => 'doc_sub',
            'event_pattern' => 'document.*',
        ]));
        $before = $service->statistics($context)->dispatches;
        $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'document.uploaded',
            'direction' => 'internal',
            'source_type' => 'document',
            'payload' => [],
        ]));
        $this->assertGreaterThan($before, $service->statistics($context)->dispatches);
    }

    public function test_wildcard_subscription_routes_workflow_events(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $service->subscribe($context, IntegrationEventSubscription::fromArray([
            'subscription_key' => 'wf_sub',
            'event_pattern' => 'workflow.*',
        ]));
        $before = $service->statistics($context)->dispatches;
        $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'workflow.completed',
            'direction' => 'internal',
            'source_type' => 'workflow',
            'payload' => [],
        ]));
        $this->assertGreaterThan($before, $service->statistics($context)->dispatches);
    }

    public function test_dispatch_produces_simulated_response(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $event = $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'dispatch.sim',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => ['x' => 1],
        ]));
        $result = $service->dispatch($context, \App\Modules\Sdk\Integration\Data\IntegrationDispatchRequest::fromArray([
            'event_public_id' => $event->publicId,
        ]));
        $this->assertTrue($result->response['simulated'] ?? false);
        $this->assertSame('accepted', $result->response['status'] ?? null);
    }

    public function test_dead_letter_created_after_max_attempts(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(IntegrationDevelopmentService::class);
        $event = $service->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'dead.letter',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        $model = \App\Models\IntegrationEvent::query()->where('public_id', $event->publicId)->firstOrFail();
        $dispatch = app(\App\Services\Integration\IntegrationDispatchService::class)->createPending($model, null, 'dl_sub', ['max_attempts' => 1]);
        app(\App\Services\Integration\IntegrationDispatchService::class)->fail($dispatch->fresh(), 'simulated failure');
        $this->assertTrue(\App\Models\IntegrationDeadLetter::query()->where('organization_id', $context->organization->id)->exists());
    }

    public function test_dead_letter_resolve(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $record = app(\App\Services\Integration\IntegrationDeadLetterService::class)->enqueue(
            $context->organization->id,
            $context->workspace->id,
            ['reason' => 'test', 'payload' => ['a' => 1]],
        );
        $resolved = app(IntegrationDevelopmentService::class)->resolveDeadLetter($context, $record->publicId);
        $this->assertSame('resolved', $resolved->status);
    }

    public function test_credential_create_and_rotate(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $credentialService = app(\App\Services\Integration\IntegrationCredentialService::class);
        $reference = $credentialService->store(
            $context->organization->id,
            $context->workspace->id,
            \App\Modules\Sdk\Integration\Data\IntegrationCredentialReference::fromArray([
                'connector_key' => 'demo',
                'credential_key' => 'api_key',
                'auth_type' => 'api_key',
            ]),
            ['token' => 'secret-one'],
        );
        $rotated = $credentialService->rotate(
            $context->organization->id,
            $context->workspace->id,
            'api_key',
            ['token' => 'secret-two'],
        );
        $this->assertNotEmpty($reference->publicId);
        $this->assertNotNull($rotated->rotatedAt);
    }

    public function test_credential_reference_never_exposes_encrypted_payload(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $reference = app(\App\Services\Integration\IntegrationCredentialService::class)->store(
            $context->organization->id,
            $context->workspace->id,
            \App\Modules\Sdk\Integration\Data\IntegrationCredentialReference::fromArray([
                'connector_key' => 'demo',
                'credential_key' => 'secret_ref',
                'auth_type' => 'shared_secret',
            ]),
            ['secret' => 'hidden'],
        );
        $array = $reference->toArray();
        $this->assertArrayNotHasKey('encrypted_payload', $array);
        $this->assertArrayNotHasKey('encryptedPayload', $array);
    }

    public function test_create_mapping(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $mapping = app(IntegrationDevelopmentService::class)->createMapping($context, \App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition::fromArray([
            'mapping_key' => 'customer_map',
            'transform_type' => 'field_mapping',
            'mapping' => ['name' => 'full_name'],
        ]));
        $this->assertSame('customer_map', $mapping->mappingKey);
    }

    public function test_transform_pass_through(): void
    {
        $transformer = app(\App\Services\Integration\IntegrationTransformerService::class);
        $payload = ['a' => 1];
        $this->assertSame($payload, $transformer->transform($payload, 'pass_through', []));
    }

    public function test_transform_static_mapping(): void
    {
        $transformer = app(\App\Services\Integration\IntegrationTransformerService::class);
        $result = $transformer->transform(['a' => 1], 'static_mapping', ['values' => ['b' => 2]]);
        $this->assertSame(2, $result['b']);
        $this->assertSame(1, $result['a']);
    }

    public function test_transform_template_mapping(): void
    {
        $transformer = app(\App\Services\Integration\IntegrationTransformerService::class);
        $result = $transformer->transform(['name' => 'Ada'], 'template_mapping', [
            'template' => ['type' => 'customer', 'name' => 'Unknown'],
        ]);
        $this->assertSame('Ada', $result['name']);
        $this->assertSame('customer', $result['type']);
    }

    public function test_retry_policy_schedules_next_attempt(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $event = app(IntegrationDevelopmentService::class)->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'retry.test',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        $model = \App\Models\IntegrationEvent::query()->where('public_id', $event->publicId)->firstOrFail();
        $dispatch = app(\App\Services\Integration\IntegrationDispatchService::class)->createPending($model, null, 'retry_sub', ['max_attempts' => 3]);
        $retried = app(\App\Services\Integration\IntegrationRetryPolicyService::class)->scheduleRetry($dispatch->fresh());
        $this->assertNotNull($retried->next_retry_at);
        $this->assertSame(1, (int) $retried->attempt);
    }

    public function test_webhook_verifier_hmac_sha256(): void
    {
        $verifier = app(IntegrationWebhookVerifierService::class);
        $payload = '{"ok":true}';
        $secret = 'test-secret';
        $signature = hash_hmac('sha256', $payload, $secret);
        $this->assertTrue($verifier->verify('hmac_sha256', ['x-heos-signature' => $signature], $payload, ['secret' => $secret]));
        $this->assertFalse($verifier->verify('hmac_sha256', ['x-heos-signature' => 'bad'], $payload, ['secret' => $secret]));
    }

    public function test_notification_bridge_publishes_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(\App\Services\Integration\IntegrationNotificationBridge::class)->publishNotificationEventBestEffort(
            $context,
            'notification.sent',
            \App\Modules\Sdk\Notification\Data\NotificationReference::fromArray([
                'public_id' => '01900000-0000-7000-8000-000000000903',
                'title' => 'Hello',
                'status' => 'sent',
                'channels' => ['in_app'],
            ]),
        );
        $this->assertTrue(collect(app(IntegrationDevelopmentService::class)->listEvents($context))->contains(
            fn ($event) => $event->eventName === 'notification.sent',
        ));
    }

    public function test_document_bridge_publishes_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(\App\Services\Integration\IntegrationDocumentBridge::class)->publishDocumentEventBestEffort(
            $context,
            'document.uploaded',
            '01900000-0000-7000-8000-000000000904',
            ['filename' => 'demo.pdf'],
        );
        $this->assertTrue(collect(app(IntegrationDevelopmentService::class)->listEvents($context))->contains(
            fn ($event) => $event->eventName === 'document.uploaded',
        ));
    }

    public function test_workflow_bridge_does_not_throw(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(\App\Services\Integration\IntegrationWorkflowBridge::class)->publishWorkflowEventBestEffort(
            $context,
            'workflow.completed',
            '01900000-0000-7000-8000-000000000905',
            ['status' => 'completed'],
        );
        $this->assertTrue(collect(app(IntegrationDevelopmentService::class)->listEvents($context))->contains(
            fn ($event) => $event->eventName === 'workflow.completed',
        ));
    }

    public function test_data_repository_bridge_publishes_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(\App\Services\Integration\IntegrationDataRepositoryBridge::class)->publishRecordEventBestEffort(
            $context,
            'created',
            new \App\Modules\Sdk\DataRepository\Data\EntityRecordReference(
                publicId: '01900000-0000-7000-8000-000000000906',
                moduleKey: 'demo.core',
                entityKey: 'customer',
                status: 'active',
            ),
        );
        $this->assertTrue(collect(app(IntegrationDevelopmentService::class)->listEvents($context))->contains(
            fn ($event) => $event->eventName === 'data.record.created',
        ));
    }

    public function test_runtime_metadata_includes_integrations(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $runtime = app(\App\Services\WorkspaceApplication\WorkspaceRuntimeResolver::class)->resolve($context);
        $this->assertArrayHasKey('integrations', $runtime->runtimeMetadata['enterprise'] ?? []);
        $this->assertTrue($runtime->capabilities['integrations'] ?? false);
    }

    public function test_doctor_health_with_migrated_tables(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $report = app(\App\Services\Integration\IntegrationHealthService::class)->health($context);
        $this->assertTrue($report->enabled);
        $this->assertTrue($report->healthy);
        $this->assertSame([], $report->missingTables);
    }

    public function test_search_indexer_does_not_throw(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $event = app(IntegrationDevelopmentService::class)->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'search.index',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        app(\App\Services\Integration\IntegrationSearchIndexer::class)->indexEventBestEffort(
            $event,
            new \App\Modules\Sdk\Enterprise\Data\EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
            ),
        );
        $this->assertTrue(true);
    }

    public function test_audit_event_published_on_publish(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(IntegrationDevelopmentService::class)->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'audit.test',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        $this->assertTrue(\App\Models\AuditLog::query()->where('action', \App\Enums\AuditAction::IntegrationEventPublished->value)->exists());
    }

    public function test_integration_origin_prevents_recursive_republish(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->expectException(\App\Modules\Sdk\Integration\Exceptions\IntegrationEventException::class);
        app(IntegrationDevelopmentService::class)->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'loop.test',
            'direction' => 'internal',
            'source_type' => 'platform',
            'metadata' => ['integration_origin' => true],
            'payload' => [],
        ]));
    }

    public function test_platform_event_bridge_skips_integration_events(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $before = app(IntegrationDevelopmentService::class)->statistics($context)->events;
        app(\App\Services\Integration\IntegrationEventBusBridge::class)->publishFromPlatformEventBestEffort(
            $context,
            new \App\Modules\Sdk\Enterprise\Data\PlatformEventRequest(
                scope: new \App\Modules\Sdk\Enterprise\Data\EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                ),
                eventName: 'platform.skip',
                payload: ['integration_event_public_id' => '01900000-0000-7000-8000-000000000907'],
            ),
        );
        $this->assertSame($before, app(IntegrationDevelopmentService::class)->statistics($context)->events);
    }

    public function test_api_list_events(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(IntegrationDevelopmentService::class)->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'api.list',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/integrations/events')
            ->assertOk();
    }

    public function test_api_post_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/integrations/events', [
                'event_name' => 'api.created',
                'payload' => ['ok' => true],
            ])
            ->assertCreated()
            ->assertJsonPath('data.event_name', 'api.created');
    }

    public function test_api_replay_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $event = app(IntegrationDevelopmentService::class)->publish($context, IntegrationEventEnvelope::fromArray([
            'event_name' => 'api.replay',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/integrations/events/'.$event->publicId.'/replay', [])
            ->assertOk();
    }

    public function test_api_list_connectors_and_create_connector(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/integrations/connectors', [
                'connector_key' => 'api_hook',
                'name' => 'API Hook',
                'connector_type' => 'webhook',
            ])
            ->assertCreated();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/integrations/connectors')
            ->assertOk();
    }

    public function test_api_create_endpoint_and_subscription(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/integrations/endpoints', [
                'endpoint_key' => 'api_endpoint',
                'name' => 'API Endpoint',
                'endpoint_type' => 'outbound_webhook',
            ])
            ->assertCreated();
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/integrations/subscriptions', [
                'subscription_key' => 'api_sub',
                'event_pattern' => 'api.*',
                'endpoint_key' => 'api_endpoint',
            ])
            ->assertCreated();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/integrations/subscriptions')
            ->assertOk();
    }

    public function test_api_statistics_and_health(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/integrations/statistics')
            ->assertOk()
            ->assertJsonStructure(['data' => ['events', 'connectors', 'dispatches']]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/integrations/health')
            ->assertOk()
            ->assertJsonPath('data.enabled', true);
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/integrations/events');
        $response->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_api_viewer_cannot_publish(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        app()->instance(TenantContext::class, $viewer);
        $this->withHeaders($this->tenantHeaders($viewer))
            ->postJson('/api/v1/tenant/integrations/events', [
                'event_name' => 'denied',
            ])
            ->assertForbidden();
    }

    public function test_tenant_isolation_for_events(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        app(IntegrationDevelopmentService::class)->publish($contextA, IntegrationEventEnvelope::fromArray([
            'event_name' => 'tenant.a',
            'direction' => 'internal',
            'source_type' => 'platform',
            'payload' => [],
        ]));
        app()->instance(TenantContext::class, $contextB);
        $eventsB = app(IntegrationDevelopmentService::class)->listEvents($contextB);
        $this->assertFalse(collect($eventsB)->contains(fn ($event) => $event->eventName === 'tenant.a'));
    }

    public function test_manager_can_manage_connectors(): void
    {
        $owner = $this->tenantContext();
        $manager = $this->roleContext($owner, 'manager');
        app()->instance(TenantContext::class, $manager);
        $this->assertTrue(app(\App\Services\Integration\IntegrationPermissionService::class)->canManage($manager));
        $this->assertTrue(app(\App\Services\Integration\IntegrationPermissionService::class)->canReplay($manager));
        $this->assertFalse(app(\App\Services\Integration\IntegrationPermissionService::class)->canAdmin($manager));
    }

    public function test_static_integration_routes_resolve(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/integrations/health')
            ->assertOk();
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/integrations/statistics')
            ->assertOk();
    }

    /**
     * @return array<string, string>
     */
    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'enterprise-integrations-'.uniqid()]);
        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'member');
    }

    private function viewerContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'viewer');
    }

    private function roleContext(TenantContext $ownerContext, string $roleKey): TenantContext
    {
        $user = $this->createActiveUser();
        $role = Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', $roleKey)
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $user,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }
}
