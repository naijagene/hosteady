<?php

namespace App\Modules\Sdk\Runtime;

use App\Modules\Sdk\Contracts\ModuleRuntimeContext;
use App\Modules\Sdk\Contracts\RuntimeModuleContributor;

class RuntimeContributorPipeline
{
    /**
     * @param  list<RuntimeModuleContributor>  $contributors
     */
    public function execute(ModuleRuntimeContext $context, array $contributors): RuntimePipelineReport
    {
        $startedAt = microtime(true);
        $warnings = [];
        $results = [];
        $collection = new RuntimeContributionCollection;

        [$orderedContributors, $orderingWarnings, $skippedKeys] = $this->orderContributors($contributors);
        $warnings = [...$warnings, ...$orderingWarnings];

        $executionList = $this->buildExecutionList($contributors, $orderedContributors);

        foreach ($executionList as $contributor) {
            if (in_array($contributor->moduleKey(), $skippedKeys, true)) {
                $results[] = new RuntimeContributorResult(
                    moduleKey: $contributor->moduleKey(),
                    success: false,
                    contribution: null,
                    durationMs: 0.0,
                    warnings: ['Contributor skipped due to dependency graph issues.'],
                    skipped: true,
                );

                continue;
            }

            $resultStartedAt = microtime(true);

            try {
                $contribution = $contributor->contribute($context);
                $this->validateContribution($contribution, $contributor->moduleKey());
                $collection = $collection->add($contribution);

                $results[] = new RuntimeContributorResult(
                    moduleKey: $contributor->moduleKey(),
                    success: true,
                    contribution: $contribution,
                    durationMs: round((microtime(true) - $resultStartedAt) * 1000, 3),
                    warnings: $contribution->warnings,
                );
            } catch (\Throwable $exception) {
                $results[] = new RuntimeContributorResult(
                    moduleKey: $contributor->moduleKey(),
                    success: false,
                    contribution: null,
                    durationMs: round((microtime(true) - $resultStartedAt) * 1000, 3),
                    warnings: [],
                    error: $exception->getMessage(),
                );

                $warnings[] = sprintf(
                    'Contributor "%s" failed: %s',
                    $contributor->moduleKey(),
                    $exception->getMessage(),
                );
            }
        }

        $executedCount = count(array_filter(
            $results,
            fn (RuntimeContributorResult $result) => $result->success && ! $result->skipped,
        ));
        $skippedCount = count(array_filter(
            $results,
            fn (RuntimeContributorResult $result) => $result->skipped || ! $result->success,
        ));

        return new RuntimePipelineReport(
            contributions: $collection,
            results: $results,
            warnings: $warnings,
            durationMs: round((microtime(true) - $startedAt) * 1000, 3),
            executedCount: $executedCount,
            skippedCount: $skippedCount,
        );
    }

    private function validateContribution(RuntimeContribution $contribution, string $expectedModuleKey): void
    {
        if ($contribution->moduleKey !== $expectedModuleKey) {
            throw new RuntimeContributionException(sprintf(
                'Contribution module_key "%s" does not match contributor "%s".',
                $contribution->moduleKey,
                $expectedModuleKey,
            ));
        }
    }

    /**
     * @param  list<RuntimeModuleContributor>  $contributors
     * @return array{0: list<RuntimeModuleContributor>, 1: list<string>, 2: list<string>}
     */
    private function orderContributors(array $contributors): array
    {
        $warnings = [];
        $skippedKeys = [];
        $byKey = [];

        foreach ($contributors as $contributor) {
            $byKey[$contributor->moduleKey()] = $contributor;
        }

        $inDegree = [];
        $dependents = [];

        foreach ($contributors as $contributor) {
            $key = $contributor->moduleKey();
            $inDegree[$key] ??= 0;
            $dependents[$key] ??= [];

            foreach ($contributor->dependencyKeys() as $dependency) {
                if (! isset($byKey[$dependency])) {
                    $warnings[] = sprintf(
                        'Contributor "%s" depends on missing module "%s".',
                        $key,
                        $dependency,
                    );

                    continue;
                }

                $dependents[$dependency][] = $key;
                $inDegree[$key] = ($inDegree[$key] ?? 0) + 1;
            }
        }

        $ready = array_values(array_filter(
            array_keys($inDegree),
            fn (string $key) => $inDegree[$key] === 0,
        ));

        usort($ready, fn (string $left, string $right) => $this->compareContributors($byKey[$left], $byKey[$right]));

        $sortedKeys = [];

        while ($ready !== []) {
            $current = array_shift($ready);
            $sortedKeys[] = $current;

            foreach ($dependents[$current] ?? [] as $dependent) {
                $inDegree[$dependent]--;

                if ($inDegree[$dependent] === 0) {
                    $ready[] = $dependent;
                }
            }

            usort($ready, fn (string $left, string $right) => $this->compareContributors($byKey[$left], $byKey[$right]));
        }

        if (count($sortedKeys) !== count($inDegree)) {
            foreach (array_keys($inDegree) as $moduleKey) {
                if (! in_array($moduleKey, $sortedKeys, true)) {
                    $warnings[] = sprintf('Circular dependency detected involving "%s".', $moduleKey);
                    $skippedKeys[] = $moduleKey;
                }
            }
        }

        $orderedContributors = [];

        foreach ($sortedKeys as $moduleKey) {
            $orderedContributors[] = $byKey[$moduleKey];
        }

        return [$orderedContributors, $warnings, array_values(array_unique($skippedKeys))];
    }

    /**
     * @param  list<RuntimeModuleContributor>  $contributors
     * @param  list<RuntimeModuleContributor>  $orderedContributors
     * @return list<RuntimeModuleContributor>
     */
    private function buildExecutionList(array $contributors, array $orderedContributors): array
    {
        $orderedKeys = array_map(
            fn (RuntimeModuleContributor $contributor) => $contributor->moduleKey(),
            $orderedContributors,
        );

        $remaining = array_values(array_filter(
            $contributors,
            fn (RuntimeModuleContributor $contributor) => ! in_array($contributor->moduleKey(), $orderedKeys, true),
        ));

        return [...$orderedContributors, ...$remaining];
    }

    private function compareContributors(RuntimeModuleContributor $left, RuntimeModuleContributor $right): int
    {
        return [$left->priority(), $left->moduleKey()] <=> [$right->priority(), $right->moduleKey()];
    }
}
