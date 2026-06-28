<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiLayoutProvider
{
    /** @return list<\App\Modules\Sdk\Ui\Data\UiLayout> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Ui\Data\UiLayout $layout): \App\Modules\Sdk\Ui\Data\UiLayout;
}
