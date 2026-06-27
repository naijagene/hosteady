<?php

namespace App\Services\Entity;

use App\Modules\Sdk\Entity\Contracts\EntityLifecycleHandler;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;
use App\Modules\Sdk\Entity\Enums\EntityLifecycleEventType;
use App\Modules\Sdk\Entity\EnterpriseEntity;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityLifecycleService
{
    public function __construct(
        private readonly EnterpriseEntityRegistryService $registryService,
        private readonly EnterpriseEntityActivityService $activityService,
        private readonly EnterpriseEntityAuditRecorder $auditRecorder,
        private readonly EnterpriseEntitySearchIndexer $searchIndexer,
        private readonly EnterpriseEntityWorkflowBridge $workflowBridge,
    ) {
    }

    public function dispatch(
        TenantContext $context,
        EntityLifecycleEvent $event,
        ?array $beforeState = null,
        ?array $afterState = null,
    ): void {
        $scope = new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            moduleKey: $event->moduleKey,
        );

        $definition = $this->registryService->find($event->moduleKey, $event->entityKey);
        $this->invokeHandlers($event, $definition);

        try {
            $this->activityService->log(
                scope: $scope,
                organizationId: $context->organization->id,
                workspaceId: $context->workspace->id,
                moduleKey: $event->moduleKey,
                entityKey: $event->entityKey,
                action: $event->eventType,
                entityPublicId: $event->entityPublicId,
                beforeState: $beforeState,
                afterState: $afterState,
                actorUserId: $context->user->id,
                actorMembershipId: $context->membership->id,
                metadata: $event->metadata,
            );
        } catch (\Throwable) {
        }

        try {
            $this->auditRecorder->recordLifecycleEvent($event);
        } catch (\Throwable) {
        }

        try {
            $this->searchIndexer->indexLifecycleBestEffort($event, $scope);
        } catch (\Throwable) {
        }

        try {
            $this->workflowBridge->triggerBestEffort(
                $context,
                'entity.lifecycle.'.$event->eventType,
                $event->toArray(),
                $scope,
            );
        } catch (\Throwable) {
        }
    }

    public function dispatchCommented(
        EnterpriseScope $scope,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $commentPublicId,
    ): void {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->dispatch(app(TenantContext::class), new EntityLifecycleEvent(
            eventType: EntityLifecycleEventType::Commented->value,
            moduleKey: $moduleKey,
            entityKey: $entityKey,
            entityPublicId: $entityPublicId,
            payload: ['comment_public_id' => $commentPublicId],
        ));
    }

    public function dispatchTagged(
        EnterpriseScope $scope,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $tagPublicId,
    ): void {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->dispatch(app(TenantContext::class), new EntityLifecycleEvent(
            eventType: EntityLifecycleEventType::Tagged->value,
            moduleKey: $moduleKey,
            entityKey: $entityKey,
            entityPublicId: $entityPublicId,
            payload: ['tag_public_id' => $tagPublicId],
        ));

        try {
            $this->workflowBridge->triggerBestEffort(
                app(TenantContext::class),
                'entity.tag.attached',
                [
                    'module_key' => $moduleKey,
                    'entity_key' => $entityKey,
                    'entity_public_id' => $entityPublicId,
                    'tag_public_id' => $tagPublicId,
                ],
                $scope,
            );
        } catch (\Throwable) {
        }
    }

    public function dispatchUntagged(
        EnterpriseScope $scope,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $tagPublicId,
    ): void {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $this->dispatch(app(TenantContext::class), new EntityLifecycleEvent(
            eventType: EntityLifecycleEventType::Untagged->value,
            moduleKey: $moduleKey,
            entityKey: $entityKey,
            entityPublicId: $entityPublicId,
            payload: ['tag_public_id' => $tagPublicId],
        ));
    }

    private function invokeHandlers(EntityLifecycleEvent $event, ?EntityDefinition $definition): void
    {
        if ($definition?->className === null || ! class_exists($definition->className)) {
            return;
        }

        if (! is_subclass_of($definition->className, EnterpriseEntity::class)) {
            return;
        }

        try {
            /** @var EnterpriseEntity $entityClass */
            $entityClass = app($definition->className);

            foreach ($entityClass->lifecycleHandlers() as $handler) {
                $resolved = is_string($handler) ? app($handler) : $handler;

                if ($resolved instanceof EntityLifecycleHandler) {
                    $resolved->handle($event);
                }
            }
        } catch (\Throwable) {
        }
    }
}
