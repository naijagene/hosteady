<?php

namespace App\Modules\Sdk\Workflow\Contracts;

use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;

interface WorkflowValidator
{
    public function validate(WorkflowDefinitionData $data): WorkflowValidationReport;

    public function validateDefinitionJson(array $definitionJson, ?string $workflowKey = null): WorkflowValidationReport;
}
