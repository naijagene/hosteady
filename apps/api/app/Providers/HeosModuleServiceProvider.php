<?php

namespace App\Providers;

use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

abstract class HeosModuleServiceProvider extends ServiceProvider
{
    abstract protected function moduleClass(): string;

    public function register(): void
    {
        $moduleClass = $this->moduleClass();

        $this->app->singleton($moduleClass, fn () => new $moduleClass);

        $this->app->make(ModuleRegistry::class)->register($this->app->make($moduleClass));
    }

    public function boot(): void
    {
        /** @var ApplicationModule $module */
        $module = $this->app->make($this->moduleClass());
        $module->boot();
    }
}
