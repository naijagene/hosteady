<?php

namespace App\Observers;

use App\Models\ApplicationSettingDefinition;
use App\Services\Runtime\RuntimeCacheInvalidator;

class ApplicationSettingDefinitionRuntimeCacheObserver
{
    public function __construct(
        private readonly RuntimeCacheInvalidator $runtimeCacheInvalidator,
    ) {
    }

    public function saved(ApplicationSettingDefinition $definition): void
    {
        $this->runtimeCacheInvalidator->invalidateForApplicationCatalogChange($definition->application_id);
    }

    public function deleted(ApplicationSettingDefinition $definition): void
    {
        $this->runtimeCacheInvalidator->invalidateForApplicationCatalogChange($definition->application_id);
    }
}
