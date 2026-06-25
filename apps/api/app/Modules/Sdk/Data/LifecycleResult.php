<?php

namespace App\Modules\Sdk\Data;

use App\Modules\Sdk\Lifecycle\LifecycleOperation;

readonly class LifecycleResult
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    public function __construct(
        public bool $success,
        public float $durationMs,
        public string $moduleKey,
        public LifecycleOperation $operation,
        public array $warnings,
        public array $errors,
        public ?LifecycleExecution $execution = null,
    ) {
    }

    public static function skipped(string $moduleKey, LifecycleOperation $operation): self
    {
        return new self(
            success: true,
            durationMs: 0.0,
            moduleKey: $moduleKey,
            operation: $operation,
            warnings: ['Module is not registered; lifecycle skipped.'],
            errors: [],
        );
    }

    public static function success(
        string $moduleKey,
        LifecycleOperation $operation,
        LifecycleExecution $execution,
        array $warnings = [],
    ): self {
        return new self(
            success: true,
            durationMs: $execution->durationMs,
            moduleKey: $moduleKey,
            operation: $operation,
            warnings: $warnings,
            errors: [],
            execution: $execution,
        );
    }

    public static function failed(
        string $moduleKey,
        LifecycleOperation $operation,
        \Throwable $exception,
        ?LifecycleExecution $execution = null,
    ): self {
        return new self(
            success: false,
            durationMs: $execution?->durationMs ?? 0.0,
            moduleKey: $moduleKey,
            operation: $operation,
            warnings: [],
            errors: [$exception->getMessage()],
            execution: $execution,
        );
    }
}
