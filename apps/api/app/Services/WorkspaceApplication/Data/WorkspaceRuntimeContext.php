<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class WorkspaceRuntimeContext
{
    /**
     * @param  list<ResolvedWorkspaceApplication>  $activeApplications
     * @param  array{generated_at: string, generated_by: string, schema_version: int}  $runtimeMetadata
     * @param  array<string, bool>  $capabilities
     * @param  list<array<string, mixed>>  $navigation
     * @param  array<string, mixed>  $featureFlags
     * @param  list<array<string, mixed>>  $moduleDiagnostics
     * @param  array<string, mixed>  $settingsMetadata
     */
    public function __construct(
        public RuntimeOrganizationSnapshot $organization,
        public RuntimeWorkspaceSnapshot $workspace,
        public RuntimeMembershipSnapshot $membership,
        public array $activeApplications,
        public ?ResolvedWorkspaceApplication $activeApplication,
        public string $runtimeVersion,
        public int $settingsVersion,
        public array $runtimeMetadata,
        public array $capabilities,
        public array $navigation = [],
        public array $featureFlags = [],
        public array $moduleDiagnostics = [],
        public array $settingsMetadata = [],
    ) {
    }
}
