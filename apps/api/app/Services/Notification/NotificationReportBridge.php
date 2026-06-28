<?php

namespace App\Services\Notification;

use App\Models\ReportDefinition;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Support\Tenant\TenantContext;

class NotificationReportBridge
{
    public function __construct(
        private readonly EnterpriseNotificationService $notificationService,
    ) {
    }

    public function notifyExportReadyBestEffort(
        TenantContext $context,
        string $moduleKey,
        string $reportKey,
        string $exportPublicId,
        ?string $recipientMembershipPublicId = null,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            $this->notificationService->send($context, new NotificationMessage(
                title: 'Report export ready',
                body: sprintf('Export for report [%s.%s] is ready.', $moduleKey, $reportKey),
                scope: 'user',
                priority: 'normal',
                templateKey: null,
                mergeData: [
                    'report_name' => $reportKey,
                ],
                channels: ['in_app'],
                recipientMembershipPublicId: $recipientMembershipPublicId ?? $context->membershipPublicId,
                recipientMembershipPublicIds: [],
                rolePublicId: null,
                moduleKey: $moduleKey,
                metadata: [
                    'event' => 'export_ready',
                    'export_public_id' => $exportPublicId,
                    'report_key' => $reportKey,
                    'type' => 'report.export_ready',
                ],
            ));
        } catch (\Throwable) {
        }
    }

    public function notifyReportEventBestEffort(
        TenantContext $context,
        string $eventName,
        ReportDefinition $definition,
        ?string $recipientMembershipPublicId = null,
    ): void {
        try {
            if (! (bool) config('heos.enterprise.notifications.enabled', true)) {
                return;
            }

            $reportName = $definition->name ?? $definition->report_key;

            $this->notificationService->send($context, new NotificationMessage(
                title: sprintf('Report: %s', $reportName),
                body: sprintf('Report [%s] triggered event [%s].', $reportName, $eventName),
                scope: $recipientMembershipPublicId !== null ? 'user' : 'organization',
                priority: 'normal',
                templateKey: null,
                mergeData: [
                    'report_name' => $reportName,
                ],
                channels: ['in_app'],
                recipientMembershipPublicId: $recipientMembershipPublicId ?? $context->membershipPublicId,
                recipientMembershipPublicIds: [],
                rolePublicId: null,
                moduleKey: $definition->module_key,
                metadata: [
                    'event' => $eventName,
                    'report_key' => $definition->report_key,
                    'type' => 'report.'.$eventName,
                ],
            ));
        } catch (\Throwable) {
        }
    }
}
