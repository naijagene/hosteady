<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PlatformSavedSearch;
use App\Models\PlatformSearchIndex;
use App\Models\SearchActivityLog;
use App\Modules\Sdk\Enterprise\Contracts\IndexPort;
use App\Modules\Sdk\Enterprise\Contracts\SearchPort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Modules\Sdk\Enterprise\Data\SearchRequest;
use App\Modules\Sdk\Enterprise\Data\SavedSearchReference;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Services\Enterprise\Search\SearchHealthService;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Services\Enterprise\Search\SearchService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4SearchServiceTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_search_index_reference_serializes_to_array(): void
    {
        $reference = new SearchIndexReference(
            publicId: '01900000-0000-7000-8000-000000000099',
            displayName: 'Rose Accord',
            entityType: 'fragrance',
            entityPublicId: 'frag-1',
            moduleKey: 'scentmaker',
        );

        $this->assertSame('Rose Accord', $reference->toArray()['display_name']);
    }

    public function test_saved_search_reference_serializes_to_array(): void
    {
        $reference = new SavedSearchReference(
            publicId: '01900000-0000-7000-8000-000000000100',
            name: 'My Fragrances',
            query: 'rose',
        );

        $this->assertSame('My Fragrances', $reference->toArray()['name']);
    }

    public function test_index_upsert_creates_search_record(): void
    {
        $context = $this->tenantContext();

        $reference = $this->indexEntity($context, 'Vanilla Batch', 'batch', 'batch-1');

        $this->assertTrue(PlatformSearchIndex::query()->where('public_id', $reference->publicId)->exists());
        $this->assertSame('Vanilla Batch', $reference->displayName);
    }

    public function test_index_upsert_updates_existing_record(): void
    {
        $context = $this->tenantContext();

        $first = $this->indexEntity($context, 'Draft Formula', 'formula', 'formula-1');
        $second = $this->indexEntity($context, 'Published Formula', 'formula', 'formula-1', keywords: 'published');

        $this->assertSame($first->publicId, $second->publicId);
        $this->assertSame('Published Formula', PlatformSearchIndex::query()->where('public_id', $first->publicId)->value('display_name'));
    }

    public function test_index_remove_deletes_record(): void
    {
        $context = $this->tenantContext();
        $reference = $this->indexEntity($context, 'Temporary Actor', 'actor', 'actor-1');

        app(SearchIndexService::class)->remove($context, 'actor', 'actor-1', 'demo');

        $this->assertSoftDeleted('platform_search_indexes', ['public_id' => $reference->publicId]);
    }

    public function test_module_registration_tracks_supported_modules(): void
    {
        app(IndexPort::class)->registerModule('scentmaker', ['fragrance', 'formula']);
        app(IndexPort::class)->registerModule('nollysoft', ['actor', 'production']);

        $this->assertSame(['scentmaker', 'nollysoft'], app(IndexPort::class)->supportedModules());
    }

    public function test_search_matches_display_name(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Lavender Harvest', 'crop', 'crop-1', keywords: 'autumn');
        $this->indexEntity($context, 'Wheat Field', 'crop', 'crop-2');

        $result = app(SearchService::class)->search($context, new SearchRequest(
            scope: $this->scope($context),
            query: 'lavender',
        ));

        $this->assertSame(1, $result->total);
        $this->assertSame('Lavender Harvest', $result->items[0]->displayName);
    }

    public function test_search_matches_keywords(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Order 1001', 'order', 'ord-1', keywords: 'priority wholesale');

        $result = app(SearchService::class)->search($context, new SearchRequest(
            scope: $this->scope($context),
            query: 'wholesale',
        ));

        $this->assertSame(1, $result->total);
    }

    public function test_search_filters_by_module_key(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Actor One', 'actor', 'actor-1', moduleKey: 'nollysoft');
        $this->indexEntity($context, 'Actor Two', 'actor', 'actor-2', moduleKey: 'demo');

        $result = app(SearchService::class)->search($context, new SearchRequest(
            scope: $this->scope($context, 'nollysoft'),
            moduleKey: 'nollysoft',
            query: 'actor',
        ));

        $this->assertSame(1, $result->total);
        $this->assertSame('nollysoft', $result->items[0]->moduleKey);
    }

    public function test_search_filters_by_entity_type(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Script Alpha', 'script', 'script-1');
        $this->indexEntity($context, 'Scene Alpha', 'scene', 'scene-1');

        $result = app(SearchService::class)->search($context, new SearchRequest(
            scope: $this->scope($context),
            entityType: 'script',
            query: 'alpha',
        ));

        $this->assertSame(1, $result->total);
        $this->assertSame('script', $result->items[0]->entityType);
    }

    public function test_search_records_activity_log(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Inventory Bolt', 'inventory', 'inv-1');

        app(SearchService::class)->search($context, new SearchRequest(
            scope: $this->scope($context),
            query: 'inventory',
            membershipPublicId: $context->membershipPublicId,
        ));

        $this->assertTrue(SearchActivityLog::query()->where('organization_id', $context->organization->id)->exists());
    }

    public function test_search_records_audit_event(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Customer Acme', 'customer', 'cust-1');

        app(SearchService::class)->search($context, new SearchRequest(
            scope: $this->scope($context),
            query: 'acme',
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::SearchExecuted->value)->exists());
    }

    public function test_index_upsert_records_audit_event(): void
    {
        $context = $this->tenantContext();

        $this->indexEntity($context, 'Indexed Item', 'item', 'item-1');

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::IndexUpdated->value)->exists());
    }

    public function test_suggestions_return_display_names(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Inspection Report', 'inspection', 'insp-1');

        $suggestions = app(SearchService::class)->suggestions($context, 'inspection');

        $this->assertContains('Inspection Report', $suggestions);
    }

    public function test_recent_returns_search_history(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Harvest Log', 'harvest', 'harvest-1');

        app(SearchService::class)->search($context, new SearchRequest(
            scope: $this->scope($context),
            query: 'harvest',
            membershipPublicId: $context->membershipPublicId,
        ));

        $recent = app(SearchService::class)->recent($context);

        $this->assertNotEmpty($recent);
        $this->assertSame('harvest', $recent[0]['query']);
    }

    public function test_save_search_creates_record(): void
    {
        $context = $this->tenantContext();

        $saved = app(SearchService::class)->saveSearch($context, 'Open Orders', 'pending', ['status' => 'open']);

        $this->assertTrue(PlatformSavedSearch::query()->where('public_id', $saved->publicId)->exists());
    }

    public function test_save_search_records_audit_event(): void
    {
        $context = $this->tenantContext();

        app(SearchService::class)->saveSearch($context, 'Customers', 'acme');

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::SearchSaved->value)->exists());
    }

    public function test_list_saved_searches_returns_membership_scoped_items(): void
    {
        $context = $this->tenantContext();

        app(SearchService::class)->saveSearch($context, 'Saved One', 'one');
        app(SearchService::class)->saveSearch($context, 'Saved Two', 'two');

        $saved = app(SearchService::class)->listSavedSearches($context);

        $this->assertCount(2, $saved);
    }

    public function test_delete_saved_search_soft_deletes_record(): void
    {
        $context = $this->tenantContext();
        $saved = app(SearchService::class)->saveSearch($context, 'Temporary', 'temp');

        app(SearchService::class)->deleteSavedSearch($context, $saved->publicId);

        $this->assertSoftDeleted('platform_saved_searches', ['public_id' => $saved->publicId]);
    }

    public function test_delete_saved_search_records_audit_event(): void
    {
        $context = $this->tenantContext();
        $saved = app(SearchService::class)->saveSearch($context, 'To Delete', 'delete-me');

        app(SearchService::class)->deleteSavedSearch($context, $saved->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::SearchDeleted->value)->exists());
    }

    public function test_search_health_service_reports_index_count(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Health Check Item', 'entity', 'ent-health');

        $health = app(SearchHealthService::class)->assess($context);

        $this->assertSame(1, $health['index_count']);
        $this->assertTrue($health['enabled']);
    }

    public function test_runtime_includes_search_capabilities(): void
    {
        $context = $this->tenantContext();

        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['search']);
        $this->assertTrue($runtime->capabilities['indexing']);
        $this->assertArrayHasKey('search', $runtime->runtimeMetadata['enterprise']);
    }

    public function test_runtime_search_disabled_when_config_off(): void
    {
        config(['heos.enterprise.search.enabled' => false]);

        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertFalse($runtime->capabilities['search']);
        $this->assertFalse($runtime->capabilities['indexing']);
    }

    public function test_doctor_exposes_search_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('search', $report->platformSummary['enterprise']);
    }

    public function test_search_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.search.enabled' => false]);
        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);

        app(SearchService::class)->search($context, new SearchRequest(
            scope: $this->scope($context),
            query: 'blocked',
        ));
    }

    public function test_indexing_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.search.enabled' => false]);
        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);

        $this->indexEntity($context, 'Blocked Index', 'entity', 'blocked-1');
    }

    public function test_search_api_returns_results(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'API Search Target', 'product', 'prod-1');
        $token = $this->issueToken($context->user);

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/search?q=api')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_search_suggestions_api_returns_suggestions(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Suggestion Target', 'item', 'item-suggest');
        $token = $this->issueToken($context->user);

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/search/suggestions?q=suggestion')
            ->assertOk()
            ->assertJsonFragment(['Suggestion Target']);
    }

    public function test_saved_search_api_lifecycle(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);
        $headers = [
            'Authorization' => 'Bearer '.$token,
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];

        $create = $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/search/saved', [
                'name' => 'API Saved',
                'query' => 'api-query',
            ])
            ->assertCreated();

        $publicId = $create->json('data.public_id');

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/search/saved')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($headers)
            ->deleteJson('/api/v1/tenant/search/saved/'.$publicId)
            ->assertOk();
    }

    public function test_search_port_direct_query(): void
    {
        $context = $this->tenantContext();
        $this->indexEntity($context, 'Direct Port Query', 'entity', 'direct-1');

        $result = app(SearchPort::class)->search(new SearchRequest(
            scope: $this->scope($context),
            query: 'direct',
        ));

        $this->assertSame(1, $result->total);
    }

    public function test_index_stores_entity_reference(): void
    {
        $context = $this->tenantContext();

        $reference = app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
            scope: $this->scope($context, 'demo'),
            entityType: 'customer',
            entityPublicId: 'cust-ref-1',
            displayName: 'Referenced Customer',
            entityReference: new EntityReference('customer', 'cust-ref-1', 'demo', 'Referenced Customer'),
        ));

        $this->assertSame('customer', $reference->entityReference?->type);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'search-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function scope(TenantContext $context, ?string $moduleKey = null): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            moduleKey: $moduleKey ?? 'demo',
        );
    }

    private function indexEntity(
        TenantContext $context,
        string $displayName,
        string $entityType,
        string $entityPublicId,
        ?string $keywords = null,
        string $moduleKey = 'demo',
    ): SearchIndexReference {
        return app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
            scope: $this->scope($context, $moduleKey),
            entityType: $entityType,
            entityPublicId: $entityPublicId,
            displayName: $displayName,
            keywords: $keywords,
        ));
    }
}
