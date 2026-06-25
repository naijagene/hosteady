<?php

namespace App\Services\Runtime\Data;

use App\Services\WorkspaceApplication\Data\ResolvedWorkspaceApplication;

readonly class RuntimeManifest
{
    /**
     * @param  list<array<string, mixed>>  $fingerprintApplications
     * @param  list<ResolvedWorkspaceApplication>  $applications
     * @param  array<string, ResolvedWorkspaceApplication>  $applicationsByPublicId
     */
    public function __construct(
        public array $fingerprintApplications,
        public array $applications,
        public array $applicationsByPublicId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fingerprint(): array
    {
        return [
            'applications' => $this->fingerprintApplications,
        ];
    }
}
