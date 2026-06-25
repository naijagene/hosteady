<?php

namespace App\Modules\Sdk\Contracts;

/**
 * Reserved lifecycle hooks for future platform integration (Slice 4+).
 */
interface ModuleLifecycleHandler
{
    public function beforeRuntimeResolved(ModuleRuntimeContext $context): void;

    public function afterRuntimeResolved(ModuleRuntimeContext $context): void;
}
