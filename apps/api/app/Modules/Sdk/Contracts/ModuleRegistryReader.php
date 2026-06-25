<?php

namespace App\Modules\Sdk\Contracts;

use App\Modules\Sdk\Data\ModuleValidationReport;

interface ModuleRegistryReader
{
    /**
     * @return list<ApplicationModule>
     */
    public function all(): array;

    public function findByKey(string $key): ?ApplicationModule;

    public function validate(): ModuleValidationReport;
}
