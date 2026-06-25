<?php

namespace App\Services\Runtime\Data;

use App\Enums\RuntimeHealthStatus;

readonly class WorkspaceRuntimeHealth
{
    /**
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public RuntimeHealthStatus $health,
        public WorkspaceRuntimeDiagnostics $diagnostics,
        public RuntimeIntegrityReport $integrity,
        public RuntimeCacheDiagnostics $cache,
        public array $dependencySummary,
        public array $recommendations,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'health' => $this->health->value,
            'diagnostics' => $this->diagnostics->toArray(),
            'integrity' => $this->integrity->toArray(),
            'cache' => $this->cache->toArray(),
            'dependency_summary' => $this->dependencySummary,
            'recommendations' => $this->recommendations,
        ];
    }
}
