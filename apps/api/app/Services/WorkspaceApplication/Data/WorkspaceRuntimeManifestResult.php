<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class WorkspaceRuntimeManifestResult
{
    /**
     * @param  array<string, mixed>  $manifest
     * @param  list<ResolvedWorkspaceApplication>  $applications
     * @param  array<string, ResolvedWorkspaceApplication>  $applicationsByPublicId
     */
    public function __construct(
        public array $manifest,
        public array $applications,
        public array $applicationsByPublicId,
    ) {
    }
}
