<?php

namespace Tests\Feature\Services\Runtime;

use App\Services\Runtime\CachedWorkspaceRuntimeProvider;
use App\Services\Runtime\LaravelRuntimeCacheStore;
use App\Services\Runtime\RuntimeCacheKeyBuilder;
use App\Services\Runtime\RuntimeMetricsCollector;
use App\Services\Runtime\RuntimeSnapshotSerializer;
use App\Services\WorkspaceApplication\Data\RuntimeMembershipSnapshot;
use App\Services\WorkspaceApplication\Data\RuntimeOrganizationSnapshot;
use App\Services\WorkspaceApplication\Data\RuntimeWorkspaceSnapshot;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Services\WorkspaceApplication\WorkspaceRuntimeResolver;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class CachedWorkspaceRuntimeProviderTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_bypasses_cache_when_disabled(): void
    {
        config(['heos.runtime_cache.enabled' => false]);

        $context = $this->tenantContext();
        $inner = Mockery::mock(WorkspaceRuntimeResolver::class);
        $inner->shouldReceive('resolve')->twice()->andReturn($this->runtimeContext($context));

        $provider = $this->cachedProvider($inner);

        $provider->resolve($context);
        $provider->resolve($context);
    }

    public function test_resolves_from_cache_on_hit(): void
    {
        config(['heos.runtime_cache.enabled' => true]);

        $context = $this->tenantContext();
        $runtime = $this->runtimeContext($context);
        $summary = new WorkspaceRuntimeSummary(2, 'runtime-hash', 1);

        $inner = Mockery::mock(WorkspaceRuntimeResolver::class);
        $inner->shouldReceive('resolveSummary')->twice()->andReturn($summary);
        $inner->shouldReceive('resolve')->once()->andReturn($runtime);

        $provider = $this->cachedProvider($inner);

        $first = $provider->resolve($context);
        $second = $provider->resolve($context);

        $this->assertSame($first->runtimeVersion, $second->runtimeVersion);
    }

    public function test_stores_snapshot_after_cache_miss(): void
    {
        config(['heos.runtime_cache.enabled' => true]);

        $context = $this->tenantContext();
        $runtime = $this->runtimeContext($context);
        $summary = new WorkspaceRuntimeSummary(2, 'runtime-hash', 1);
        $store = new LaravelRuntimeCacheStore(Cache::store('array'));
        $keyBuilder = new RuntimeCacheKeyBuilder($store);
        $cacheKey = $keyBuilder->build($context, $summary);

        $inner = Mockery::mock(WorkspaceRuntimeResolver::class);
        $inner->shouldReceive('resolveSummary')->once()->andReturn($summary);
        $inner->shouldReceive('resolve')->once()->andReturn($runtime);

        $provider = new CachedWorkspaceRuntimeProvider(
            $inner,
            $store,
            $keyBuilder,
            app(RuntimeSnapshotSerializer::class),
            app(RuntimeMetricsCollector::class),
        );

        $provider->resolve($context);

        $this->assertIsArray($store->get($cacheKey));
    }

    public function test_cache_miss_after_generation_increment(): void
    {
        config(['heos.runtime_cache.enabled' => true]);

        $context = $this->tenantContext();
        $runtime = $this->runtimeContext($context);
        $summary = new WorkspaceRuntimeSummary(2, 'runtime-hash', 1);

        $inner = Mockery::mock(WorkspaceRuntimeResolver::class);
        $inner->shouldReceive('resolveSummary')->twice()->andReturn($summary);
        $inner->shouldReceive('resolve')->twice()->andReturn($runtime);

        $provider = $this->cachedProvider($inner);

        $provider->resolve($context);
        app(\App\Services\Runtime\RuntimeCacheInvalidator::class)->invalidateTenantContext($context);
        $provider->resolve($context);
    }

    public function test_resolve_summary_always_delegates_to_inner_provider(): void
    {
        config(['heos.runtime_cache.enabled' => true]);

        $context = $this->tenantContext();
        $summary = new WorkspaceRuntimeSummary(2, 'runtime-hash', 1);

        $inner = Mockery::mock(WorkspaceRuntimeResolver::class);
        $inner->shouldReceive('resolveSummary')->once()->andReturn($summary);

        $summaryResult = $this->cachedProvider($inner)->resolveSummary($context);

        $this->assertSame('runtime-hash', $summaryResult->runtimeVersion);
    }

    public function test_uses_runtime_cache_store_abstraction(): void
    {
        $this->assertInstanceOf(
            \App\Services\Runtime\RuntimeCacheStore::class,
            app(\App\Services\Runtime\RuntimeCacheStore::class),
        );
        $this->assertInstanceOf(
            WorkspaceRuntimeProvider::class,
            app(WorkspaceRuntimeProvider::class),
        );
    }

    private function cachedProvider(WorkspaceRuntimeResolver $inner): CachedWorkspaceRuntimeProvider
    {
        $store = new LaravelRuntimeCacheStore(Cache::store('array'));

        return new CachedWorkspaceRuntimeProvider(
            $inner,
            $store,
            new RuntimeCacheKeyBuilder($store),
            app(RuntimeSnapshotSerializer::class),
            app(RuntimeMetricsCollector::class),
        );
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'cached-runtime-org']);
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }

    private function runtimeContext(TenantContext $context): WorkspaceRuntimeContext
    {
        return new WorkspaceRuntimeContext(
            organization: new RuntimeOrganizationSnapshot(
                $context->organizationPublicId,
                $context->organization->name,
                $context->organization->slug,
                $context->organization->status->value,
            ),
            workspace: new RuntimeWorkspaceSnapshot(
                $context->workspacePublicId,
                $context->workspace->name,
                $context->workspace->slug,
                $context->workspace->is_default,
                $context->workspace->status->value,
            ),
            membership: new RuntimeMembershipSnapshot(
                $context->membershipPublicId,
                $context->membership->status->value,
            ),
            activeApplications: [],
            activeApplication: null,
            runtimeVersion: 'runtime-hash',
            settingsVersion: 1,
            runtimeMetadata: [
                'generated_at' => now()->toIso8601String(),
                'generated_by' => 'WorkspaceRuntimeResolver',
                'schema_version' => 1,
            ],
            capabilities: [
                'audit' => true,
                'settings' => true,
                'workspace' => true,
                'notifications' => false,
                'automation' => false,
            ],
        );
    }
}
