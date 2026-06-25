<?php

namespace Tests\Feature\Services\Runtime;

use App\Services\Runtime\CachedWorkspaceRuntimeProvider;
use App\Services\Runtime\LaravelRuntimeCacheStore;
use App\Services\Runtime\RuntimeCacheKeyBuilder;
use App\Services\Runtime\RuntimeMetricsCollector;
use App\Services\Runtime\RuntimeSnapshotSerializer;
use App\Services\WorkspaceApplication\WorkspaceRuntimeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class RuntimeCacheLockTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_stampede_protection_resolves_when_cache_enabled(): void
    {
        config(['heos.runtime_cache.enabled' => true]);

        $context = $this->tenantContext();
        $store = new LaravelRuntimeCacheStore(Cache::store('array'));
        $provider = new CachedWorkspaceRuntimeProvider(
            app(WorkspaceRuntimeResolver::class),
            $store,
            new RuntimeCacheKeyBuilder($store),
            app(RuntimeSnapshotSerializer::class),
            app(RuntimeMetricsCollector::class),
        );

        $first = $provider->resolve($context);
        $second = $provider->resolve($context);

        $this->assertSame($first->runtimeVersion, $second->runtimeVersion);
    }

    public function test_cache_hit_marks_metrics_as_hit_possible(): void
    {
        config(['heos.runtime_cache.enabled' => true]);

        $context = $this->tenantContext();
        $store = new LaravelRuntimeCacheStore(Cache::store('array'));
        $provider = new CachedWorkspaceRuntimeProvider(
            app(WorkspaceRuntimeResolver::class),
            $store,
            new RuntimeCacheKeyBuilder($store),
            app(RuntimeSnapshotSerializer::class),
            app(RuntimeMetricsCollector::class),
        );

        $provider->resolve($context);
        $provider->resolve($context);

        $this->assertTrue(app(RuntimeMetricsCollector::class)->lastCacheHitPossible());
    }

    private function tenantContext(): \App\Support\Tenant\TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-lock-org']);
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return \App\Support\Tenant\TenantContext::fromModels($user, $organization, $membership, $workspace);
    }
}
