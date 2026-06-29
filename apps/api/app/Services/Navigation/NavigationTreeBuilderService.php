<?php

namespace App\Services\Navigation;

use App\Modules\Sdk\Navigation\Contracts\NavigationTreeBuilder;
use App\Modules\Sdk\Navigation\Data\NavigationItem;
use App\Modules\Sdk\Navigation\Data\NavigationTree;
use App\Modules\Sdk\Navigation\Data\NavigationTreeNode;

class NavigationTreeBuilderService implements NavigationTreeBuilder
{
    public function __construct(
        private readonly NavigationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function emptyTree(): NavigationTree
    {
        return new NavigationTree(
            nodes: [],
            warnings: $this->tableHealthSupport->warningsForCoreTables(),
        );
    }

    /** @param  list<NavigationItem>  $items */
    public function build(array $items): NavigationTree
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->emptyTree();
        }

        $warnings = [];
        $byPublicId = [];
        $byParent = [];
        $childrenOf = [];

        foreach ($items as $item) {
            if (isset($byPublicId[$item->publicId])) {
                $warnings[] = sprintf('Duplicate navigation item public id [%s].', $item->publicId);

                continue;
            }

            $byPublicId[$item->publicId] = $item;
            $parentKey = $item->parentItemPublicId ?? '_root';
            $byParent[$parentKey] ??= [];
            $byParent[$parentKey][] = $item;
            $childrenOf[$item->publicId] = $item->parentItemPublicId;
        }

        foreach ($childrenOf as $itemId => $parentId) {
            if ($parentId === null || $parentId === '') {
                continue;
            }

            if (! isset($byPublicId[$parentId])) {
                $warnings[] = sprintf('Navigation item [%s] references missing parent [%s].', $itemId, $parentId);
            }

            if ($this->hasCycle($itemId, $parentId, $childrenOf)) {
                $warnings[] = sprintf('Navigation item cycle detected at [%s].', $itemId);
            }
        }

        foreach ($byParent as &$siblings) {
            usort($siblings, fn (NavigationItem $a, NavigationItem $b) => $a->sortOrder <=> $b->sortOrder
                ?: strcmp($a->label, $b->label));
        }
        unset($siblings);

        $nodes = $this->buildNodes($byParent, '_root', 0, [], $warnings);

        return new NavigationTree(
            nodes: array_map(fn (NavigationTreeNode $node) => $node->toArray(), $nodes),
            warnings: $warnings,
        );
    }

    /**
     * @param  array<string, list<NavigationItem>>  $byParent
     * @param  list<string>  $path
     * @return list<NavigationTreeNode>
     */
    private function buildNodes(array $byParent, string $parentKey, int $depth, array $path, array &$warnings): array
    {
        $nodes = [];

        foreach ($byParent[$parentKey] ?? [] as $item) {
            if (in_array($item->publicId, $path, true)) {
                $warnings[] = sprintf('Skipping cyclic navigation item [%s].', $item->publicId);

                continue;
            }

            $nextPath = [...$path, $item->publicId];
            $childNodes = $this->buildNodes($byParent, $item->publicId, $depth + 1, $nextPath, $warnings);

            $nodes[] = new NavigationTreeNode(
                item: $item->toArray(),
                children: array_map(fn (NavigationTreeNode $node) => $node->toArray(), $childNodes),
                depth: $depth,
            );
        }

        return $nodes;
    }

    /**
     * @param  array<string, ?string>  $childrenOf
     */
    private function hasCycle(string $itemId, ?string $parentId, array $childrenOf): bool
    {
        $visited = [];
        $current = $parentId;

        while ($current !== null && $current !== '') {
            if ($current === $itemId) {
                return true;
            }

            if (isset($visited[$current])) {
                return true;
            }

            $visited[$current] = true;
            $current = $childrenOf[$current] ?? null;
        }

        return false;
    }
}
