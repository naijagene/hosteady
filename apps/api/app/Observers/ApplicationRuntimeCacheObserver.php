<?php

namespace App\Observers;

use App\Models\Application;
use App\Services\Runtime\RuntimeCacheInvalidator;

class ApplicationRuntimeCacheObserver
{
    public function __construct(
        private readonly RuntimeCacheInvalidator $runtimeCacheInvalidator,
    ) {
    }

    public function updated(Application $application): void
    {
        if (! $application->wasChanged(['capabilities', 'dependencies', 'version', 'status'])) {
            return;
        }

        $this->runtimeCacheInvalidator->invalidateForApplicationCatalogChange($application->id);
    }
}
