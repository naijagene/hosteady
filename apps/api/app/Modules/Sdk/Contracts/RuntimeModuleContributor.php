<?php

namespace App\Modules\Sdk\Contracts;

use App\Modules\Sdk\Runtime\RuntimeContribution;

interface RuntimeModuleContributor
{
    public function moduleKey(): string;

    public function priority(): int;

    /**
     * @return list<string>
     */
    public function dependencyKeys(): array;

    public function contribute(ModuleRuntimeContext $context): RuntimeContribution;
}
