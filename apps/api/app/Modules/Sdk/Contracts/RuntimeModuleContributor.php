<?php

namespace App\Modules\Sdk\Contracts;

/**
 * Reserved runtime extension contract (Slice 5).
 */
interface RuntimeModuleContributor
{
    public function moduleKey(): string;

    /**
     * @return array<string, mixed>
     */
    public function contribute(ModuleRuntimeContext $context): array;
}
