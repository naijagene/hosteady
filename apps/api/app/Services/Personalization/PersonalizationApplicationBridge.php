<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class PersonalizationApplicationBridge
{
    public function resolve(TenantContext $context, array $runtimePayload): array
    {
        return [
            'personalization' => $runtimePayload,
            'source' => 'personalization_application_bridge',
        ];
    }
}
