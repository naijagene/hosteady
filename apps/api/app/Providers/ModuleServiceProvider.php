<?php

namespace App\Providers;

use App\Modules\Sdk\Contracts\ModuleRegistryEventDispatcher;
use App\Modules\Sdk\ModuleManifestValidator;
use App\Modules\Sdk\ModuleRegistry;
use App\Modules\Sdk\SimpleModuleRegistryEventDispatcher;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistryEventDispatcher::class, SimpleModuleRegistryEventDispatcher::class);
        $this->app->singleton(ModuleManifestValidator::class);
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry(
                $app->make(ModuleManifestValidator::class),
                $app->make(ModuleRegistryEventDispatcher::class),
            );
        });

        foreach (config('heos.module_providers', []) as $providerClass) {
            $this->app->register($providerClass);
        }
    }
}
