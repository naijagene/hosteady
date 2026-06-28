<?php

namespace App\Services\Document;

use App\Models\EnterpriseAttachment;
use App\Models\EnterpriseDocument;
use App\Models\EnterpriseDocumentActivity;
use App\Models\EnterpriseDocumentOcrResult;
use App\Models\EnterpriseDocumentPreview;
use App\Models\EnterpriseDocumentScan;
use App\Models\EnterpriseDocumentThumbnail;
use App\Models\EnterpriseDocumentVersion;
use App\Modules\Sdk\Document\Data\DocumentStatistics;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class EnterpriseDocumentStatisticsService
{
    public function statisticsForScope(?object $organization = null, ?object $workspace = null): DocumentStatistics
    {
        $documentsQuery = EnterpriseDocument::query();
        $versionsQuery = EnterpriseDocumentVersion::query();
        $attachmentsQuery = EnterpriseAttachment::query();
        $previewsQuery = EnterpriseDocumentPreview::query();
        $scansQuery = EnterpriseDocumentScan::query();
        $ocrQuery = EnterpriseDocumentOcrResult::query();
        $activityQuery = EnterpriseDocumentActivity::query();

        if ($organization !== null) {
            $documentsQuery->where('organization_id', $organization->id);
            $versionsQuery->where('organization_id', $organization->id);
            $attachmentsQuery->where('organization_id', $organization->id);
            $previewsQuery->where('organization_id', $organization->id);
            $scansQuery->where('organization_id', $organization->id);
            $ocrQuery->where('organization_id', $organization->id);
            $activityQuery->where('organization_id', $organization->id);
        }

        if ($workspace !== null) {
            EnterpriseDocumentMapper::applyWorkspaceScope($documentsQuery, $workspace->id);
            EnterpriseDocumentMapper::applyWorkspaceScope($versionsQuery, $workspace->id);
            EnterpriseDocumentMapper::applyWorkspaceScope($attachmentsQuery, $workspace->id);
            EnterpriseDocumentMapper::applyWorkspaceScope($previewsQuery, $workspace->id);
            EnterpriseDocumentMapper::applyWorkspaceScope($scansQuery, $workspace->id);
            EnterpriseDocumentMapper::applyWorkspaceScope($ocrQuery, $workspace->id);
            EnterpriseDocumentMapper::applyWorkspaceScope($activityQuery, $workspace->id);
        }

        return new DocumentStatistics(
            documents: $documentsQuery->count(),
            versions: $versionsQuery->count(),
            attachments: $attachmentsQuery->count(),
            previews: $previewsQuery->count(),
            scans: $scansQuery->count(),
            ocrResults: $ocrQuery->count(),
            activityLogs: $activityQuery->count(),
        );
    }
}
