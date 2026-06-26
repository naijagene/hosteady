<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

interface ModuleJobProvider
{
    /**
     * @return list<string>
     */
    public function supportedJobTypes(): array;
}
