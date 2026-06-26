<?php

namespace App\Services\Enterprise\Workflow\Designer;

use App\Modules\Sdk\Workflow\Designer\Contracts\WorkflowCanvasNormalizer;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasEdge;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasNode;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerMetadata;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowDesignerIssueSeverity;
use Illuminate\Support\Str;

class WorkflowCanvasNormalizationService implements WorkflowCanvasNormalizer
{
    /**
     * @param  list<array<string, mixed>>  $definitionNodes
     * @return array{canvas: WorkflowCanvas, warnings: list<array<string, mixed>>}
     */
    public function normalize(WorkflowCanvas $canvas, array $definitionNodes = []): array
    {
        $warnings = [];
        $nodes = $canvas->nodes;
        $edges = $canvas->edges;

        $nodes = $this->ensureUniqueNodeIds($nodes, $warnings);
        $edges = $this->removeOrphanEdges($edges, $nodes, $warnings);
        $edges = $this->ensureUniqueEdgeIds($edges, $warnings);

        if ($definitionNodes !== []) {
            [$nodes, $definitionWarnings] = $this->syncWithDefinitionNodes($nodes, $definitionNodes);
            $warnings = array_merge($warnings, $definitionWarnings);
        }

        $metadata = $canvas->metadata ?? new WorkflowDesignerMetadata(
            designerVersion: '1.0',
            lastSavedAt: now()->toIso8601String(),
        );

        return [
            'canvas' => new WorkflowCanvas(
                nodes: $nodes,
                edges: $edges,
                viewport: $canvas->viewport,
                metadata: $metadata,
            ),
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<WorkflowCanvasNode>  $nodes
     * @param  list<array<string, mixed>>  $warnings
     * @return list<WorkflowCanvasNode>
     */
    private function ensureUniqueNodeIds(array $nodes, array &$warnings): array
    {
        $seen = [];
        $normalized = [];

        foreach ($nodes as $node) {
            $id = $node->id;

            if (isset($seen[$id])) {
                $newId = $id.'_'.Str::lower(Str::random(4));
                $warnings[] = [
                    'severity' => WorkflowDesignerIssueSeverity::Warning->value,
                    'code' => 'duplicate_node_id',
                    'message' => sprintf('Duplicate node id [%s] renamed to [%s].', $id, $newId),
                    'node_id' => $id,
                ];
                $id = $newId;
            }

            $seen[$id] = true;
            $normalized[] = new WorkflowCanvasNode(
                id: $id,
                type: $node->type,
                label: $node->label,
                x: $node->x,
                y: $node->y,
                width: $node->width,
                height: $node->height,
                config: $node->config,
                metadata: $node->metadata,
            );
        }

        return $normalized;
    }

    /**
     * @param  list<WorkflowCanvasEdge>  $edges
     * @param  list<WorkflowCanvasNode>  $nodes
     * @param  list<array<string, mixed>>  $warnings
     * @return list<WorkflowCanvasEdge>
     */
    private function removeOrphanEdges(array $edges, array $nodes, array &$warnings): array
    {
        $nodeIds = array_flip(array_map(fn (WorkflowCanvasNode $node) => $node->id, $nodes));
        $valid = [];

        foreach ($edges as $edge) {
            if (! isset($nodeIds[$edge->source]) || ! isset($nodeIds[$edge->target])) {
                $warnings[] = [
                    'severity' => WorkflowDesignerIssueSeverity::Warning->value,
                    'code' => 'orphan_edge_removed',
                    'message' => sprintf('Edge [%s] removed because source or target node is missing.', $edge->id),
                    'edge_id' => $edge->id,
                ];

                continue;
            }

            $valid[] = $edge;
        }

        return $valid;
    }

    /**
     * @param  list<WorkflowCanvasEdge>  $edges
     * @param  list<array<string, mixed>>  $warnings
     * @return list<WorkflowCanvasEdge>
     */
    private function ensureUniqueEdgeIds(array $edges, array &$warnings): array
    {
        $seen = [];
        $normalized = [];

        foreach ($edges as $edge) {
            $id = $edge->id;

            if (isset($seen[$id])) {
                $newId = $id.'_'.Str::lower(Str::random(4));
                $warnings[] = [
                    'severity' => WorkflowDesignerIssueSeverity::Warning->value,
                    'code' => 'duplicate_edge_id',
                    'message' => sprintf('Duplicate edge id [%s] renamed to [%s].', $id, $newId),
                    'edge_id' => $id,
                ];
                $id = $newId;
            }

            $seen[$id] = true;
            $normalized[] = new WorkflowCanvasEdge(
                id: $id,
                source: $edge->source,
                target: $edge->target,
                label: $edge->label,
                condition: $edge->condition,
                metadata: $edge->metadata,
            );
        }

        return $normalized;
    }

    /**
     * @param  list<WorkflowCanvasNode>  $nodes
     * @param  list<array<string, mixed>>  $definitionNodes
     * @return array{0: list<WorkflowCanvasNode>, 1: list<array<string, mixed>>}
     */
    private function syncWithDefinitionNodes(array $nodes, array $definitionNodes): array
    {
        $warnings = [];
        $canvasById = [];

        foreach ($nodes as $node) {
            $canvasById[$node->id] = $node;
        }

        $synced = [];

        foreach ($definitionNodes as $defNode) {
            $id = (string) $defNode['id'];

            if (isset($canvasById[$id])) {
                $synced[] = $canvasById[$id];
                unset($canvasById[$id]);

                continue;
            }

            $warnings[] = [
                'severity' => WorkflowDesignerIssueSeverity::Info->value,
                'code' => 'definition_node_added',
                'message' => sprintf('Definition node [%s] added to canvas with default layout.', $id),
                'node_id' => $id,
            ];

            $synced[] = new WorkflowCanvasNode(
                id: $id,
                type: (string) ($defNode['type'] ?? 'task'),
                label: isset($defNode['label']) ? (string) $defNode['label'] : null,
                x: count($synced) * 160,
                y: 100,
            );
        }

        foreach ($canvasById as $orphan) {
            $warnings[] = [
                'severity' => WorkflowDesignerIssueSeverity::Warning->value,
                'code' => 'canvas_node_orphan',
                'message' => sprintf('Canvas node [%s] is not present in workflow definition.', $orphan->id),
                'node_id' => $orphan->id,
            ];
            $synced[] = $orphan;
        }

        return [$synced, $warnings];
    }
}
