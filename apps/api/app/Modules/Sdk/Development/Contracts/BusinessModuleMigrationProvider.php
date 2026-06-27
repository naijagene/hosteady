<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleMigrationDefinition;

interface BusinessModuleMigrationProvider
{
    public function moduleKey(): string;

    /**
     * @return list<BusinessModuleMigrationDefinition>
     */
    public function migrations(): array;
}
