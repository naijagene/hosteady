<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowDesignerDiff implements \JsonSerializable
{
    /**
     * @param  list<array<string, mixed>>  $addedNodes
     * @param  list<array<string, mixed>>  $removedNodes
     * @param  list<array<string, mixed>>  $movedNodes
     * @param  list<array<string, mixed>>  $changedNodes
     * @param  list<array<string, mixed>>  $addedEdges
     * @param  list<array<string, mixed>>  $removedEdges
     * @param  list<array<string, mixed>>  $changedEdges
     * @param  array<string, mixed>  $metadataChanges
     */
    public function __construct(
        public string $fromSnapshotPublicId,
        public string $toSnapshotPublicId,
        public array $addedNodes = [],
        public array $removedNodes = [],
        public array $movedNodes = [],
        public array $changedNodes = [],
        public array $addedEdges = [],
        public array $removedEdges = [],
        public array $changedEdges = [],
        public array $metadataChanges = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from_snapshot_public_id' => $this->fromSnapshotPublicId,
            'to_snapshot_public_id' => $this->toSnapshotPublicId,
            'added_nodes' => $this->addedNodes,
            'removed_nodes' => $this->removedNodes,
            'moved_nodes' => $this->movedNodes,
            'changed_nodes' => $this->changedNodes,
            'added_edges' => $this->addedEdges,
            'removed_edges' => $this->removedEdges,
            'changed_edges' => $this->changedEdges,
            'metadata_changes' => $this->metadataChanges,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
