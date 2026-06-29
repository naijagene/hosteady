<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class PersonalizationThemeBridge
{
    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function resolve(TenantContext $context, array $preferences): array
    {
        if (! isset($preferences['theme_public_id'])) {
            return [];
        }

        return [
            'theme_public_id' => (string) $preferences['theme_public_id'],
            'source' => 'personalization_preference',
        ];
    }
}
