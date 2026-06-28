<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiPersonalizationProvider
{
    public function get(\App\Support\Tenant\TenantContext $context, string $pagePublicId): \App\Modules\Sdk\Ui\Data\UiPersonalization;

    public function update(\App\Support\Tenant\TenantContext $context, string $pagePublicId, array $personalization): \App\Modules\Sdk\Ui\Data\UiPersonalization;
}
