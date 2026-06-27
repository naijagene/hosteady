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
        $this->app->singleton(\App\Services\Module\ModuleValidationService::class);
        $this->app->singleton(\App\Services\Module\ModuleDependencyGraphService::class);
        $this->app->singleton(\App\Services\Module\ModuleHealthAggregator::class);
        $this->app->singleton(\App\Services\Module\ModuleInspectionService::class);
        $this->app->singleton(\App\Services\Module\ModuleDocumentationService::class);
        $this->app->singleton(\App\Services\Module\ModuleDoctorService::class);
        $this->app->singleton(\App\Services\Module\ModuleDeveloperAuditRecorder::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleValidatorService::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleRegistryService::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleInstallerService::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleScaffolderService::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleHealthService::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleStatisticsService::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleManifestLoader::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleFilesystemService::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleAuditRecorder::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleSearchIndexer::class);
        $this->app->singleton(\App\Services\Module\Development\BusinessModuleDevelopmentService::class);
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
