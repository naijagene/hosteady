<?php

namespace App\Modules\Sdk\Contracts;

/**
 * Minimal runtime context for reserved lifecycle hooks.
 */
interface ModuleRuntimeContext
{
    public function organizationPublicId(): string;

    public function workspacePublicId(): ?string;
}
