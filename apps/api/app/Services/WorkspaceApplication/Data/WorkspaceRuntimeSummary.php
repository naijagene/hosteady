<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class WorkspaceRuntimeSummary
{
    public function __construct(
        public int $activeApplicationCount,
        public string $runtimeVersion,
        public int $settingsVersion,
    ) {
    }
}
