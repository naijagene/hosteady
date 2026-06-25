<?php

namespace App\Services\Module\Data;

readonly class ModuleDependencyGraph
{
    /**
     * @param  array<string, list<string>>  $adjacency
     * @param  list<string>  $topologicalOrder
     * @param  list<string>  $reverseOrder
     * @param  array<string, list<string>>  $dependencyTree
     * @param  list<string>  $cycles
     */
    public function __construct(
        public array $adjacency,
        public array $topologicalOrder,
        public array $reverseOrder,
        public array $dependencyTree,
        public array $cycles,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'adjacency' => $this->adjacency,
            'topological_order' => $this->topologicalOrder,
            'reverse_order' => $this->reverseOrder,
            'dependency_tree' => $this->dependencyTree,
            'cycles' => $this->cycles,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportGraph(): array
    {
        $nodes = array_keys($this->adjacency);
        sort($nodes);

        $edges = [];

        foreach ($this->adjacency as $dependency => $dependents) {
            foreach ($dependents as $dependent) {
                $edges[] = [
                    'from' => $dependency,
                    'to' => $dependent,
                    'type' => 'depends_on',
                ];
            }
        }

        usort($edges, fn (array $left, array $right) => [$left['from'], $left['to']] <=> [$right['from'], $right['to']]);

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'cycles' => $this->cycles,
        ];
    }
}
