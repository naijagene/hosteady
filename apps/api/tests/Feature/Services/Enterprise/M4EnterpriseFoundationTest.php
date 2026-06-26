<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PlatformEvent;
use App\Models\PlatformNotification;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\NotificationRequest;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Enterprise\Data\ReferenceCatalogData;
use App\Modules\Sdk\Enterprise\Data\ReferenceItemData;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Services\Enterprise\Notification\NotificationService;
use App\Services\Enterprise\ReferenceData\ReferenceDataService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4EnterpriseFoundationTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_entity_reference_serializes_to_array(): void
    {
        $reference = new EntityReference(
            type: 'workspace_application',
            publicId: '01900000-0000-7000-8000-000000000099',
            moduleKey: 'demo',
            label: 'Demo App',
        );

        $this->assertSame([
            'type' => 'workspace_application',
            'public_id' => '01900000-0000-7000-8000-000000000099',
            'module_key' => 'demo',
            'label' => 'Demo App',
        ], $reference->toArray());
    }

    public function test_entity_reference_deserializes_from_array(): void
    {
        $reference = EntityReference::fromArray([
            'type' => 'notification',
            'public_id' => 'abc',
            'module_key' => 'core',
        ]);

        $this->assertSame('notification', $reference->type);
        $this->assertSame('abc', $reference->publicId);
        $this->assertSame('core', $reference->moduleKey);
    }

    public function test_event_bus_dispatches_sync_event(): void
    {
        $context = $this->tenantContext();

        $result = app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            eventName: 'platform.test.event',
            payload: ['message' => 'hello'],
            subject: new EntityReference('demo', 'demo-subject', 'demo'),
        ));

        $this->assertNotEmpty($result->eventPublicId);
        $this->assertSame('processed', $result->status);
        $this->assertFalse($result->async);
        $this->assertTrue(PlatformEvent::query()->where('public_id', $result->eventPublicId)->exists());
    }

    public function test_event_bus_records_audit_event(): void
    {
        $context = $this->tenantContext();

        app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            eventName: 'platform.test.audit',
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::PlatformEventDispatched->value)->exists());
    }

    public function test_notification_service_delivers_in_app_notification(): void
    {
        $context = $this->tenantContext();

        $result = app(NotificationService::class)->notify($context, new NotificationRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            recipientMembershipPublicId: $context->membershipPublicId,
            type: 'demo.welcome',
            title: 'Welcome',
            body: 'Demo notification',
            subject: new EntityReference('membership', $context->membershipPublicId, 'demo'),
        ));

        $this->assertSame('delivered', $result->status);
        $this->assertContains('in_app', $result->deliveredChannels);
        $this->assertTrue(PlatformNotification::query()->where('public_id', $result->notificationPublicId)->exists());
    }

    public function test_notification_mark_read_sets_timestamp(): void
    {
        $context = $this->tenantContext();
        $service = app(NotificationService::class);

        $result = $service->notify($context, new NotificationRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            recipientMembershipPublicId: $context->membershipPublicId,
            type: 'demo.read',
            title: 'Read me',
            body: 'Body',
        ));

        $notification = $service->markRead($context, $result->notificationPublicId);

        $this->assertNotNull($notification->read_at);
    }

    public function test_reference_data_lists_currency_catalog(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $context = $this->tenantContext();

        $items = app(ReferenceDataService::class)->listItems($context, 'currencies');

        $this->assertNotEmpty($items);
        $this->assertSame('USD', $items[0]->code);
    }

    public function test_reference_data_finds_country_item(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $context = $this->tenantContext();

        $item = app(ReferenceDataService::class)->findItem($context, 'countries', 'NG');

        $this->assertNotNull($item);
        $this->assertSame('Nigeria', $item->label);
    }

    public function test_runtime_bridge_enables_notifications_capability(): void
    {
        $context = $this->tenantContext();

        $runtime = app(EnterpriseRuntimeBridge::class)->resolve($context);

        $this->assertTrue($runtime->capabilityEnabled('notifications'));
        $this->assertTrue($runtime->capabilityEnabled('events'));
        $this->assertTrue($runtime->capabilityEnabled('reference_data'));
    }

    public function test_reference_catalog_registration_records_audit(): void
    {
        app(\App\Modules\Sdk\Enterprise\Contracts\ReferenceDataPort::class)->registerCatalog(
            new ReferenceCatalogData('test-catalog', 'Test Catalog', moduleKey: 'demo'),
        );

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ReferenceCatalogRegistered->value)->exists());
    }

    public function test_notifications_api_lists_unread_notifications(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        app(NotificationService::class)->notify($context, new NotificationRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            recipientMembershipPublicId: $context->membershipPublicId,
            type: 'demo.api',
            title: 'API',
            body: 'From API test',
        ));

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_reference_data_api_returns_catalog(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/reference/currencies')
            ->assertOk()
            ->assertJsonPath('data.catalog.key', 'currencies')
            ->assertJsonStructure(['data' => ['catalog', 'items']]);
    }

    public function test_doctor_includes_enterprise_summary(): void
    {
        \Illuminate\Support\Facades\Artisan::call('heos:doctor', ['--json' => true]);

        $payload = json_decode(\Illuminate\Support\Facades\Artisan::output(), true);

        $this->assertTrue($payload['platform_summary']['enterprise']['notifications']);
        $this->assertTrue($payload['platform_summary']['enterprise']['event_bus']);
    }

    public function test_event_bus_rejects_when_capability_disabled(): void
    {
        config([
            'heos.enterprise.runtime_aware' => false,
            'heos.enterprise.event_bus.enabled' => false,
        ]);
        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);

        app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            eventName: 'platform.disabled',
        ));
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $this->seed(ReferenceDataSeeder::class);

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'enterprise-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }
}
