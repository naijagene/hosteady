<?php

namespace App\Modules\Sdk\Data;

use App\Modules\Sdk\Lifecycle\LifecycleOperation;

readonly class LifecycleExecution
{
    public function __construct(
        public string $moduleKey,
        public LifecycleOperation $operation,
        public float $startedAt,
        public float $finishedAt,
        public float $durationMs,
        public string $status,
        public ?string $exceptionMessage = null,
    ) {
    }
}
