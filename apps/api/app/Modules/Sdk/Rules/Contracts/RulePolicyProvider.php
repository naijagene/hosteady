<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RulePolicyProvider
{
    public function canEvaluate(\App\Support\Tenant\TenantContext $context): bool;

    public function canExecute(\App\Support\Tenant\TenantContext $context): bool;

    public function canManage(\App\Support\Tenant\TenantContext $context): bool;
}
