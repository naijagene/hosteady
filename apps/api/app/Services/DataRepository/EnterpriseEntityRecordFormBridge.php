<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Enums\FormType;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordFormBridge
{
    public function __construct(
        private readonly EnterpriseEntityRecordMutationService $mutationService,
    ) {
    }

    public function mutateFromForm(
        TenantContext $context,
        FormDefinition $definition,
        array $values,
        ?string $recordPublicId = null,
    ): ?string {
        if ($definition->entityKey === null) {
            return $recordPublicId;
        }

        if (! EnterpriseEntityRecordMapper::entityBindingEnabled($definition->metadata)) {
            return $recordPublicId;
        }

        $mode = (string) ($definition->metadata['entity_binding']['mode'] ?? match ($definition->type) {
            FormType::Create->value => 'create',
            FormType::Edit->value => 'update',
            default => '',
        });

        if ($mode === 'create') {
            $result = $this->mutationService->create(
                $context->organization->id,
                $context->workspace?->id,
                new EntityRecordCreateRequest(
                    moduleKey: $definition->moduleKey,
                    entityKey: $definition->entityKey,
                    values: $values,
                ),
            );

            return $result->recordPublicId;
        }

        if ($mode === 'update' && $recordPublicId !== null) {
            $result = $this->mutationService->update(
                $context->organization->id,
                $context->workspace?->id,
                new EntityRecordUpdateRequest(
                    moduleKey: $definition->moduleKey,
                    entityKey: $definition->entityKey,
                    recordPublicId: $recordPublicId,
                    values: $values,
                ),
            );

            return $result->recordPublicId;
        }

        return $recordPublicId;
    }
}
