<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Contracts\EntityRecordLifecycleDispatcher;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;
use App\Modules\Sdk\Entity\Enums\EntityLifecycleEventType;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Services\Entity\EnterpriseEntityLifecycleService;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordLifecycleService implements EntityRecordLifecycleDispatcher
{
    public function __construct(
        private readonly EnterpriseEntityRecordActivityService $activityService,
        private readonly EnterpriseEntityRecordAuditRecorder $auditRecorder,
        private readonly EnterpriseEntityRecordSearchIndexer $searchIndexer,
        private readonly EnterpriseEntityRecordWorkflowBridge $workflowBridge,
        private readonly EnterpriseEntityLifecycleService $entityLifecycleService,
    ) {
    }

    public function dispatchCreated(TenantContext $context, EntityRecord $record, array $beforeState = []): void
    {
        $this->dispatchRecordLifecycle($context, $record, EntityLifecycleEventType::Created, $beforeState, $record->recordData->values);
        $this->dispatchCommon($context, $record, 'created', $beforeState, $record->recordData->values);
        $this->auditRecorder->recordCreated($record);
    }

    public function dispatchUpdated(TenantContext $context, EntityRecord $record, array $beforeState, array $afterState): void
    {
        $this->dispatchRecordLifecycle($context, $record, EntityLifecycleEventType::Updated, $beforeState, $afterState);
        $this->dispatchCommon($context, $record, 'updated', $beforeState, $afterState);
        $this->auditRecorder->recordUpdated($record);
        $this->auditRecorder->recordVersioned($record);
    }

    public function dispatchDeleted(TenantContext $context, EntityRecord $record, array $beforeState): void
    {
        $this->dispatchRecordLifecycle($context, $record, EntityLifecycleEventType::Deleted, $beforeState, []);
        $this->dispatchCommon($context, $record, 'deleted', $beforeState, []);
        $this->auditRecorder->recordDeleted($record);
    }

    public function dispatchRestored(TenantContext $context, EntityRecord $record, array $beforeState): void
    {
        $this->dispatchRecordLifecycle($context, $record, EntityLifecycleEventType::Restored, $beforeState, $record->recordData->values);
        $this->dispatchCommon($context, $record, 'restored', $beforeState, $record->recordData->values);
        $this->auditRecorder->recordRestored($record);
    }

    public function dispatchVersioned(TenantContext $context, EntityRecord $record, int $versionNumber): void
    {
        try {
            $this->auditRecorder->recordVersioned($record);
        } catch (\Throwable) {
        }
    }

    public function dispatchLinked(TenantContext $context, array $link): void
    {
        try {
            $this->auditRecorder->recordLinked($link);
            $this->workflowBridge->triggerBestEffort($context, 'data.record.linked', $link);
        } catch (\Throwable) {
        }
    }

    public function dispatchUnlinked(TenantContext $context, array $link): void
    {
        try {
            $this->auditRecorder->recordUnlinked($link);
            $this->workflowBridge->triggerBestEffort($context, 'data.record.unlinked', $link);
        } catch (\Throwable) {
        }
    }

    public function dispatchActivityLogged(TenantContext $context, array $activity): void
    {
        try {
            $this->workflowBridge->triggerBestEffort($context, 'data.record.activity.logged', $activity);
        } catch (\Throwable) {
        }
    }

    public function dispatchQueried(TenantContext $context, string $moduleKey, string $entityKey, int $total): void
    {
        try {
            $this->auditRecorder->recordQueried($moduleKey, $entityKey, $total);
        } catch (\Throwable) {
        }
    }

    private function dispatchCommon(
        TenantContext $context,
        EntityRecord $record,
        string $action,
        ?array $beforeState,
        ?array $afterState,
    ): void {
        try {
            $this->activityService->log(
                organizationId: $context->organization->id,
                workspaceId: $context->workspace?->id,
                moduleKey: $record->moduleKey,
                entityKey: $record->entityKey,
                recordPublicId: $record->publicId,
                action: $action,
                beforeState: $beforeState,
                afterState: $afterState,
                userId: $context->user->id,
                membershipId: $context->membership->id,
            );
        } catch (\Throwable) {
        }

        try {
            $scope = new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $record->moduleKey,
            );
            $this->searchIndexer->indexRecordBestEffort($record, $scope);
        } catch (\Throwable) {
        }

        try {
            $this->workflowBridge->triggerBestEffort(
                $context,
                'data.record.'.$action,
                $record->toArray(),
            );
        } catch (\Throwable) {
        }

        try {
            app(\App\Services\Notification\NotificationEntityBridge::class)->notifyRecordEventBestEffort(
                $context,
                $action,
                new \App\Modules\Sdk\DataRepository\Data\EntityRecordReference(
                    publicId: $record->publicId,
                    moduleKey: $record->moduleKey,
                    entityKey: $record->entityKey,
                    status: $record->status,
                ),
            );
        } catch (\Throwable) {
        }

        try {
            app(\App\Services\Integration\IntegrationEntityBridge::class)->publishRecordEventBestEffort(
                $context,
                $action,
                new \App\Modules\Sdk\DataRepository\Data\EntityRecordReference(
                    publicId: $record->publicId,
                    moduleKey: $record->moduleKey,
                    entityKey: $record->entityKey,
                    status: $record->status,
                ),
                $afterState ?? [],
            );
        } catch (\Throwable) {
        }
    }

    private function dispatchRecordLifecycle(
        TenantContext $context,
        EntityRecord $record,
        EntityLifecycleEventType $eventType,
        ?array $beforeState,
        ?array $afterState,
    ): void {
        try {
            $this->entityLifecycleService->dispatch($context, new EntityLifecycleEvent(
                eventType: $eventType->value,
                moduleKey: $record->moduleKey,
                entityKey: $record->entityKey,
                entityPublicId: $record->publicId,
                payload: $record->recordData->values,
            ), beforeState: $beforeState, afterState: $afterState);
        } catch (\Throwable) {
        }
    }
}
