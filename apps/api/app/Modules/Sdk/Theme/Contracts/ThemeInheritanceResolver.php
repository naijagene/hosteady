<?php

namespace App\Modules\Sdk\Theme\Contracts;

interface ThemeInheritanceResolver
{
    /**
     * @return array{theme: array<string, mixed>, warnings: list<string>}
     */
    public function resolve(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Theme\Data\ThemeDefinition $definition, ?\App\Modules\Sdk\Theme\Data\ThemeVersion $version = null): array;
}
