<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Contracts;

use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;

interface WorkflowPackageProvider
{
    public function normalizeManifest(WorkflowPackageManifest $manifest): WorkflowPackageManifest;
}
