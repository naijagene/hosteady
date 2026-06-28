<?php

namespace App\Services\Notification;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class NotificationSearchIndexer
{
    public function indexBestEffort(NotificationReference $notification, TenantContext $context): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: is_string($notification->metadata['module_key'] ?? null)
                        ? $notification->metadata['module_key']
                        : null,
                ),
                entityType: 'enterprise_notification',
                entityPublicId: $notification->publicId,
                displayName: $notification->title,
                keywords: implode(' ', array_filter([
                    $notification->title,
                    $notification->body,
                    $notification->publicId,
                    $notification->templateKey,
                ])),
                metadata: [
                    'status' => $notification->status,
                    'scope' => $notification->scope,
                    'priority' => $notification->priority,
                ],
                entityReference: null,
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }
}
