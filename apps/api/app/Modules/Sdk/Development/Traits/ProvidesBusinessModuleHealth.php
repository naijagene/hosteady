<?php

namespace App\Modules\Sdk\Development\Traits;

use App\Modules\Sdk\Development\Data\BusinessModuleHealthReport;

trait ProvidesBusinessModuleHealth
{
    public function health(): BusinessModuleHealthReport
    {
        return new BusinessModuleHealthReport(
            enabled: true,
            registered: 1,
            installed: 0,
            warnings: [],
            status: 'healthy',
        );
    }
}
