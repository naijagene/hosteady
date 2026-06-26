<?php

namespace App\Modules\Sdk\Workflow\Contracts;

use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;

interface WorkflowDefinitionProvider
{
    public function moduleKey(): string;

    /**
     * @return list<WorkflowDefinitionData>
     */
    public function definitions(): array;
}
