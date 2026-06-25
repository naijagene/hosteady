<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use App\Observers\ApplicationRuntimeCacheObserver;
use App\Observers\ApplicationSettingDefinitionRuntimeCacheObserver;
use App\Services\Runtime\CachedWorkspaceRuntimeProvider;
use App\Services\Runtime\LaravelRuntimeCacheStore;
use App\Services\Runtime\RuntimeCacheStore;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Services\WorkspaceApplication\WorkspaceRuntimeResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RuntimeCacheStore::class, function ($app) {
            $configuredStore = config('heos.runtime_cache.store');

            if (is_string($configuredStore) && $configuredStore !== '') {
                return new LaravelRuntimeCacheStore(Cache::store($configuredStore));
            }

            return new LaravelRuntimeCacheStore($app->make('cache.store'));
        });

        $this->app->bind(WorkspaceRuntimeProvider::class, function ($app) {
            $resolver = $app->make(WorkspaceRuntimeResolver::class);

            if (! config('heos.runtime_cache.enabled', true)) {
                return $resolver;
            }

            return new CachedWorkspaceRuntimeProvider(
                $resolver,
                $app->make(RuntimeCacheStore::class),
                $app->make(\App\Services\Runtime\RuntimeCacheKeyBuilder::class),
                $app->make(\App\Services\Runtime\RuntimeSnapshotSerializer::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Application::observe(ApplicationRuntimeCacheObserver::class);
        ApplicationSettingDefinition::observe(ApplicationSettingDefinitionRuntimeCacheObserver::class);
    }
}
