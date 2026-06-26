<?php

namespace App\Services\Enterprise\FileMedia;

use App\Enums\FileVisibility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PlatformFile;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileDownloadResult;
use App\Modules\Sdk\Enterprise\Data\FileReference;
use App\Modules\Sdk\Enterprise\Data\FileUpdateRequest;
use App\Modules\Sdk\Enterprise\Data\FileUploadRequest;
use App\Services\Enterprise\Audit\EnterpriseFileAuditRecorder;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class FileVisibilityResolver
{
    public function canAccess(
        PlatformFile $file,
        TenantContext $context,
        bool $hasReadPermission,
    ): bool {
        if (! $hasReadPermission) {
            return false;
        }

        if ($file->organization_id !== $context->organization->id) {
            return false;
        }

        return match ($file->visibility) {
            FileVisibility::Private => $file->uploaded_membership_id === $context->membership->id,
            FileVisibility::Workspace => $file->workspace_id === null
                || $file->workspace_id === $context->workspace->id,
            FileVisibility::Organization, FileVisibility::Public => true,
        };
    }

    public function canManage(PlatformFile $file, TenantContext $context, bool $hasManagePermission): bool
    {
        if (! $hasManagePermission) {
            return $file->uploaded_membership_id === $context->membership->id;
        }

        return $file->organization_id === $context->organization->id;
    }
}
