<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleScaffoldRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleScaffoldResult;

interface BusinessModuleScaffolder
{
    public function scaffold(BusinessModuleScaffoldRequest $request): BusinessModuleScaffoldResult;
}
