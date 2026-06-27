<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleHealthReport;

interface BusinessModuleHealthProvider
{
    public function health(): BusinessModuleHealthReport;
}
