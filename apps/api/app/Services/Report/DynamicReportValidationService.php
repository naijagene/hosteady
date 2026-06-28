<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Exceptions\ReportValidationException;

class DynamicReportValidationService
{
    public function validate(ReportDefinition $definition): bool
    {
        $this->assertValid($definition);

        return true;
    }

    public function assertValid(ReportDefinition $definition): void
    {
        if ($definition->moduleKey === '') {
            throw new ReportValidationException('Module key is required.');
        }

        if ($definition->reportKey === '') {
            throw new ReportValidationException('Report key is required.');
        }

        if ($definition->name === '') {
            throw new ReportValidationException('Report name is required.');
        }

        if (! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->moduleKey)) {
            throw new ReportValidationException('Module key format is invalid.');
        }

        if (! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->reportKey)) {
            throw new ReportValidationException('Report key format is invalid.');
        }
    }
}
