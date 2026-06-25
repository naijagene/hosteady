<?php

namespace App\Modules\Sdk\Runtime;

use App\Modules\Sdk\Contracts\ModuleRuntimeContext;
use App\Modules\Sdk\Contracts\RuntimeModuleContributor;

class RuntimeExtensionManager
{
    private ?RuntimePipelineReport $lastPipelineReport = null;

    public function __construct(
        private readonly RuntimeContributorPipeline $pipeline,
    ) {
    }

    /**
     * @param  list<RuntimeModuleContributor>  $contributors
     */
    public function resolve(ModuleRuntimeContext $context, array $contributors): RuntimePipelineReport
    {
        $report = $this->pipeline->execute($context, $contributors);
        $this->lastPipelineReport = $report;

        return $report;
    }

    public function lastPipelineReport(): ?RuntimePipelineReport
    {
        return $this->lastPipelineReport;
    }

    /**
     * @param  array<string, bool>  $platformCapabilities
     * @return array<string, bool>
     */
    public function mergeCapabilities(array $platformCapabilities, RuntimeContributionCollection $contributions): array
    {
        $merged = $platformCapabilities;

        foreach ($contributions->merge()['capabilities'] as $capability) {
            $merged[$capability] = true;
        }

        ksort($merged);

        return $merged;
    }
}
