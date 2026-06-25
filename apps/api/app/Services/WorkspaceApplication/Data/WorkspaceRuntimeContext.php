<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class WorkspaceRuntimeContext
{
    /**
     * @param  list<ResolvedWorkspaceApplication>  $activeApplications
     * @param  array{generated_at: string, generated_by: string, schema_version: int}  $runtimeMetadata
     * @param  array{audit: bool, settings: bool, workspace: bool, notifications: bool, automation: bool}  $capabilities
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
    ) {
    }
}
