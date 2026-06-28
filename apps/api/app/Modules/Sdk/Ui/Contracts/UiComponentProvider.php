<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiComponentProvider
{
    /** @return list<\App\Modules\Sdk\Ui\Data\UiComponent> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Ui\Data\UiComponent $component): \App\Modules\Sdk\Ui\Data\UiComponent;
}
