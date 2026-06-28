<?php

namespace App\Modules\Sdk\Ui\Contracts;

interface UiConditionEvaluator
{
    /**
     * @param  array<int, array<string, mixed>>  $conditions
     * @param  array<string, mixed>  $values
     */
    public function evaluate(\App\Support\Tenant\TenantContext $context, array $conditions, array $values = []): bool;
}
