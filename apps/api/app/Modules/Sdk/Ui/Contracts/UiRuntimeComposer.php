<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiRuntimeComposer
{
    public function compose(\App\Support\Tenant\TenantContext $context): \App\Modules\Sdk\Ui\Data\UiRenderPayload;

    /** @return array<string, mixed> */
    public function runtimeMetadata(\App\Support\Tenant\TenantContext $context): array;
}
