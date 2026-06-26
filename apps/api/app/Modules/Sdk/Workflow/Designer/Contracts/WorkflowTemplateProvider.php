<?php

namespace App\Modules\Sdk\Workflow\Designer\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowNodeTemplate;

interface WorkflowTemplateProvider
{
    /**
     * @return list<WorkflowNodeTemplate>
     */
    public function listTemplates(EnterpriseScope $scope): array;

    public function ensureSystemTemplates(): void;
}
