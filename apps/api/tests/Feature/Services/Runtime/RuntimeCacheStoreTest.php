<?php

namespace Tests\Feature\Services\Runtime;

use App\Services\Runtime\LaravelRuntimeCacheStore;
use App\Services\Runtime\RuntimeCacheKeyBuilder;
use App\Services\Runtime\RuntimeCacheStore;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class RuntimeCacheStoreTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private RuntimeCacheStore $cacheStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheStore = new LaravelRuntimeCacheStore(Cache::store('array'));
    }

    public function test_stores_and_retrieves_cached_payload(): void
    {
        $this->cacheStore->put('heos:runtime:test', ['runtime_version' => 'abc'], 300);

        $this->assertSame(['runtime_version' => 'abc'], $this->cacheStore->get('heos:runtime:test'));
    }

    public function test_returns_null_on_cache_miss(): void
    {
        $this->assertNull($this->cacheStore->get('heos:runtime:missing'));
    }

    public function test_starts_generation_at_one(): void
    {
        $this->assertSame(1, $this->cacheStore->currentGeneration('org-public-id', 'workspace-public-id'));
    }

    public function test_increments_generation_counter(): void
    {
        $this->assertSame(1, $this->cacheStore->incrementGeneration('org-public-id', 'workspace-public-id'));
        $this->assertSame(2, $this->cacheStore->incrementGeneration('org-public-id', 'workspace-public-id'));
        $this->assertSame(2, $this->cacheStore->currentGeneration('org-public-id', 'workspace-public-id'));
    }

    public function test_builds_cache_key_with_schema_version(): void
    {
        config(['heos.runtime_cache.schema_version' => 1]);

        $context = $this->tenantContext();
        $summary = new WorkspaceRuntimeSummary(
            activeApplicationCount: 2,
            runtimeVersion: 'runtime-hash',
            settingsVersion: 3,
        );

        $key = app(RuntimeCacheKeyBuilder::class)->build($context, $summary, null);

        $this->assertStringStartsWith('heos:runtime:v1:schema1:', $key);
        $this->assertStringContainsString($context->organizationPublicId, $key);
        $this->assertStringContainsString($context->workspacePublicId, $key);
        $this->assertStringContainsString('runtime-hash', $key);
        $this->assertStringContainsString(':3:none', $key);
    }

    public function test_builds_cache_key_with_active_application_public_id(): void
    {
        $context = $this->tenantContext();
        $summary = new WorkspaceRuntimeSummary(2, 'runtime-hash', 1);
        $activePublicId = '01999999-9999-7999-8999-999999999999';

        $key = app(RuntimeCacheKeyBuilder::class)->build($context, $summary, $activePublicId);

        $this->assertStringEndsWith(':'.$activePublicId, $key);
    }

    public function test_cache_key_changes_when_generation_increments(): void
    {
        $context = $this->tenantContext();
        $summary = new WorkspaceRuntimeSummary(2, 'runtime-hash', 1);
        $builder = app(RuntimeCacheKeyBuilder::class);

        $before = $builder->build($context, $summary);
        $this->cacheStore->incrementGeneration($context->organizationPublicId, $context->workspacePublicId);
        $after = $builder->build($context, $summary);

        $this->assertNotSame($before, $after);
    }

    public function test_cache_key_changes_when_schema_version_changes(): void
    {
        $context = $this->tenantContext();
        $summary = new WorkspaceRuntimeSummary(2, 'runtime-hash', 1);
        $builder = app(RuntimeCacheKeyBuilder::class);

        config(['heos.runtime_cache.schema_version' => 1]);
        $schemaOne = $builder->build($context, $summary);

        config(['heos.runtime_cache.schema_version' => 2]);
        $schemaTwo = $builder->build($context, $summary);

        $this->assertStringContainsString(':schema1:', $schemaOne);
        $this->assertStringContainsString(':schema2:', $schemaTwo);
        $this->assertNotSame($schemaOne, $schemaTwo);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-cache-org']);
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }
}
