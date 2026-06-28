<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Contracts\EntityRecordMutationHandler;
use App\Modules\Sdk\DataRepository\Contracts\EntityRecordRepository;
use App\Modules\Sdk\DataRepository\Contracts\EntityRecordValidator;
use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordDeleteRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordMutationResult;
use App\Modules\Sdk\DataRepository\Data\EntityRecordRestoreRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Modules\Sdk\DataRepository\Enums\EntityRecordMutationType;
use App\Modules\Sdk\DataRepository\Exceptions\EntityRecordNotFoundException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;

class EnterpriseEntityRecordMutationService implements EntityRecordMutationHandler
{
    public function __construct(
        private readonly EntityRecordRepository $repository,
        private readonly EntityRecordValidator $validator,
        private readonly EnterpriseEntityRecordLifecycleService $lifecycleService,
        private readonly EnterpriseEntityRecordVersionService $versionService,
        private readonly \App\Services\Rules\RuleDataRepositoryBridge $ruleDataRepositoryBridge,
    ) {
    }

    public function create(string $organizationId, ?string $workspaceId, EntityRecordCreateRequest $request): EntityRecordMutationResult
    {
        $definition = $this->repository->resolveDefinition($request->moduleKey, $request->entityKey);
        $report = $this->validator->validateCreate($request, $definition);
        $this->validator->assertValid($report);

        $this->dispatchIfBound(fn (TenantContext $context) => $this->ruleDataRepositoryBridge->assertAllowedBeforeMutation(
            $context,
            'entity_creating',
            $request->moduleKey,
            $request->entityKey,
            null,
            $request->values,
        ));

        return DB::transaction(function () use ($organizationId, $workspaceId, $request) {
            $record = $this->repository->create($organizationId, $workspaceId, $request);
            $this->versionService->snapshot($record, 'create');
            $this->dispatchIfBound(fn (TenantContext $context) => $this->lifecycleService->dispatchCreated(
                $context,
                $record,
            ));
            $this->dispatchIfBound(fn (TenantContext $context) => $this->ruleDataRepositoryBridge->dispatchAfterMutationBestEffort(
                $context,
                'entity_created',
                $record,
            ));

            return new EntityRecordMutationResult(
                moduleKey: $record->moduleKey,
                entityKey: $record->entityKey,
                mutationType: EntityRecordMutationType::Create->value,
                success: true,
                recordPublicId: $record->publicId,
                record: $record,
            );
        });
    }

    public function update(string $organizationId, ?string $workspaceId, EntityRecordUpdateRequest $request): EntityRecordMutationResult
    {
        $definition = $this->repository->resolveDefinition($request->moduleKey, $request->entityKey);
        $existing = $this->repository->find($organizationId, $workspaceId, $request->moduleKey, $request->entityKey, $request->recordPublicId);

        if ($existing === null) {
            throw new EntityRecordNotFoundException(sprintf('Entity record [%s] was not found.', $request->recordPublicId));
        }

        $beforeState = $existing->recordData->values;
        $report = $this->validator->validateUpdate($request, $definition, $beforeState);
        $this->validator->assertValid($report);

        $this->dispatchIfBound(fn (TenantContext $context) => $this->ruleDataRepositoryBridge->assertAllowedBeforeMutation(
            $context,
            'entity_updating',
            $request->moduleKey,
            $request->entityKey,
            $request->recordPublicId,
            $request->values,
        ));

        return DB::transaction(function () use ($organizationId, $workspaceId, $request, $beforeState) {
            $record = $this->repository->update($organizationId, $workspaceId, $request);
            $this->versionService->snapshot($record, 'update');
            $this->dispatchIfBound(fn (TenantContext $context) => $this->lifecycleService->dispatchUpdated(
                $context,
                $record,
                $beforeState,
                $record->recordData->values,
            ));
            $this->dispatchIfBound(fn (TenantContext $context) => $this->ruleDataRepositoryBridge->dispatchAfterMutationBestEffort(
                $context,
                'entity_updated',
                $record,
            ));

            return new EntityRecordMutationResult(
                moduleKey: $record->moduleKey,
                entityKey: $record->entityKey,
                mutationType: EntityRecordMutationType::Update->value,
                success: true,
                recordPublicId: $record->publicId,
                record: $record,
            );
        });
    }

    public function delete(string $organizationId, ?string $workspaceId, EntityRecordDeleteRequest $request): EntityRecordMutationResult
    {
        $existing = $this->repository->find($organizationId, $workspaceId, $request->moduleKey, $request->entityKey, $request->recordPublicId);

        if ($existing === null) {
            throw new EntityRecordNotFoundException(sprintf('Entity record [%s] was not found.', $request->recordPublicId));
        }

        $beforeState = $existing->recordData->values;

        $this->dispatchIfBound(fn (TenantContext $context) => $this->ruleDataRepositoryBridge->assertAllowedBeforeMutation(
            $context,
            'entity_deleting',
            $request->moduleKey,
            $request->entityKey,
            $request->recordPublicId,
            $beforeState,
        ));

        return DB::transaction(function () use ($organizationId, $workspaceId, $request, $beforeState) {
            $record = $this->repository->delete($organizationId, $workspaceId, $request);
            $this->dispatchIfBound(fn (TenantContext $context) => $this->lifecycleService->dispatchDeleted(
                $context,
                $record,
                $beforeState,
            ));
            $this->dispatchIfBound(fn (TenantContext $context) => $this->ruleDataRepositoryBridge->dispatchAfterMutationBestEffort(
                $context,
                'entity_deleted',
                $record,
            ));

            return new EntityRecordMutationResult(
                moduleKey: $record->moduleKey,
                entityKey: $record->entityKey,
                mutationType: EntityRecordMutationType::Delete->value,
                success: true,
                recordPublicId: $record->publicId,
                record: $record,
            );
        });
    }

    public function restore(string $organizationId, ?string $workspaceId, EntityRecordRestoreRequest $request): EntityRecordMutationResult
    {
        return DB::transaction(function () use ($organizationId, $workspaceId, $request) {
            $record = $this->repository->restore($organizationId, $workspaceId, $request);
            $this->dispatchIfBound(fn (TenantContext $context) => $this->lifecycleService->dispatchRestored(
                $context,
                $record,
                [],
            ));

            return new EntityRecordMutationResult(
                moduleKey: $record->moduleKey,
                entityKey: $record->entityKey,
                mutationType: EntityRecordMutationType::Restore->value,
                success: true,
                recordPublicId: $record->publicId,
                record: $record,
            );
        });
    }

    private function dispatchIfBound(callable $callback): void
    {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $callback(app(TenantContext::class));
    }
}
