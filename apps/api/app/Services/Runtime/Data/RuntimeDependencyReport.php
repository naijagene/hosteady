<?php

namespace App\Services\Runtime\Data;

use App\Enums\RuntimeHealthStatus;

readonly class RuntimeDependencyReport
{
    /**
     * @param  list<string>  $missingDependencies
     * @param  list<string>  $disabledDependencies
     * @param  list<string>  $circularDependencies
     * @param  list<string>  $duplicateDependencies
     * @param  list<string>  $versionMismatches
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        public RuntimeHealthStatus $status,
        public array $missingDependencies,
        public array $disabledDependencies,
        public array $circularDependencies,
        public array $duplicateDependencies,
        public array $versionMismatches,
        public array $errors,
        public array $warnings,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'status' => $this->status->value,
            'missing_count' => count($this->missingDependencies),
            'disabled_count' => count($this->disabledDependencies),
            'circular_count' => count($this->circularDependencies),
            'duplicate_count' => count($this->duplicateDependencies),
            'version_mismatch_count' => count($this->versionMismatches),
            'missing_dependencies' => $this->missingDependencies,
            'disabled_dependencies' => $this->disabledDependencies,
            'circular_dependencies' => $this->circularDependencies,
            'duplicate_dependencies' => $this->duplicateDependencies,
            'version_mismatches' => $this->versionMismatches,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'missing_dependencies' => $this->missingDependencies,
            'disabled_dependencies' => $this->disabledDependencies,
            'circular_dependencies' => $this->circularDependencies,
            'duplicate_dependencies' => $this->duplicateDependencies,
            'version_mismatches' => $this->versionMismatches,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
