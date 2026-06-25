<?php

namespace App\Services\Module;

use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRuntimeContext;
use App\Modules\Sdk\Contracts\RuntimeModuleContributor;
use App\Modules\Sdk\Runtime\RuntimeContribution;

class ApplicationModuleRuntimeContributor implements RuntimeModuleContributor
{
    public function __construct(
        private readonly ApplicationModule $module,
    ) {
    }

    public function moduleKey(): string
    {
        return $this->module->key();
    }

    public function priority(): int
    {
        return 0;
    }

    public function dependencyKeys(): array
    {
        return array_map(
            fn ($dependency) => $dependency->key,
            $this->module->manifest()->dependencies,
        );
    }

    public function contribute(ModuleRuntimeContext $context): RuntimeContribution
    {
        return $this->module->contributeRuntime($context);
    }
}
