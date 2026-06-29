<?php

namespace App\Modules\Sdk\Navigation\Contracts;

interface NavigationRenderer
{
    public function render(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $moduleKey = null, bool $previewDraft = false): \App\Modules\Sdk\Navigation\Data\NavigationRenderPayload;
}
