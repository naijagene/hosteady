<?php

namespace App\Services\Enterprise\Runtime;

use App\Modules\Sdk\Enterprise\Contracts\EnterpriseRuntimeContext;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;

class EnterpriseRuntimeContextFactory
{
    public function fromRuntime(WorkspaceRuntimeContext $runtime): EnterpriseRuntimeContext
    {
        return new WorkspaceEnterpriseRuntimeContext($runtime);
    }

    public function fromConfig(): EnterpriseRuntimeContext
    {
        return new ConfigEnterpriseRuntimeContext;
    }
}
