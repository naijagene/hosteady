<?php

namespace App\Modules\Sdk\Workflow\Designer\Contracts;

use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;

interface WorkflowCanvasNormalizer
{
    /**
     * @param  list<array<string, mixed>>  $definitionNodes
     * @return array{canvas: WorkflowCanvas, warnings: list<array<string, mixed>>}
     */
    public function normalize(WorkflowCanvas $canvas, array $definitionNodes = []): array;
}
