<?php

namespace App\Services\Entity;

use App\Modules\Sdk\Entity\Data\EntityReferenceBridge;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileUpdateRequest;
use App\Services\Enterprise\FileMedia\FileQueryService;
use App\Services\Enterprise\FileMedia\FileService;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityAttachmentBridge
{
    public function attachBestEffort(
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $filePublicId,
    ): void {
        try {
            if (! app()->bound(FileService::class) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(FileService::class)->update($context, new FileUpdateRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $moduleKey,
                ),
                filePublicId: $filePublicId,
                entityReference: EntityReferenceBridge::fromEntity(
                    $moduleKey,
                    $entityKey,
                    $entityPublicId,
                ),
            ));
        } catch (\Throwable) {
        }
    }

    public function detachBestEffort(
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $filePublicId,
    ): void {
        try {
            if (! app()->bound(FileQueryService::class) || ! app()->bound(FileService::class) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);
            $scope = new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
                moduleKey: $moduleKey,
            );

            $file = app(FileQueryService::class)->findModel($scope, $filePublicId);
            $reference = is_array($file->entity_reference) ? $file->entity_reference : [];
            $linkedPublicId = $reference['public_id'] ?? $reference['publicId'] ?? null;

            if ($linkedPublicId !== $entityPublicId) {
                return;
            }

            app(FileService::class)->update($context, new FileUpdateRequest(
                scope: $scope,
                filePublicId: $filePublicId,
                entityReference: null,
            ));
        } catch (\Throwable) {
        }
    }
}
