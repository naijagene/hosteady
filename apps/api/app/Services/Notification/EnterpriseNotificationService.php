<?php

namespace App\Services\Notification;

use App\Enums\MembershipStatus;
use App\Models\EnterpriseNotification;
use App\Models\OrganizationMemberRole;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Modules\Sdk\Notification\Contracts\NotificationPort;
use App\Modules\Sdk\Notification\Contracts\NotificationProvider;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Modules\Sdk\Notification\Data\NotificationRecipient;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Enums\NotificationStatus;
use App\Modules\Sdk\Notification\Exceptions\NotificationNotFoundException;
use App\Services\Notification\Providers\EmailProvider;
use App\Services\Notification\Providers\InAppProvider;
use App\Services\Notification\Providers\PushProvider;
use App\Services\Notification\Providers\SlackProvider;
use App\Services\Notification\Providers\SmsProvider;
use App\Services\Notification\Providers\TeamsProvider;
use App\Services\Notification\Providers\WebhookProvider;
use App\Services\Notification\Providers\WhatsappProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseNotificationService implements NotificationPort
{
    /**
     * @var array<string, NotificationProvider>
     */
    private array $providers;

    public function __construct(
        private readonly NotificationRendererService $rendererService,
        private readonly NotificationTemplateService $templateService,
        private readonly NotificationPreferenceService $preferenceService,
        private readonly NotificationDeliveryService $deliveryService,
        private readonly NotificationAuditRecorder $auditRecorder,
        private readonly NotificationSearchIndexer $searchIndexer,
        InAppProvider $inAppProvider,
        EmailProvider $emailProvider,
        SmsProvider $smsProvider,
        PushProvider $pushProvider,
        WhatsappProvider $whatsappProvider,
        SlackProvider $slackProvider,
        TeamsProvider $teamsProvider,
        WebhookProvider $webhookProvider,
    ) {
        $this->providers = [
            'in_app' => $inAppProvider,
            'email' => $emailProvider,
            'sms' => $smsProvider,
            'push' => $pushProvider,
            'whatsapp' => $whatsappProvider,
            'slack' => $slackProvider,
            'teams' => $teamsProvider,
            'webhook' => $webhookProvider,
        ];
    }

    public function send(TenantContext $context, NotificationMessage $message): NotificationReference
    {
        $mergeData = $this->buildMergeData($context, $message);
        $title = $message->title;
        $body = $message->body;
        $channels = $message->channels !== [] ? $message->channels : ['in_app'];

        if ($message->templateKey !== null && $message->moduleKey !== null) {
            $template = $this->templateService->findByKey(
                $context->organization->id,
                $context->workspace?->id,
                $message->moduleKey,
                $message->templateKey,
            );

            if ($template !== null) {
                $rendered = $this->rendererService->render($template, $mergeData);
                $title = $rendered['title'];
                $body = $rendered['body'];
                if ($template->channels !== []) {
                    $channels = $template->channels;
                }
            }
        }

        $title = $this->rendererService->replaceVariables($title, $mergeData);
        $body = $this->rendererService->replaceVariables($body, $mergeData);

        $model = EnterpriseNotification::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'scope' => $message->scope,
            'priority' => $message->priority,
            'status' => NotificationStatus::Pending,
            'title' => $title,
            'body' => $body,
            'template_key' => $message->templateKey,
            'merge_data' => $mergeData,
            'channels' => $channels,
            'metadata' => array_merge($message->metadata, [
                'module_key' => $message->moduleKey,
                'type' => $message->metadata['type'] ?? $message->templateKey ?? 'enterprise_notification',
            ]),
            'sender_membership_id' => $context->membership->id,
        ]);

        $reference = NotificationMapper::toReference($model);
        $recipients = $this->resolveRecipients($context, $message);
        $notificationType = is_string($message->metadata['type'] ?? null)
            ? $message->metadata['type']
            : ($message->templateKey ?? 'enterprise_notification');
        $deliveredCount = 0;

        foreach ($recipients as $recipient) {
            foreach ($channels as $channel) {
                if (! $this->preferenceService->isChannelEnabled(
                    $context->organization->id,
                    $recipient->membershipPublicId,
                    $notificationType,
                    $channel,
                )) {
                    continue;
                }

                $provider = $this->providers[$channel] ?? null;

                if ($provider === null) {
                    continue;
                }

                try {
                    $delivery = $provider->deliver($channel, $reference, $recipient, $context);
                    $tracked = $this->deliveryService->track(
                        $context->organization->id,
                        $context->workspace?->id,
                        $delivery,
                    );

                    if ($tracked->status === 'delivered') {
                        $deliveredCount++;
                        $this->auditRecorder->recordDelivered($reference, $tracked);
                    } else {
                        $this->auditRecorder->recordDeliveryFailed($reference, $tracked);
                    }
                } catch (\Throwable $exception) {
                    $failedDelivery = new \App\Modules\Sdk\Notification\Data\NotificationDelivery(
                        publicId: (string) Str::uuid7(),
                        notificationPublicId: $reference->publicId,
                        channel: $channel,
                        status: 'failed',
                        recipientMembershipPublicId: $recipient->membershipPublicId,
                        deliveredAt: null,
                        metadata: ['error' => $exception->getMessage()],
                    );
                    $this->deliveryService->track(
                        $context->organization->id,
                        $context->workspace?->id,
                        $failedDelivery,
                    );
                    $this->auditRecorder->recordDeliveryFailed($reference, $failedDelivery);
                }
            }
        }

        $model->status = $deliveredCount > 0 ? NotificationStatus::Delivered : NotificationStatus::Failed;
        $model->save();

        $reference = NotificationMapper::toReference($model->fresh());
        $this->auditRecorder->recordSent($reference);
        $this->searchIndexer->indexBestEffort($reference, $context);

        try {
            app(\App\Services\Integration\IntegrationNotificationBridge::class)->publishNotificationEventBestEffort(
                $context,
                'notification.sent',
                $reference,
            );
        } catch (\Throwable) {
        }

        return $reference;
    }

    /**
     * @return list<NotificationReference>
     */
    public function list(TenantContext $context, int $limit = 50): array
    {
        $notificationIds = \App\Models\NotificationDelivery::query()
            ->where('organization_id', $context->organization->id)
            ->where('recipient_membership_id', $context->membership->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('enterprise_notification_id');

        if ($notificationIds->isEmpty()) {
            return [];
        }

        return EnterpriseNotification::query()
            ->where('organization_id', $context->organization->id)
            ->whereIn('id', $notificationIds)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (EnterpriseNotification $model) => NotificationMapper::toReference($model))
            ->all();
    }

    public function find(TenantContext $context, string $notificationPublicId): ?NotificationReference
    {
        $model = $this->resolveAccessibleModel($context, $notificationPublicId);

        return $model !== null ? NotificationMapper::toReference($model) : null;
    }

    public function markRead(TenantContext $context, string $notificationPublicId): NotificationReference
    {
        $model = $this->resolveAccessibleModel($context, $notificationPublicId, required: true);

        if ($model->read_at === null) {
            $model->update([
                'read_at' => now(),
                'status' => NotificationStatus::Read,
            ]);

            \App\Models\NotificationDelivery::query()
                ->where('enterprise_notification_id', $model->id)
                ->where('recipient_membership_id', $context->membership->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $reference = NotificationMapper::toReference($model->fresh());
        $this->auditRecorder->recordRead($reference);

        return $reference;
    }

    public function markUnread(TenantContext $context, string $notificationPublicId): NotificationReference
    {
        $model = $this->resolveAccessibleModel($context, $notificationPublicId, required: true);

        if ($model->read_at !== null) {
            $model->update([
                'read_at' => null,
                'status' => NotificationStatus::Delivered,
            ]);

            \App\Models\NotificationDelivery::query()
                ->where('enterprise_notification_id', $model->id)
                ->where('recipient_membership_id', $context->membership->id)
                ->update(['read_at' => null]);
        }

        $reference = NotificationMapper::toReference($model->fresh());
        $this->auditRecorder->recordUnread($reference);

        return $reference;
    }

    public function delete(TenantContext $context, string $notificationPublicId): NotificationReference
    {
        $model = $this->resolveAccessibleModel($context, $notificationPublicId, required: true);
        $reference = NotificationMapper::toReference($model);

        $model->update(['deleted_by_user_id' => $context->user->id]);
        $model->delete();
        $this->auditRecorder->recordDeleted($reference);

        return $reference;
    }

    /**
     * @return list<NotificationRecipient>
     */
    private function resolveRecipients(TenantContext $context, NotificationMessage $message): array
    {
        return match ($message->scope) {
            'user' => $this->recipientFromPublicId($context->organization->id, $message->recipientMembershipPublicId),
            'users' => $this->recipientsFromPublicIds($context->organization->id, $message->recipientMembershipPublicIds),
            'role' => $this->recipientsForRole($context->organization->id, $message->rolePublicId),
            'workspace' => $this->recipientsForWorkspace($context->organization->id, $context->workspace?->id),
            'organization', 'broadcast' => $this->recipientsForOrganization($context->organization->id),
            default => [],
        };
    }

    /**
     * @return list<NotificationRecipient>
     */
    private function recipientFromPublicId(string $organizationId, ?string $membershipPublicId): array
    {
        if ($membershipPublicId === null || $membershipPublicId === '') {
            return [];
        }

        $membership = OrganizationMembership::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->where('public_id', $membershipPublicId)
            ->first();

        return $membership !== null ? [$this->toRecipient($membership)] : [];
    }

    /**
     * @param  list<string>  $membershipPublicIds
     * @return list<NotificationRecipient>
     */
    private function recipientsFromPublicIds(string $organizationId, array $membershipPublicIds): array
    {
        if ($membershipPublicIds === []) {
            return [];
        }

        return OrganizationMembership::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->whereIn('public_id', $membershipPublicIds)
            ->get()
            ->map(fn (OrganizationMembership $membership) => $this->toRecipient($membership))
            ->all();
    }

    /**
     * @return list<NotificationRecipient>
     */
    private function recipientsForRole(string $organizationId, ?string $rolePublicId): array
    {
        if ($rolePublicId === null || $rolePublicId === '') {
            return [];
        }

        $roleId = Role::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $rolePublicId)
            ->value('id');

        if ($roleId === null) {
            return [];
        }

        $membershipIds = OrganizationMemberRole::query()
            ->where('role_id', $roleId)
            ->pluck('organization_membership_id')
            ->all();

        if ($membershipIds === []) {
            return [];
        }

        return OrganizationMembership::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->whereIn('id', $membershipIds)
            ->where('status', MembershipStatus::Active)
            ->get()
            ->map(fn (OrganizationMembership $membership) => $this->toRecipient($membership))
            ->all();
    }

    /**
     * @return list<NotificationRecipient>
     */
    private function recipientsForWorkspace(string $organizationId, ?string $workspaceId): array
    {
        $query = OrganizationMembership::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->where('status', MembershipStatus::Active);

        if ($workspaceId !== null) {
            $query->where('default_workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(fn (OrganizationMembership $membership) => $this->toRecipient($membership))
            ->all();
    }

    /**
     * @return list<NotificationRecipient>
     */
    private function recipientsForOrganization(string $organizationId): array
    {
        return OrganizationMembership::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->where('status', MembershipStatus::Active)
            ->get()
            ->map(fn (OrganizationMembership $membership) => $this->toRecipient($membership))
            ->all();
    }

    private function toRecipient(OrganizationMembership $membership): NotificationRecipient
    {
        return new NotificationRecipient(
            membershipPublicId: $membership->public_id,
            userPublicId: $membership->user?->public_id,
            displayName: $membership->user?->name,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMergeData(TenantContext $context, NotificationMessage $message): array
    {
        return array_merge([
            'user_name' => $context->user->name ?? '',
            'workspace' => $context->workspace?->name ?? '',
            'organization' => $context->organization->name ?? '',
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
        ], $message->mergeData);
    }

    private function resolveAccessibleModel(
        TenantContext $context,
        string $notificationPublicId,
        bool $required = false,
    ): ?EnterpriseNotification {
        $model = EnterpriseNotification::query()
            ->where('organization_id', $context->organization->id)
            ->where('public_id', $notificationPublicId)
            ->first();

        if ($model === null) {
            if ($required) {
                throw new NotificationNotFoundException(sprintf('Notification [%s] was not found.', $notificationPublicId));
            }

            return null;
        }

        $hasDelivery = \App\Models\NotificationDelivery::query()
            ->where('enterprise_notification_id', $model->id)
            ->where('recipient_membership_id', $context->membership->id)
            ->exists();

        $isSender = $model->sender_membership_id === $context->membership->id;

        if (! $hasDelivery && ! $isSender) {
            if ($required) {
                throw new NotificationNotFoundException(sprintf('Notification [%s] was not found.', $notificationPublicId));
            }

            return null;
        }

        return $model;
    }
}
