<?php

namespace App\Services\Entity;

use App\Modules\Sdk\Entity\Contracts\EntityRepository;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;
use App\Modules\Sdk\Entity\Data\EntityMutationRequest;
use App\Modules\Sdk\Entity\Data\EntityMutationResult;
use App\Modules\Sdk\Entity\Data\EntityValidationReport;
use App\Modules\Sdk\Entity\Enums\EntityLifecycleEventType;
use App\Modules\Sdk\Entity\Exceptions\EntityNotFoundException;
use App\Modules\Sdk\Entity\Exceptions\EntityValidationException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseEntityRepositoryService implements EntityRepository
{
    public function __construct(
        private readonly EnterpriseEntityRegistryService $registryService,
        private readonly EnterpriseEntityValidationService $validator,
        private readonly EnterpriseEntityLifecycleService $lifecycleService,
        private readonly EnterpriseEntityAttachmentBridge $attachmentBridge,
    ) {
    }

    public function resolveDefinition(string $moduleKey, string $entityKey): EntityDefinition
    {
        $definition = $this->registryService->find($moduleKey, $entityKey);

        if ($definition === null) {
            throw new EntityNotFoundException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        return $definition;
    }

    public function validateMutation(EntityMutationRequest $request): EntityValidationReport
    {
        $definition = $this->resolveDefinition($request->moduleKey, $request->entityKey);

        return $this->validator->validateMutation($request, $definition);
    }

    public function mutate(EntityMutationRequest $request): EntityMutationResult
    {
        $report = $this->validateMutation($request);

        if (! $report->valid) {
            throw new EntityValidationException(sprintf(
                'Entity mutation for [%s.%s] is invalid.',
                $request->moduleKey,
                $request->entityKey,
            ));
        }

        $entityPublicId = $request->operation === 'create'
            ? (string) Str::uuid7()
            : $request->entityPublicId;
        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        $beforeEvent = match ($request->operation) {
            'create' => EntityLifecycleEventType::Creating,
            'update' => EntityLifecycleEventType::Updating,
            'delete' => EntityLifecycleEventType::Deleting,
            'restore' => EntityLifecycleEventType::Restoring,
            default => EntityLifecycleEventType::Updating,
        };

        $afterEvent = match ($request->operation) {
            'create' => EntityLifecycleEventType::Created,
            'update' => EntityLifecycleEventType::Updated,
            'delete' => EntityLifecycleEventType::Deleted,
            'restore' => EntityLifecycleEventType::Restored,
            default => EntityLifecycleEventType::Updated,
        };

        if ($context !== null) {
            $this->lifecycleService->dispatch($context, new EntityLifecycleEvent(
                eventType: $beforeEvent->value,
                moduleKey: $request->moduleKey,
                entityKey: $request->entityKey,
                entityPublicId: $entityPublicId,
                payload: $request->attributes,
                metadata: $request->metadata,
            ), beforeState: null, afterState: null);

            $this->lifecycleService->dispatch($context, new EntityLifecycleEvent(
                eventType: $afterEvent->value,
                moduleKey: $request->moduleKey,
                entityKey: $request->entityKey,
                entityPublicId: $entityPublicId,
                payload: $request->attributes,
                metadata: $request->metadata,
            ), beforeState: null, afterState: $request->attributes);

            if (isset($request->metadata['file_public_id'])) {
                $this->attachmentBridge->attachBestEffort(
                    $request->moduleKey,
                    $request->entityKey,
                    $entityPublicId,
                    (string) $request->metadata['file_public_id'],
                );
            }
        }

        return new EntityMutationResult(
            moduleKey: $request->moduleKey,
            entityKey: $request->entityKey,
            operation: $request->operation,
            success: true,
            entityPublicId: $entityPublicId,
            attributes: $request->attributes,
        );
    }
}
