<?php

namespace App\Modules\Sdk\Workflow\Designer\Contracts;

use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;

interface WorkflowLayoutProvider
{
    public function defaultLayout(): array;

    public function applyAutoLayout(WorkflowCanvas $canvas): WorkflowCanvas;
}
