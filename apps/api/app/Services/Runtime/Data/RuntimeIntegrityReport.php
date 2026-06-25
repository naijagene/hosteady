<?php

namespace App\Services\Runtime\Data;

use App\Enums\RuntimeHealthStatus;

readonly class RuntimeIntegrityReport
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        public RuntimeHealthStatus $status,
        public bool $fingerprintValid,
        public array $errors,
        public array $warnings,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'fingerprint_valid' => $this->fingerprintValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
