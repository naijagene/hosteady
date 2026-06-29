<?php

namespace App\Modules\Sdk\Navigation\Contracts;

interface NavigationDraftManager
{
    public function saveDraft(\App\Support\Tenant\TenantContext $context, string $navigationKey, array $structure, ?string $moduleKey = null): \App\Modules\Sdk\Navigation\Data\NavigationVersion;

    public function loadDraft(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $moduleKey = null): ?\App\Modules\Sdk\Navigation\Data\NavigationVersion;

    public function discardDraft(\App\Support\Tenant\TenantContext $context, string $navigationKey, ?string $moduleKey = null): void;
}
