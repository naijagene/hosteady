<?php

namespace App\Services\Module;

use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\ModuleRegistry;
use App\Services\Module\Data\ModuleDependencyGraph;

class ModuleDependencyGraphService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {
    }

    public function build(): ModuleDependencyGraph
    {
        $modules = $this->registry->all();
        $adjacency = $this->buildAdjacency($modules);
        $dependencyGraph = $this->buildDependencyGraph($modules);
        $cycles = $this->detectCycles($dependencyGraph);
        $topologicalOrder = $this->topologicalOrder($modules, $adjacency);
        $reverseOrder = array_reverse($topologicalOrder);
        $dependencyTree = $this->buildDependencyTree($modules);

        return new ModuleDependencyGraph(
            adjacency: $adjacency,
            topologicalOrder: $topologicalOrder,
            reverseOrder: $reverseOrder,
            dependencyTree: $dependencyTree,
            cycles: $cycles,
        );
    }

    /**
     * @param  list<ApplicationModule>  $modules
     * @return array<string, list<string>>
     */
    private function buildAdjacency(array $modules): array
    {
        $adjacency = [];

        foreach ($modules as $module) {
            $adjacency[$module->key()] ??= [];
        }

        foreach ($modules as $module) {
            foreach ($module->manifest()->dependencies as $dependency) {
                $adjacency[$dependency->key] ??= [];
                $adjacency[$dependency->key][] = $module->key();
            }
        }

        foreach ($adjacency as $dependency => $dependents) {
            $dependents = array_values(array_unique($dependents));
            sort($dependents);
            $adjacency[$dependency] = $dependents;
        }

        ksort($adjacency);

        return $adjacency;
    }

    /**
     * @param  list<ApplicationModule>  $modules
     * @return array<string, list<string>>
     */
    private function buildDependencyGraph(array $modules): array
    {
        $graph = [];

        foreach ($modules as $module) {
            $graph[$module->key()] = array_map(
                fn ($dependency) => $dependency->key,
                $module->manifest()->dependencies,
            );
        }

        ksort($graph);

        return $graph;
    }

    /**
     * @param  array<string, list<string>>  $dependencyGraph
     * @return list<string>
     */
    private function detectCycles(array $dependencyGraph): array
    {
        $cycles = [];

        foreach (array_keys($dependencyGraph) as $node) {
            if ($this->hasCycle($node, $dependencyGraph, [])) {
                $cycles[] = $node;
            }
        }

        sort($cycles);

        return array_values(array_unique($cycles));
    }

    /**
     * @param  array<string, list<string>>  $dependencyGraph
     * @param  list<string>  $stack
     */
    private function hasCycle(string $node, array $dependencyGraph, array $stack): bool
    {
        if (in_array($node, $stack, true)) {
            return true;
        }

        $stack[] = $node;

        foreach ($dependencyGraph[$node] ?? [] as $dependency) {
            if (! isset($dependencyGraph[$dependency])) {
                continue;
            }

            if ($this->hasCycle($dependency, $dependencyGraph, $stack)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<ApplicationModule>  $modules
     * @param  array<string, list<string>>  $adjacency
     * @return list<string>
     */
    private function topologicalOrder(array $modules, array $adjacency): array
    {
        $inDegree = [];
        $byKey = [];

        foreach ($modules as $module) {
            $key = $module->key();
            $byKey[$key] = $module;
            $inDegree[$key] = 0;
        }

        foreach ($modules as $module) {
            foreach ($module->manifest()->dependencies as $dependency) {
                if (! isset($byKey[$dependency->key])) {
                    continue;
                }

                $inDegree[$module->key()] = ($inDegree[$module->key()] ?? 0) + 1;
            }
        }

        $ready = array_values(array_filter(
            array_keys($inDegree),
            fn (string $key) => $inDegree[$key] === 0,
        ));

        usort($ready, fn (string $left, string $right) => $left <=> $right);

        $sorted = [];

        while ($ready !== []) {
            $current = array_shift($ready);
            $sorted[] = $current;

            foreach ($adjacency[$current] ?? [] as $dependent) {
                if (! isset($inDegree[$dependent])) {
                    continue;
                }

                $inDegree[$dependent]--;

                if ($inDegree[$dependent] === 0) {
                    $ready[] = $dependent;
                }
            }

            usort($ready, fn (string $left, string $right) => $left <=> $right);
        }

        return $sorted;
    }

    /**
     * @param  list<ApplicationModule>  $modules
     * @return array<string, list<string>>
     */
    private function buildDependencyTree(array $modules): array
    {
        $tree = [];

        foreach ($modules as $module) {
            $tree[$module->key()] = array_map(
                fn ($dependency) => $dependency->key,
                $module->manifest()->dependencies,
            );

            sort($tree[$module->key()]);
        }

        ksort($tree);

        return $tree;
    }
}
