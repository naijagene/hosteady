<?php

namespace App\Services\Entity;

use App\Models\EntityActivityLog;
use App\Models\EntityComment;
use App\Models\EntityDefinition as EntityDefinitionModel;
use App\Models\EntityTag;
use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;
use App\Modules\Sdk\Entity\Data\EntityReferenceBridge;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class EnterpriseEntitySearchIndexer
{
    public function indexDefinitionBestEffort(EntityDefinitionModel $definition): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $definition->module_key,
                ),
                entityType: 'entity_definition',
                entityPublicId: $definition->public_id,
                displayName: $definition->name,
                keywords: implode(' ', array_filter([$definition->module_key, $definition->entity_key, $definition->name])),
                metadata: [
                    'module_key' => $definition->module_key,
                    'entity_key' => $definition->entity_key,
                    'status' => $definition->status,
                ],
                entityReference: EntityReferenceBridge::fromEntity(
                    $definition->module_key,
                    $definition->entity_key,
                    $definition->public_id,
                    $definition->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexActivityBestEffort(EntityActivityLog $activity, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: 'entity_activity',
                entityPublicId: $activity->public_id,
                displayName: sprintf('%s activity', $activity->action),
                keywords: implode(' ', array_filter([
                    $activity->module_key,
                    $activity->entity_key,
                    $activity->action,
                    $activity->entity_public_id,
                ])),
                metadata: [
                    'module_key' => $activity->module_key,
                    'entity_key' => $activity->entity_key,
                    'action' => $activity->action,
                ],
                entityReference: EntityReferenceBridge::fromEntity(
                    $activity->module_key,
                    $activity->entity_key,
                    $activity->entity_public_id ?? $activity->public_id,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexCommentBestEffort(EntityComment $comment, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: 'entity_comment',
                entityPublicId: $comment->public_id,
                displayName: 'Entity comment',
                keywords: implode(' ', array_filter([
                    $comment->module_key,
                    $comment->entity_key,
                    $comment->entity_public_id,
                    $comment->comment_body,
                ])),
                metadata: [
                    'module_key' => $comment->module_key,
                    'entity_key' => $comment->entity_key,
                    'entity_public_id' => $comment->entity_public_id,
                ],
                entityReference: EntityReferenceBridge::fromEntity(
                    $comment->module_key,
                    $comment->entity_key,
                    $comment->entity_public_id,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexTagBestEffort(EntityTag $tag, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: 'entity_tag',
                entityPublicId: $tag->public_id,
                displayName: $tag->name,
                keywords: implode(' ', array_filter([$tag->tag_key, $tag->name])),
                metadata: [
                    'tag_key' => $tag->tag_key,
                    'color' => $tag->color,
                ],
                entityReference: new \App\Modules\Sdk\Enterprise\Data\EntityReference(
                    type: 'entity_tag',
                    publicId: $tag->public_id,
                    moduleKey: null,
                    label: $tag->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexLifecycleBestEffort(EntityLifecycleEvent $event, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class) || $event->entityPublicId === null) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: sprintf('%s.%s', $event->moduleKey, $event->entityKey),
                entityPublicId: $event->entityPublicId,
                displayName: sprintf('%s.%s', $event->moduleKey, $event->entityKey),
                keywords: implode(' ', array_filter([
                    $event->moduleKey,
                    $event->entityKey,
                    $event->eventType,
                    $event->entityPublicId,
                ])),
                metadata: [
                    'event_type' => $event->eventType,
                    'module_key' => $event->moduleKey,
                    'entity_key' => $event->entityKey,
                ],
                entityReference: EntityReferenceBridge::fromEntity(
                    $event->moduleKey,
                    $event->entityKey,
                    $event->entityPublicId,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }
}
