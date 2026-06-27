<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleSeederDefinition;

interface BusinessModuleSeeder
{
    public function moduleKey(): string;

    /**
     * @return list<BusinessModuleSeederDefinition>
     */
    public function seeders(): array;
}
