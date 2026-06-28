<?php

namespace App\Services\Document;

use App\Models\EnterpriseAttachment;
use App\Models\EnterpriseDocument;
use App\Models\EnterpriseDocumentVersion;
use App\Models\PlatformFile;
use App\Modules\Sdk\Document\Data\DocumentQuotaReport;
use App\Modules\Sdk\Document\Exceptions\DocumentQuotaException;

class EnterpriseDocumentQuotaService
{
    public function report(?object $organization = null, ?object $workspace = null): DocumentQuotaReport
    {
        $documentsQuery = EnterpriseDocument::query();
        $attachmentsQuery = EnterpriseAttachment::query();
        $versionsQuery = EnterpriseDocumentVersion::query();

        if ($organization !== null) {
            $documentsQuery->where('organization_id', $organization->id);
            $attachmentsQuery->where('organization_id', $organization->id);
            $versionsQuery->where('organization_id', $organization->id);
        }

        if ($workspace !== null) {
            EnterpriseDocumentMapper::applyWorkspaceScope($documentsQuery, $workspace->id);
            EnterpriseDocumentMapper::applyWorkspaceScope($attachmentsQuery, $workspace->id);
            EnterpriseDocumentMapper::applyWorkspaceScope($versionsQuery, $workspace->id);
        }

        $platformFileIds = $versionsQuery->pluck('platform_file_id')->filter()->unique()->values();
        $totalBytes = $platformFileIds->isEmpty()
            ? 0
            : (int) PlatformFile::query()->whereIn('id', $platformFileIds)->sum('size_bytes');

        $quotaBytes = (int) config('heos.enterprise.documents.quota_bytes', 5_368_709_120);

        return new DocumentQuotaReport(
            documentsCount: $documentsQuery->count(),
            attachmentsCount: $attachmentsQuery->count(),
            totalBytes: $totalBytes,
            quotaBytes: $quotaBytes,
            metadata: [
                'within_quota' => $totalBytes <= $quotaBytes,
            ],
        );
    }

    public function assertWithinQuota(?object $organization = null, ?object $workspace = null): void
    {
        $report = $this->report($organization, $workspace);

        if ($report->totalBytes > $report->quotaBytes) {
            throw new DocumentQuotaException('Document storage quota has been exceeded.');
        }
    }
}
