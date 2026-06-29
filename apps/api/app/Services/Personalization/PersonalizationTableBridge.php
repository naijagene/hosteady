<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class PersonalizationTableBridge
{
    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function resolve(TenantContext $context, array $preferences): array
    {
        return [
            'table_density' => $preferences['table_density'] ?? null,
            'column_order' => is_array($preferences['table_column_order'] ?? null) ? $preferences['table_column_order'] : [],
        ];
    }
}
