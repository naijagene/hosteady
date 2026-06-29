<?php

namespace App\Modules\Sdk\Navigation\Contracts;

interface NavigationVisibilityResolver
{
    public function isVisible(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Navigation\Data\NavigationItem $item): bool;

    public function evaluate(\App\Support\Tenant\TenantContext $context, array $conditions, array $values = []): bool;
}
