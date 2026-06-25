<?php

namespace App\Modules\Sdk\Contracts;

interface ModuleHealthContext
{
    public function organizationPublicId(): string;

    public function workspacePublicId(): ?string;
}
