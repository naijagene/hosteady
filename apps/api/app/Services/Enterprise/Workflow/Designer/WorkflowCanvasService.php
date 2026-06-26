<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowLayoutProvider;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasNode;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasViewport;

class WorkflowCanvasService implements WorkflowLayoutProvider
{
    /**
     * @return array<string, mixed>
     */
    public function defaultLayout(): array
    {
        return [
            'direction' => 'horizontal',
            'node_spacing' => 160,
            'rank_spacing' => 80,
        ];
    }

    public function applyAutoLayout(WorkflowCanvas $canvas): WorkflowCanvas
    {
        $layout = $this->defaultLayout();
        $spacing = (float) ($layout['node_spacing'] ?? 160);
        $nodes = [];
        $index = 0;

        foreach ($canvas->nodes as $node) {
            $nodes[] = new WorkflowCanvasNode(
                id: $node->id,
                type: $node->type,
                label: $node->label,
                x: $index * $spacing,
                y: 100,
                width: $node->width,
                height: $node->height,
                config: $node->config,
                metadata: $node->metadata,
            );
            $index++;
        }

        return new WorkflowCanvas(
            nodes: $nodes,
            edges: $canvas->edges,
            viewport: $canvas->viewport ?? new WorkflowCanvasViewport(),
            metadata: $canvas->metadata,
        );
    }

    public function emptyCanvas(): WorkflowCanvas
    {
        return new WorkflowCanvas(
            nodes: [],
            edges: [],
            viewport: new WorkflowCanvasViewport(),
        );
    }
}
