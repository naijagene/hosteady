<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiPageRegistry
{
    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Ui\Data\UiPageDefinition $definition): \App\Modules\Sdk\Ui\Data\UiPageDefinition;

    /** @return list<\App\Modules\Sdk\Ui\Data\UiPageDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $pageKey): \App\Modules\Sdk\Ui\Data\UiPageDefinition;

    public function findByRoutePath(string $organizationId, ?string $workspaceId, string $routePath): \App\Modules\Sdk\Ui\Data\UiPageDefinition;
}
