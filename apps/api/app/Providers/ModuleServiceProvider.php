<?php

namespace App\Providers;

use App\Modules\Sdk\Contracts\ModuleRegistryEventDispatcher;
use App\Modules\Sdk\Contracts\ModuleSyncPort;
use App\Modules\Sdk\ModuleManifestValidator;
use App\Modules\Sdk\ModuleRegistry;
use App\Modules\Sdk\SimpleModuleRegistryEventDispatcher;
use App\Services\Module\ModuleSyncService;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistryEventDispatcher::class, SimpleModuleRegistryEventDispatcher::class);
        $this->app->singleton(ModuleManifestValidator::class);
        $this->app->singleton(ModuleSyncService::class);
        $this->app->singleton(ModuleSyncPort::class, ModuleSyncService::class);
        $this->app->singleton(\App\Modules\Sdk\Lifecycle\ModuleLifecycleDispatcher::class);
        $this->app->singleton(\App\Services\Audit\ModuleLifecycleAuditRecorder::class);
        $this->app->singleton(\App\Services\Module\ModuleLifecycleManager::class);
        $this->app->singleton(\App\Modules\Sdk\Runtime\RuntimeContributorPipeline::class);
        $this->app->singleton(\App\Modules\Sdk\Runtime\RuntimeExtensionManager::class);
        $this->app->singleton(\App\Services\Module\RuntimeContributionAuditRecorder::class);
        $this->app->singleton(\App\Services\Module\RuntimeExtensionService::class);
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry(
                $app->make(ModuleManifestValidator::class),
                $app->make(ModuleRegistryEventDispatcher::class),
                $app->make(ModuleSyncPort::class),
            );
        });

        foreach (config('heos.module_providers', []) as $providerClass) {
            $this->app->register($providerClass);
        }
    }
}
