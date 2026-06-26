<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerDiff;

class WorkflowCanvasDiffService
{
    public function diff(
        string $fromPublicId,
        string $toPublicId,
        WorkflowCanvas $fromCanvas,
        WorkflowCanvas $toCanvas,
    ): WorkflowDesignerDiff {
        $fromNodes = $this->indexNodes($fromCanvas);
        $toNodes = $this->indexNodes($toCanvas);
        $fromEdges = $this->indexEdges($fromCanvas);
        $toEdges = $this->indexEdges($toCanvas);

        $addedNodes = [];
        $removedNodes = [];
        $movedNodes = [];
        $changedNodes = [];

        foreach ($toNodes as $id => $node) {
            if (! isset($fromNodes[$id])) {
                $addedNodes[] = $node->toArray();
            }
        }

        foreach ($fromNodes as $id => $node) {
            if (! isset($toNodes[$id])) {
                $removedNodes[] = $node->toArray();
            }
        }

        foreach ($toNodes as $id => $toNode) {
            if (! isset($fromNodes[$id])) {
                continue;
            }

            $fromNode = $fromNodes[$id];

            if ($fromNode->x !== $toNode->x || $fromNode->y !== $toNode->y) {
                $movedNodes[] = [
                    'id' => $id,
                    'from' => ['x' => $fromNode->x, 'y' => $fromNode->y],
                    'to' => ['x' => $toNode->x, 'y' => $toNode->y],
                ];
            }

            if ($fromNode->type !== $toNode->type
                || $fromNode->label !== $toNode->label
                || $fromNode->config !== $toNode->config
                || $fromNode->width !== $toNode->width
                || $fromNode->height !== $toNode->height) {
                $changedNodes[] = [
                    'id' => $id,
                    'from' => $fromNode->toArray(),
                    'to' => $toNode->toArray(),
                ];
            }
        }

        $addedEdges = [];
        $removedEdges = [];
        $changedEdges = [];

        foreach ($toEdges as $id => $edge) {
            if (! isset($fromEdges[$id])) {
                $addedEdges[] = $edge->toArray();
            }
        }

        foreach ($fromEdges as $id => $edge) {
            if (! isset($toEdges[$id])) {
                $removedEdges[] = $edge->toArray();
            }
        }

        foreach ($toEdges as $id => $toEdge) {
            if (! isset($fromEdges[$id])) {
                continue;
            }

            $fromEdge = $fromEdges[$id];

            if ($fromEdge->source !== $toEdge->source
                || $fromEdge->target !== $toEdge->target
                || $fromEdge->label !== $toEdge->label
                || $fromEdge->condition !== $toEdge->condition) {
                $changedEdges[] = [
                    'id' => $id,
                    'from' => $fromEdge->toArray(),
                    'to' => $toEdge->toArray(),
                ];
            }
        }

        $metadataChanges = $this->diffMetadata($fromCanvas, $toCanvas);

        return new WorkflowDesignerDiff(
            fromSnapshotPublicId: $fromPublicId,
            toSnapshotPublicId: $toPublicId,
            addedNodes: $addedNodes,
            removedNodes: $removedNodes,
            movedNodes: $movedNodes,
            changedNodes: $changedNodes,
            addedEdges: $addedEdges,
            removedEdges: $removedEdges,
            changedEdges: $changedEdges,
            metadataChanges: $metadataChanges,
        );
    }

    /**
     * @return array<string, \App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasNode>
     */
    private function indexNodes(WorkflowCanvas $canvas): array
    {
        $indexed = [];

        foreach ($canvas->nodes as $node) {
            $indexed[$node->id] = $node;
        }

        return $indexed;
    }

    /**
     * @return array<string, \App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasEdge>
     */
    private function indexEdges(WorkflowCanvas $canvas): array
    {
        $indexed = [];

        foreach ($canvas->edges as $edge) {
            $indexed[$edge->id] = $edge;
        }

        return $indexed;
    }

    /**
     * @return array<string, mixed>
     */
    private function diffMetadata(WorkflowCanvas $from, WorkflowCanvas $to): array
    {
        $fromMeta = $from->metadata?->toArray() ?? [];
        $toMeta = $to->metadata?->toArray() ?? [];
        $changes = [];

        foreach (array_unique(array_merge(array_keys($fromMeta), array_keys($toMeta))) as $key) {
            $fromValue = $fromMeta[$key] ?? null;
            $toValue = $toMeta[$key] ?? null;

            if ($fromValue !== $toValue) {
                $changes[$key] = ['from' => $fromValue, 'to' => $toValue];
            }
        }

        return $changes;
    }
}
