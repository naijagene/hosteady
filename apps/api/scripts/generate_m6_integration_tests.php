<?php

/**
 * Generates M6EnterpriseIntegrationEventBusTest.php
 * Run: php scripts/generate_m6_integration_tests.php
 */
declare(strict_types=1);

$base = dirname(__DIR__);
$enums = [
    'IntegrationEventStatus' => ['pending', 'published', 'routed', 'replayed', 'failed', 'dead_lettered'],
    'IntegrationEventDirection' => ['inbound', 'outbound', 'internal'],
    'IntegrationEventSourceType' => ['module', 'workflow', 'rule', 'notification', 'document', 'data', 'integration', 'system'],
    'IntegrationEndpointType' => ['internal_handler', 'outbound_webhook', 'inbound_webhook', 'notification_channel', 'custom'],
    'IntegrationConnectorType' => ['internal', 'webhook', 'email', 'sms', 'custom'],
    'IntegrationAuthType' => ['none', 'shared_secret', 'api_key', 'oauth2', 'hmac_sha256', 'basic'],
    'IntegrationTransformType' => ['pass_through', 'field_mapping', 'template_mapping', 'static_mapping'],
    'IntegrationDeliveryStatus' => ['pending', 'simulating', 'completed', 'failed', 'dead_lettered'],
    'IntegrationRetryStatus' => ['pending', 'scheduled', 'exhausted'],
    'IntegrationDeadLetterStatus' => ['open', 'resolved'],
];

$dtos = [
    'IntegrationEvent', 'IntegrationEventEnvelope', 'IntegrationEventSubscription',
    'IntegrationConnectorDefinition', 'IntegrationEndpointDefinition', 'IntegrationCredentialReference',
    'IntegrationMappingDefinition', 'IntegrationTransformDefinition', 'IntegrationDispatchRequest',
    'IntegrationDispatchResult', 'IntegrationProcessingResult', 'IntegrationReplayRequest',
    'IntegrationReplayResult', 'IntegrationDeadLetterRecord', 'IntegrationStatistics', 'IntegrationHealthReport',
];

$lines = [];
$lines[] = '<?php';
$lines[] = '';
$lines[] = 'namespace Tests\Feature\Services\Integration;';
$lines[] = '';
$lines[] = 'use App\Models\Permission;';
$lines[] = 'use App\Models\Role;';
$lines[] = 'use App\Modules\Sdk\Integration\Contracts\IntegrationEventBus;';
$lines[] = 'use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;';
$lines[] = 'use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;';
$lines[] = 'use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;';
$lines[] = 'use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;';
$lines[] = 'use App\Modules\Sdk\Integration\Data\IntegrationReplayRequest;';
$lines[] = 'use App\Services\Integration\EnterpriseIntegrationEventBusService;';
$lines[] = 'use App\Services\Integration\IntegrationDevelopmentService;';
$lines[] = 'use App\Services\Integration\IntegrationMapper;';
$lines[] = 'use App\Services\Integration\IntegrationMapperService;';
$lines[] = 'use App\Services\Integration\IntegrationWebhookVerifierService;';
$lines[] = 'use App\Services\Module\ModuleDoctorService;';
$lines[] = 'use App\Support\Tenant\TenantContext;';
$lines[] = 'use Illuminate\Foundation\Testing\RefreshDatabase;';
$lines[] = 'use Tests\Support\InteractsWithHeosApi;';
$lines[] = 'use Tests\Support\InteractsWithHeosPlatform;';
$lines[] = 'use Tests\TestCase;';
$lines[] = '';
$lines[] = 'class M6EnterpriseIntegrationEventBusTest extends TestCase';
$lines[] = '{';
$lines[] = '    use InteractsWithHeosApi;';
$lines[] = '    use InteractsWithHeosPlatform;';
$lines[] = '    use RefreshDatabase;';
$lines[] = '';

foreach ($enums as $enum => $cases) {
    $fqcn = "\\App\\Modules\\Sdk\\Integration\\Enums\\$enum";
    $method = 'test_'.strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', preg_replace('/^Integration/', '', $enum))).'_enum_has_expected_cases';
    $lines[] = "    public function {$method}(): void";
    $lines[] = '    {';
    $lines[] = "        \$cases = array_map(static fn ($fqcn \$case) => \$case->value, $fqcn::cases());";
    foreach ($cases as $expected) {
        $lines[] = "        \$this->assertContains('{$expected}', \$cases);";
    }
    $lines[] = '    }';
    $lines[] = '';
}

foreach ($dtos as $dto) {
    $method = 'test_'.strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $dto)).'_dto_roundtrip';
    $fqcn = "\\App\\Modules\\Sdk\\Integration\\Data\\$dto";
    $lines[] = "    public function {$method}(): void";
    $lines[] = '    {';
    $lines[] = "        \$sample = {$fqcn}::fromArray([]);";
    $lines[] = "        \$roundtrip = {$fqcn}::fromArray(\$sample->toArray());";
    $lines[] = '        $this->assertSame($sample->toArray(), $roundtrip->toArray());';
    $lines[] = '    }';
    $lines[] = '';
}

$extraTests = <<<'PHP'
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
        $this->assertSame(111, Permission::query()->count());
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
PHP;

$lines = array_merge($lines, explode("\n", $extraTests));
$content = implode("\n", $lines)."\n";
$path = $base.'/tests/Feature/Services/Integration/M6EnterpriseIntegrationEventBusTest.php';
$dir = dirname($path);
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}
file_put_contents($path, $content);
echo 'Wrote: tests/Feature/Services/Integration/M6EnterpriseIntegrationEventBusTest.php'."\n";
echo 'Test methods: '.substr_count($content, 'public function test_')."\n";
