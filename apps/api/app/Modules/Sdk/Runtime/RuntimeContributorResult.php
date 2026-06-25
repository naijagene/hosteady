<?php

namespace App\Modules\Sdk\Runtime;

readonly class RuntimeContributorResult
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $moduleKey,
        public bool $success,
        public ?RuntimeContribution $contribution,
        public float $durationMs,
        public array $warnings = [],
        public ?string $error = null,
        public bool $skipped = false,
    ) {
    }
}
