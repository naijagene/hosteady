<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleValidationReport
{
    /**
     * @param  list<ModuleValidationIssue>  $issues
     */
    public function __construct(
        public array $issues = [],
    ) {
    }

    public function isValid(): bool
    {
        return $this->issues === [];
    }
}
