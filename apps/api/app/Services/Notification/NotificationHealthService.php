<?php

namespace App\Services\Notification;

use App\Modules\Sdk\Notification\Data\NotificationHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class NotificationHealthService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'enterprise_notifications',
        'notification_deliveries',
        'notification_templates',
        'notification_preferences',
        'notification_subscriptions',
        'notification_schedules',
        'notification_digests',
        'notification_activity_logs',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly NotificationStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): NotificationHealthReport
    {
        return $this->healthReport($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.notifications.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            self::REQUIRED_TABLES,
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->fallbackAssessment($enabled),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );

        return [
            'enabled' => (bool) config('heos.enterprise.notifications.enabled', true),
            'notifications' => $stats->notifications,
            'deliveries' => $stats->deliveries,
            'templates' => $stats->templates,
            'subscriptions' => $stats->subscriptions,
            'schedules' => $stats->schedules,
            'digests' => $stats->digests,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $report = $this->healthReport($context);
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $report->enabled,
            'notifications' => $report->notifications,
            'deliveries' => $report->deliveries,
            'warnings' => $report->warnings,
            'status' => $report->status,
        ];

        if ($missingTables !== []) {
            $assessment['missing_tables'] = $missingTables;
        }

        return $assessment;
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackAssessment(bool $enabled): array
    {
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $enabled,
            'notifications' => 0,
            'deliveries' => 0,
            'warnings' => array_map(
                fn (string $table): string => $this->tableGuard->missingTableWarning($table),
                $missingTables,
            ),
            'status' => 'warning',
        ];

        if ($missingTables !== []) {
            $assessment['missing_tables'] = $missingTables;
        }

        return $assessment;
    }

    private function healthReport(?TenantContext $context): NotificationHealthReport
    {
        $enabled = (bool) config('heos.enterprise.notifications.enabled', true);
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);
        $warnings = array_map(
            fn (string $table): string => $this->tableGuard->missingTableWarning($table),
            $missingTables,
        );
        $status = 'healthy';

        if (! $enabled) {
            $warnings[] = 'Enterprise notification platform is disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $missingTables === [] && $stats->notifications === 0) {
            $warnings[] = 'No notifications have been sent yet.';
            $status = 'warning';
        }

        if ($missingTables !== []) {
            $status = 'warning';
        }

        return new NotificationHealthReport(
            enabled: $enabled,
            notifications: $stats->notifications,
            deliveries: $stats->deliveries,
            warnings: $warnings,
            status: $status,
            missingTables: $missingTables,
        );
    }
}
