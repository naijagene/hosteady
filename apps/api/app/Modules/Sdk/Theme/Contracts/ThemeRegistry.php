<?php

namespace App\Modules\Sdk\Theme\Contracts;

interface ThemeRegistry
{
    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Theme\Data\ThemeDefinition $definition): \App\Modules\Sdk\Theme\Data\ThemeDefinition;

    /** @return list<\App\Modules\Sdk\Theme\Data\ThemeDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $themeKey): \App\Modules\Sdk\Theme\Data\ThemeDefinition;
}
