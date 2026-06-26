<?php

namespace App\Modules\Sdk\Workflow\Contracts;

interface ModuleWorkflowProvider
{
    public function moduleKey(): string;

    /**
     * @return list<string>
     */
    public function workflowKeys(): array;
}
