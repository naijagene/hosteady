<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleValidationReport;

interface BusinessModuleValidator
{
    public function validate(BusinessModuleManifest $manifest): BusinessModuleValidationReport;
}
