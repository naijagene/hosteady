<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleSetProvider
{
    /** @return list<\App\Modules\Sdk\Rules\Data\RuleSetDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?\App\Modules\Sdk\Rules\Data\RuleSetDefinition;

    public function create(string $organizationId, ?string $workspaceId, \App\Modules\Sdk\Rules\Data\RuleSetDefinition $definition): \App\Modules\Sdk\Rules\Data\RuleSetDefinition;
}
