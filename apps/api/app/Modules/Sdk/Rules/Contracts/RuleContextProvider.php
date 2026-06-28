<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleContextProvider
{
    public function build(\App\Support\Tenant\TenantContext $context, string $triggerType, array $facts = [], array $metadata = []): \App\Modules\Sdk\Rules\Data\RuleContext;
}
