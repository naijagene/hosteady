<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleRegistry
{
    /** @return list<\App\Modules\Sdk\Rules\Data\RuleDefinition> */
    public function listEnabled(string $organizationId, ?string $workspaceId, string $triggerType, ?string $moduleKey = null, ?string $entityKey = null): array;

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?\App\Modules\Sdk\Rules\Data\RuleDefinition;
}
