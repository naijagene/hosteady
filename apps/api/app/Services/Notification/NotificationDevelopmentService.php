<?php

namespace App\Services\Notification;

use App\Modules\Sdk\Notification\Contracts\NotificationDeliveryTracker;
use App\Modules\Sdk\Notification\Contracts\NotificationPort;
use App\Modules\Sdk\Notification\Contracts\NotificationPreferenceProvider;
use App\Modules\Sdk\Notification\Contracts\NotificationQueue;
use App\Modules\Sdk\Notification\Contracts\NotificationScheduler;
use App\Modules\Sdk\Notification\Contracts\NotificationTemplateProvider;
use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use App\Modules\Sdk\Notification\Data\NotificationDigest;
use App\Modules\Sdk\Notification\Data\NotificationHealthReport;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Modules\Sdk\Notification\Data\NotificationPreference;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Data\NotificationSchedule;
use App\Modules\Sdk\Notification\Data\NotificationStatistics;
use App\Modules\Sdk\Notification\Data\NotificationTemplate;
use App\Modules\Sdk\Notification\Exceptions\NotificationNotFoundException;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotificationDevelopmentService
{
    public function __construct(
        private readonly NotificationPort $notificationPort,
        private readonly NotificationTemplateProvider $templateProvider,
        private readonly NotificationPreferenceProvider $preferenceProvider,
        private readonly NotificationDeliveryTracker $deliveryTracker,
        private readonly NotificationQueue $notificationQueue,
        private readonly NotificationScheduler $notificationScheduler,
        private readonly NotificationDigestService $digestService,
        private readonly NotificationHealthService $healthService,
        private readonly NotificationStatisticsService $statisticsService,
        private readonly NotificationAuditRecorder $auditRecorder,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly EnterpriseNotificationPermissionService $permissionService,
    ) {
    }

    /**
     * @return list<NotificationReference>
     */
    public function list(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->notificationPort->list($context, $limit);
    }

    public function show(TenantContext $context, string $notificationPublicId): NotificationReference
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $notification = $this->notificationPort->find($context, $notificationPublicId);

        if ($notification === null) {
            throw new NotificationNotFoundException(sprintf('Notification [%s] was not found.', $notificationPublicId));
        }

        return $notification;
    }

    public function send(TenantContext $context, NotificationMessage $message): NotificationReference
    {
        $this->requireCapability($context);

        if ($message->scope === 'broadcast') {
            $this->assertBroadcast($context);
        } else {
            $this->assertSend($context);
        }

        return $this->notificationPort->send($context, $message);
    }

    public function markRead(TenantContext $context, string $notificationPublicId): NotificationReference
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->notificationPort->markRead($context, $notificationPublicId);
    }

    public function markUnread(TenantContext $context, string $notificationPublicId): NotificationReference
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->notificationPort->markUnread($context, $notificationPublicId);
    }

    public function delete(TenantContext $context, string $notificationPublicId): NotificationReference
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->notificationPort->delete($context, $notificationPublicId);
    }

    /**
     * @return list<NotificationDelivery>
     */
    public function listDeliveries(TenantContext $context, string $notificationPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);
        $this->show($context, $notificationPublicId);

        return $this->deliveryTracker->listForNotification(
            $context->organization->id,
            $context->workspace?->id,
            $notificationPublicId,
        );
    }

    /**
     * @return list<NotificationTemplate>
     */
    public function listTemplates(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->templateProvider->list(
            $context->organization->id,
            $context->workspace?->id,
            $limit,
        );
    }

    public function createTemplate(TenantContext $context, NotificationTemplate $template): NotificationTemplate
    {
        $this->requireCapability($context);
        $this->assertTemplates($context);

        $created = $this->templateProvider->create(
            $context->organization->id,
            $context->workspace?->id,
            $template,
        );
        $this->auditRecorder->recordTemplateCreated($created);

        return $created;
    }

    public function updateTemplate(TenantContext $context, NotificationTemplate $template): NotificationTemplate
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $updated = $this->templateProvider->update(
            $context->organization->id,
            $context->workspace?->id,
            $template,
        );
        $this->auditRecorder->recordTemplateUpdated($updated);

        return $updated;
    }

    /**
     * @return list<NotificationPreference>
     */
    public function getPreferences(TenantContext $context, ?string $membershipPublicId = null): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->preferenceProvider->get(
            $context->organization->id,
            $membershipPublicId ?? $context->membershipPublicId,
        );
    }

    public function updatePreference(TenantContext $context, NotificationPreference $preference): NotificationPreference
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->preferenceProvider->update(
            $context->organization->id,
            $context->membershipPublicId,
            $preference,
        );
    }

    public function enqueue(TenantContext $context, string $notificationPublicId): void
    {
        $this->requireCapability($context);
        $this->assertManage($context);
        $this->show($context, $notificationPublicId);

        $this->notificationQueue->enqueue($notificationPublicId);
    }

    public function schedule(TenantContext $context, NotificationSchedule $schedule): NotificationSchedule
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $created = $this->notificationScheduler->schedule(
            $context->organization->id,
            $context->workspace?->id,
            $context->membershipPublicId,
            $schedule,
        );
        $this->auditRecorder->recordScheduled($created);

        return $created;
    }

    /**
     * @return list<NotificationSchedule>
     */
    public function listSchedules(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->notificationScheduler->list(
            $context->organization->id,
            $context->workspace?->id,
            $context->membershipPublicId,
        );
    }

    public function cancelSchedule(TenantContext $context, string $schedulePublicId): NotificationSchedule
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $cancelled = $this->notificationScheduler->cancel(
            $context->organization->id,
            $context->workspace?->id,
            $schedulePublicId,
        );
        $this->auditRecorder->recordScheduleCancelled($cancelled);

        return $cancelled;
    }

    public function createDigest(TenantContext $context, string $frequency = 'daily'): NotificationDigest
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->digestService->create(
            $context->organization->id,
            $context->membershipPublicId,
            $frequency,
        );
    }

    /**
     * @return list<NotificationDigest>
     */
    public function listDigests(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->digestService->list(
            $context->organization->id,
            $context->membershipPublicId,
            $limit,
        );
    }

    public function health(TenantContext $context): NotificationHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): NotificationStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope(
            $context->organization,
            $context->workspace,
        );
    }

    public function store(TenantContext $context, NotificationMessage $message): NotificationReference
    {
        return $this->send($context, $message);
    }

    public function destroy(TenantContext $context, string $notificationPublicId): NotificationReference
    {
        return $this->delete($context, $notificationPublicId);
    }

    /**
     * @return list<NotificationPreference>
     */
    public function showPreferences(TenantContext $context, ?string $membershipPublicId = null): array
    {
        return $this->getPreferences($context, $membershipPublicId);
    }

    public function updatePreferences(TenantContext $context, NotificationPreference $preference): NotificationPreference
    {
        $this->requireCapability($context);
        $this->assertPreferences($context);

        return $this->preferenceProvider->update(
            $context->organization->id,
            $context->membershipPublicId,
            $preference,
        );
    }

    public function storeTemplate(TenantContext $context, NotificationTemplate $template): NotificationTemplate
    {
        return $this->createTemplate($context, $template);
    }

    public function storeDigest(TenantContext $context, NotificationDigest $digest): NotificationDigest
    {
        return $this->createDigest($context, $digest->frequency);
    }

    public function storeSchedule(TenantContext $context, NotificationSchedule $schedule): NotificationSchedule
    {
        return $this->schedule($context, $schedule);
    }

    private function requireCapability(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'notifications');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionService->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read notifications.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionService->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage notifications.');
        }
    }

    private function assertSend(TenantContext $context): void
    {
        if (! $this->permissionService->canSend($context) && ! $this->permissionService->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to send notifications.');
        }
    }

    private function assertBroadcast(TenantContext $context): void
    {
        if (! $this->permissionService->canBroadcast($context) && ! $this->permissionService->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to broadcast notifications.');
        }
    }

    private function assertTemplates(TenantContext $context): void
    {
        if (! $this->permissionService->canTemplates($context) && ! $this->permissionService->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage notification templates.');
        }
    }

    private function assertPreferences(TenantContext $context): void
    {
        if (! $this->permissionService->canPreferences($context) && ! $this->permissionService->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage notification preferences.');
        }
    }
}
