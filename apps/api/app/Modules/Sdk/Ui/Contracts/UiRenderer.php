<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiRenderer
{
    public function render(\App\Support\Tenant\TenantContext $context, string $moduleKey, string $pageKey): \App\Modules\Sdk\Ui\Data\UiRenderPayload;
}
