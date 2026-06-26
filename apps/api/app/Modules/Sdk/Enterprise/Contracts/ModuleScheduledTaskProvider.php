<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

interface ModuleScheduledTaskProvider
{
    /**
     * @return list<string>
     */
    public function supportedTaskTypes(): array;
}
