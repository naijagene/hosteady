<?php

namespace App\Modules\Sdk\Runtime;

readonly class RuntimePipelineReport
{
    /**
     * @param  list<RuntimeContributorResult>  $results
     * @param  list<string>  $warnings
     */
    public function __construct(
        public RuntimeContributionCollection $contributions,
        public array $results,
        public array $warnings,
        public float $durationMs,
        public int $executedCount,
        public int $skippedCount,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDiagnosticsSummary(): array
    {
        return [
            'executed' => $this->executedCount,
            'skipped' => $this->skippedCount,
            'warnings' => $this->warnings,
            'duration_ms' => round($this->durationMs, 3),
            'modules' => array_map(
                fn (RuntimeContributorResult $result) => [
                    'module_key' => $result->moduleKey,
                    'success' => $result->success,
                    'skipped' => $result->skipped,
                    'duration_ms' => $result->durationMs,
                    'warnings' => $result->warnings,
                    'error' => $result->error,
                ],
                $this->results,
            ),
        ];
    }
}
