<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Contracts;

use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;

interface WorkflowPackageValidator
{
    /**
     * @return list<string>
     */
    public function validate(WorkflowPackageManifest $manifest): array;
}
