<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Contracts\RuleContextProvider;
use App\Modules\Sdk\Rules\Data\RuleContext;
use App\Support\Tenant\TenantContext;

class RuleContextService implements RuleContextProvider
{
    public function __construct(
        private readonly RuleFactService $factService,
    ) {
    }

    public function build(TenantContext $context, string $triggerType, array $facts = [], array $metadata = []): RuleContext
    {
        return new RuleContext(
            organizationId: $context->organization->id,
            workspaceId: $context->workspace?->id,
            moduleKey: $metadata['module_key'] ?? null,
            entityKey: $metadata['entity_key'] ?? null,
            triggerType: $triggerType,
            subjectPublicId: $metadata['subject_public_id'] ?? null,
            facts: $this->factService->factsFromArray($facts),
            metadata: $metadata,
        );
    }
}
