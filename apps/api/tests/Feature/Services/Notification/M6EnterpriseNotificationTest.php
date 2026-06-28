<?php

namespace Tests\Feature\Services\Notification;

use App\Enums\AuditAction;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\EnterpriseNotification;
use App\Models\NotificationDelivery as NotificationDeliveryModel;
use App\Models\PlatformNotification;
use App\Models\Role;
use App\Modules\Sdk\DataRepository\Data\EntityRecordReference;
use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\NotificationRequest;
use App\Modules\Sdk\Notification\Contracts\NotificationDeliveryTracker;
use App\Modules\Sdk\Notification\Contracts\NotificationPort;
use App\Modules\Sdk\Notification\Contracts\NotificationPreferenceProvider;
use App\Modules\Sdk\Notification\Contracts\NotificationQueue;
use App\Modules\Sdk\Notification\Contracts\NotificationRenderer;
use App\Modules\Sdk\Notification\Contracts\NotificationScheduler;
use App\Modules\Sdk\Notification\Contracts\NotificationTemplateProvider;
use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use App\Modules\Sdk\Notification\Data\NotificationDigest;
use App\Modules\Sdk\Notification\Data\NotificationHealthReport;
use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Modules\Sdk\Notification\Data\NotificationPreference;
use App\Modules\Sdk\Notification\Data\NotificationRecipient;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Data\NotificationSchedule;
use App\Modules\Sdk\Notification\Data\NotificationStatistics;
use App\Modules\Sdk\Notification\Data\NotificationTemplate;
use App\Modules\Sdk\Notification\Enums\NotificationChannelType;
use App\Modules\Sdk\Notification\Enums\NotificationPriority;
use App\Modules\Sdk\Notification\Enums\NotificationScope;
use App\Modules\Sdk\Notification\Enums\NotificationStatus;
use App\Modules\Sdk\Notification\Enums\NotificationTemplateType;
use App\Modules\Sdk\Notification\Exceptions\NotificationNotFoundException;
use App\Services\Enterprise\Notification\NotificationService;
use App\Services\Module\ModuleDoctorService;
use App\Services\Notification\EnterpriseNotificationPermissionService;
use App\Services\Notification\EnterpriseNotificationService;
use App\Services\Notification\NotificationAuditRecorder;
use App\Services\Notification\NotificationDevelopmentService;
use App\Services\Notification\NotificationDigestService;
use App\Services\Notification\NotificationDocumentBridge;
use App\Services\Notification\NotificationEntityBridge;
use App\Services\Notification\NotificationHealthService;
use App\Services\Notification\NotificationPreferenceService;
use App\Services\Notification\NotificationRendererService;
use App\Services\Notification\NotificationReportBridge;
use App\Services\Notification\NotificationScheduleService;
use App\Services\Notification\NotificationStatisticsService;
use App\Services\Notification\NotificationTemplateService;
use App\Services\Notification\Providers\EmailProvider;
use App\Services\Notification\Providers\InAppProvider;
use App\Services\Notification\Providers\PushProvider;
use App\Services\Notification\Providers\SlackProvider;
use App\Services\Notification\Providers\SmsProvider;
use App\Services\Notification\Providers\TeamsProvider;
use App\Services\Notification\Providers\WebhookProvider;
use App\Services\Notification\Providers\WhatsappProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M6EnterpriseNotificationTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_notification_message_dto_roundtrip(): void
    {
        $message = NotificationMessage::fromArray([
            'title' => 'Hello',
            'body' => 'World',
            'scope' => 'user',
            'priority' => 'normal',
            'template_key' => 'welcome',
            'merge_data' => ['user_name' => 'Ada'],
            'channels' => ['in_app'],
            'recipient_membership_public_id' => '01900000-0000-7000-8000-000000000801',
            'module_key' => 'demo.core',
            'metadata' => ['type' => 'demo.welcome'],
        ]);

        $roundtrip = NotificationMessage::fromArray($message->toArray());

        $this->assertSame('Hello', $roundtrip->title);
        $this->assertSame('welcome', $roundtrip->templateKey);
    }

    public function test_notification_message_dto_supports_camel_case_keys(): void
    {
        $message = NotificationMessage::fromArray([
            'title' => 'Camel',
            'body' => 'Case',
            'scope' => 'users',
            'priority' => 'high',
            'templateKey' => 'alert',
            'mergeData' => ['task_name' => 'Review'],
            'channels' => ['email'],
            'recipientMembershipPublicIds' => ['01900000-0000-7000-8000-000000000802'],
            'moduleKey' => 'demo.core',
            'metadata' => [],
        ]);

        $this->assertSame('alert', $message->templateKey);
        $this->assertSame('Review', $message->mergeData['task_name']);
    }

    public function test_notification_reference_dto_roundtrip(): void
    {
        $reference = NotificationReference::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000803',
            'title' => 'Alert',
            'body' => 'Something happened',
            'status' => 'delivered',
            'priority' => 'high',
            'scope' => 'user',
            'template_key' => 'alert',
            'channels' => ['in_app'],
            'merge_data' => [],
            'metadata' => ['type' => 'demo.alert'],
            'read_at' => null,
            'created_at' => '2026-06-28T10:00:00+00:00',
        ]);

        $this->assertSame('delivered', NotificationReference::fromArray($reference->toArray())->status);
    }

    public function test_notification_delivery_dto_roundtrip(): void
    {
        $delivery = NotificationDelivery::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000804',
            'notification_public_id' => '01900000-0000-7000-8000-000000000803',
            'channel' => 'in_app',
            'status' => 'delivered',
            'recipient_membership_public_id' => '01900000-0000-7000-8000-000000000801',
            'delivered_at' => '2026-06-28T10:00:01+00:00',
            'metadata' => ['provider' => 'in_app'],
        ]);

        $this->assertSame('in_app', $delivery->jsonSerialize()['channel']);
    }

    public function test_notification_template_dto_roundtrip(): void
    {
        $template = NotificationTemplate::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000805',
            'module_key' => 'demo.core',
            'type' => 'welcome',
            'template_type' => 'module',
            'subject' => 'Welcome {{user_name}}',
            'body' => 'Hello {{user_name}}',
            'channels' => ['in_app', 'email'],
            'variables' => ['user_name'],
            'scope' => 'organization',
        ]);

        $this->assertSame('welcome', $template->toArray()['type']);
    }

    public function test_notification_preference_dto_roundtrip(): void
    {
        $preference = NotificationPreference::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000806',
            'channel' => 'email',
            'type' => 'demo.welcome',
            'enabled' => false,
            'preferred_channels' => ['email'],
            'digest_frequency' => 'daily',
            'quiet_hours' => ['start' => '22:00', 'end' => '07:00'],
        ]);

        $this->assertFalse($preference->jsonSerialize()['enabled']);
    }

    public function test_notification_schedule_dto_roundtrip(): void
    {
        $schedule = NotificationSchedule::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000807',
            'title' => 'Weekly digest',
            'cron_expression' => '0 9 * * 1',
            'template_key' => 'weekly_digest',
            'status' => 'active',
            'metadata' => ['timezone' => 'UTC'],
        ]);

        $this->assertSame('0 9 * * 1', $schedule->toArray()['cron_expression']);
    }

    public function test_notification_digest_dto_roundtrip(): void
    {
        $digest = NotificationDigest::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000808',
            'frequency' => 'weekly',
            'status' => 'pending',
            'notification_count' => 5,
            'metadata' => ['source' => 'scheduler'],
        ]);

        $this->assertSame(5, $digest->jsonSerialize()['notification_count']);
    }

    public function test_notification_health_report_dto_roundtrip(): void
    {
        $report = NotificationHealthReport::fromArray([
            'enabled' => true,
            'notifications' => 3,
            'deliveries' => 4,
            'warnings' => [],
            'status' => 'healthy',
            'missing_tables' => [],
        ]);

        $this->assertSame('healthy', $report->toArray()['status']);
    }

    public function test_notification_statistics_dto_roundtrip(): void
    {
        $statistics = NotificationStatistics::fromArray([
            'notifications' => 2,
            'deliveries' => 3,
            'templates' => 1,
            'subscriptions' => 0,
            'schedules' => 1,
            'digests' => 1,
        ]);

        $this->assertSame(2, $statistics->jsonSerialize()['notifications']);
    }

    public function test_notification_recipient_dto_roundtrip(): void
    {
        $recipient = NotificationRecipient::fromArray([
            'membership_public_id' => '01900000-0000-7000-8000-000000000809',
            'user_public_id' => '01900000-0000-7000-8000-000000000810',
            'display_name' => 'Ada Lovelace',
        ]);

        $this->assertSame('Ada Lovelace', $recipient->toArray()['display_name']);
    }

    public function test_notification_scope_enum_values(): void
    {
        $this->assertSame('user', NotificationScope::User->value);
        $this->assertSame('broadcast', NotificationScope::Broadcast->value);
        $this->assertCount(6, NotificationScope::cases());
    }

    public function test_notification_status_enum_values(): void
    {
        $this->assertSame('pending', NotificationStatus::Pending->value);
        $this->assertSame('read', NotificationStatus::Read->value);
        $this->assertContains(NotificationStatus::Delivered, NotificationStatus::cases());
    }

    public function test_notification_priority_enum_values(): void
    {
        $this->assertSame('urgent', NotificationPriority::Urgent->value);
        $this->assertCount(4, NotificationPriority::cases());
    }

    public function test_notification_channel_type_enum_values(): void
    {
        $this->assertSame('in_app', NotificationChannelType::InApp->value);
        $this->assertSame('webhook', NotificationChannelType::Webhook->value);
        $this->assertCount(8, NotificationChannelType::cases());
    }

    public function test_notification_template_type_enum_values(): void
    {
        $this->assertSame('digest', NotificationTemplateType::Digest->value);
        $this->assertCount(4, NotificationTemplateType::cases());
    }

    public function test_notification_contracts_are_bound(): void
    {
        $this->assertInstanceOf(NotificationPort::class, app(NotificationPort::class));
        $this->assertInstanceOf(NotificationRenderer::class, app(NotificationRenderer::class));
        $this->assertInstanceOf(NotificationTemplateProvider::class, app(NotificationTemplateProvider::class));
        $this->assertInstanceOf(NotificationPreferenceProvider::class, app(NotificationPreferenceProvider::class));
        $this->assertInstanceOf(NotificationDeliveryTracker::class, app(NotificationDeliveryTracker::class));
        $this->assertInstanceOf(NotificationQueue::class, app(NotificationQueue::class));
        $this->assertInstanceOf(NotificationScheduler::class, app(NotificationScheduler::class));
        $this->assertInstanceOf(EnterpriseNotificationService::class, app(EnterpriseNotificationService::class));
    }

    public function test_renderer_replaces_known_variables(): void
    {
        $renderer = app(NotificationRendererService::class);

        $rendered = $renderer->replaceVariables(
            'Hello {{user_name}} from {{organization}}',
            [
                'user_name' => 'Ada',
                'organization' => 'Acme Corp',
            ],
        );

        $this->assertSame('Hello Ada from Acme Corp', $rendered);
    }

    public function test_renderer_replaces_custom_merge_data(): void
    {
        $renderer = app(NotificationRendererService::class);

        $rendered = $renderer->replaceVariables(
            'Task {{task_name}} due on {{date}}',
            [
                'task_name' => 'Review contract',
                'date' => '2026-06-28',
            ],
        );

        $this->assertSame('Task Review contract due on 2026-06-28', $rendered);
    }

    public function test_renderer_render_template_applies_subject_and_body(): void
    {
        $renderer = app(NotificationRendererService::class);

        $rendered = $renderer->render(
            new NotificationTemplate(
                publicId: '',
                moduleKey: 'demo.core',
                type: 'welcome',
                templateType: 'module',
                subject: 'Welcome {{user_name}}',
                body: 'Hello {{user_name}} in {{workspace}}',
                channels: ['in_app'],
                variables: ['user_name', 'workspace'],
            ),
            [
                'user_name' => 'Ada',
                'workspace' => 'Default',
            ],
        );

        $this->assertSame('Welcome Ada', $rendered['title']);
        $this->assertSame('Hello Ada in Default', $rendered['body']);
    }

    public function test_send_user_scope_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $notification = $this->sendSampleNotification($context);

        $this->assertNotEmpty($notification->publicId);
        $this->assertSame('delivered', $notification->status);
        $this->assertDatabaseHas('enterprise_notifications', ['public_id' => $notification->publicId]);
    }

    public function test_send_users_scope_notification(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);

        $notification = $this->sendSampleNotification($ownerContext, [
            'scope' => 'users',
            'recipient_membership_public_ids' => [
                $ownerContext->membershipPublicId,
                $memberContext->membershipPublicId,
            ],
        ]);

        $this->assertSame('delivered', $notification->status);
        $this->assertGreaterThanOrEqual(2, NotificationDeliveryModel::query()
            ->where('notification_public_id', $notification->publicId)
            ->count());
    }

    public function test_send_workspace_scope_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $notification = $this->sendSampleNotification($context, [
            'scope' => 'workspace',
            'title' => 'Workspace notice',
        ]);

        $this->assertSame('workspace', $notification->scope);
        $this->assertTrue(NotificationDeliveryModel::query()
            ->where('notification_public_id', $notification->publicId)
            ->exists());
    }

    public function test_send_organization_scope_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $notification = $this->sendSampleNotification($context, [
            'scope' => 'organization',
            'title' => 'Organization notice',
        ]);

        $this->assertSame('organization', $notification->scope);
        $this->assertDatabaseHas('enterprise_notifications', [
            'public_id' => $notification->publicId,
            'scope' => 'organization',
        ]);
    }

    public function test_list_returns_sent_notifications(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $sent = $this->sendSampleNotification($context);

        $listed = $service->list($context);

        $this->assertNotEmpty($listed);
        $this->assertSame($sent->publicId, $listed[0]->publicId);
    }

    public function test_mark_read_sets_read_timestamp(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $notification = $this->sendSampleNotification($context);
        $read = $service->markRead($context, $notification->publicId);

        $this->assertNotNull($read->readAt);
        $this->assertSame('read', $read->status);
    }

    public function test_mark_unread_clears_read_timestamp(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $notification = $this->sendSampleNotification($context);
        $service->markRead($context, $notification->publicId);
        $unread = $service->markUnread($context, $notification->publicId);

        $this->assertNull($unread->readAt);
        $this->assertSame('delivered', $unread->status);
    }

    public function test_delete_soft_deletes_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $notification = $this->sendSampleNotification($context);
        $service->delete($context, $notification->publicId);

        $this->assertSoftDeleted('enterprise_notifications', ['public_id' => $notification->publicId]);
    }

    public function test_send_creates_delivery_records(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $notification = $this->sendSampleNotification($context);

        $this->assertDatabaseHas('notification_deliveries', [
            'notification_public_id' => $notification->publicId,
            'channel' => 'in_app',
            'status' => 'delivered',
        ]);
    }

    public function test_delivery_lists_for_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $notification = $this->sendSampleNotification($context);

        $deliveries = $service->listDeliveries($context, $notification->publicId);

        $this->assertCount(1, $deliveries);
        $this->assertSame('in_app', $deliveries[0]->channel);
    }

    public function test_template_create_persists_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $template = $service->createTemplate($context, new NotificationTemplate(
            publicId: '',
            moduleKey: 'demo.core',
            type: 'welcome',
            templateType: 'module',
            subject: 'Welcome',
            body: 'Hello there',
            channels: ['in_app'],
            variables: [],
        ));

        $this->assertNotEmpty($template->publicId);
        $this->assertDatabaseHas('notification_templates', [
            'public_id' => $template->publicId,
            'type' => 'welcome',
        ]);
    }

    public function test_template_find_by_key(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationTemplateService::class);

        app(NotificationDevelopmentService::class)->createTemplate($context, new NotificationTemplate(
            publicId: '',
            moduleKey: 'demo.core',
            type: 'reminder',
            templateType: 'module',
            subject: 'Reminder',
            body: 'Do the thing',
            channels: ['in_app'],
            variables: [],
        ));

        $found = $service->findByKey(
            $context->organization->id,
            $context->workspace?->id,
            'demo.core',
            'reminder',
        );

        $this->assertNotNull($found);
        $this->assertSame('reminder', $found->type);
    }

    public function test_template_list_returns_created(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $service->createTemplate($context, new NotificationTemplate(
            publicId: '',
            moduleKey: 'demo.core',
            type: 'listed',
            templateType: 'custom',
            subject: 'Listed',
            body: 'Template body',
            channels: ['in_app'],
            variables: [],
        ));

        $this->assertNotEmpty($service->listTemplates($context));
    }

    public function test_template_update_changes_body(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $created = $service->createTemplate($context, new NotificationTemplate(
            publicId: '',
            moduleKey: 'demo.core',
            type: 'update_me',
            templateType: 'module',
            subject: 'Original',
            body: 'Original body',
            channels: ['in_app'],
            variables: [],
        ));

        $updated = $service->updateTemplate($context, new NotificationTemplate(
            publicId: $created->publicId,
            moduleKey: 'demo.core',
            type: 'update_me',
            templateType: 'module',
            subject: 'Updated',
            body: 'Updated body',
            channels: ['in_app', 'email'],
            variables: [],
        ));

        $this->assertSame('Updated body', $updated->body);
    }

    public function test_preference_is_enabled_by_default(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationPreferenceService::class);

        $this->assertTrue($service->isChannelEnabled(
            $context->organization->id,
            $context->membershipPublicId,
            'demo.welcome',
            'in_app',
        ));
    }

    public function test_preference_update_persists(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $preference = $service->updatePreference($context, new NotificationPreference(
            publicId: '',
            channel: 'email',
            type: 'demo.welcome',
            enabled: false,
            preferredChannels: ['email'],
            digestFrequency: 'daily',
            quietHours: [],
        ));

        $this->assertFalse($preference->enabled);
        $this->assertDatabaseHas('notification_preferences', [
            'public_id' => $preference->publicId,
            'enabled' => false,
        ]);
    }

    public function test_preference_get_returns_saved(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $service->updatePreference($context, new NotificationPreference(
            publicId: '',
            channel: 'sms',
            type: 'demo.alert',
            enabled: true,
            preferredChannels: ['sms'],
            digestFrequency: null,
            quietHours: [],
        ));

        $preferences = $service->getPreferences($context);

        $this->assertNotEmpty($preferences);
        $this->assertSame('sms', $preferences[0]->channel);
    }

    public function test_schedule_create_persists(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $schedule = $service->schedule($context, new NotificationSchedule(
            publicId: '',
            title: 'Daily reminder',
            cronExpression: '0 8 * * *',
            templateKey: 'daily_reminder',
            status: 'active',
            metadata: [],
        ));

        $this->assertNotEmpty($schedule->publicId);
        $this->assertDatabaseHas('notification_schedules', [
            'public_id' => $schedule->publicId,
            'title' => 'Daily reminder',
        ]);
    }

    public function test_schedule_list_returns_created(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $service->schedule($context, new NotificationSchedule(
            publicId: '',
            title: 'Listed schedule',
            cronExpression: '0 9 * * 1',
            templateKey: 'weekly',
            status: 'active',
            metadata: [],
        ));

        $this->assertNotEmpty($service->listSchedules($context));
    }

    public function test_schedule_cancel_updates_status(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $schedule = $service->schedule($context, new NotificationSchedule(
            publicId: '',
            title: 'Cancel me',
            cronExpression: '0 10 * * *',
            templateKey: null,
            status: 'active',
            metadata: [],
        ));

        $cancelled = $service->cancelSchedule($context, $schedule->publicId);

        $this->assertSame('cancelled', $cancelled->status);
    }

    public function test_digest_create_persists(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDigestService::class);

        $digest = $service->create(
            $context->organization->id,
            $context->membershipPublicId,
            'daily',
        );

        $this->assertNotEmpty($digest->publicId);
        $this->assertDatabaseHas('notification_digests', [
            'public_id' => $digest->publicId,
            'frequency' => 'daily',
        ]);
    }

    public function test_digest_list_returns_created(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $service->createDigest($context, 'weekly');

        $this->assertNotEmpty($service->listDigests($context));
    }

    public function test_digest_mark_generated(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $digestService = app(NotificationDigestService::class);

        $digest = $digestService->create(
            $context->organization->id,
            $context->membershipPublicId,
            'daily',
        );

        $generated = $digestService->markGenerated(
            $context->organization->id,
            $digest->publicId,
            7,
        );

        $this->assertSame('generated', $generated->status);
        $this->assertSame(7, $generated->notificationCount);
    }

    public function test_digest_delete_removes_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $digestService = app(NotificationDigestService::class);

        $digest = $digestService->create(
            $context->organization->id,
            $context->membershipPublicId,
            'daily',
        );

        $digestService->delete($context->organization->id, $digest->publicId);

        $this->assertSoftDeleted('notification_digests', ['public_id' => $digest->publicId]);
    }

    public function test_health_service_assessment(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->sendSampleNotification($context);

        $assessment = app(NotificationHealthService::class)->assess($context);

        $this->assertTrue($assessment['enabled']);
        $this->assertSame(1, $assessment['notifications']);
    }

    public function test_health_report_returns_dto(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->sendSampleNotification($context);

        $report = app(NotificationDevelopmentService::class)->health($context);

        $this->assertTrue($report->enabled);
        $this->assertSame(1, $report->notifications);
    }

    public function test_statistics_counts_notifications(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->sendSampleNotification($context);

        $statistics = app(NotificationStatisticsService::class)->statisticsForScope(
            $context->organization,
            $context->workspace,
        );

        $this->assertSame(1, $statistics->notifications);
        $this->assertGreaterThanOrEqual(1, $statistics->deliveries);
    }

    public function test_send_records_audit_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->sendSampleNotification($context);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::NotificationSent->value)->exists());
    }

    public function test_mark_read_records_audit_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $notification = $this->sendSampleNotification($context);
        $service->markRead($context, $notification->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::NotificationRead->value)->exists());
    }

    public function test_template_create_records_audit_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $service->createTemplate($context, new NotificationTemplate(
            publicId: '',
            moduleKey: 'demo.core',
            type: 'audit_template',
            templateType: 'module',
            subject: 'Audit',
            body: 'Audit body',
            channels: ['in_app'],
            variables: [],
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::NotificationPreferenceUpdated->value)->exists());
    }

    public function test_schedule_create_records_audit_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $service->schedule($context, new NotificationSchedule(
            publicId: '',
            title: 'Audit schedule',
            cronExpression: '0 7 * * *',
            templateKey: null,
            status: 'active',
            metadata: [],
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::NotificationSent->value)->exists());
    }

    public function test_document_bridge_does_not_throw(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        app(NotificationDocumentBridge::class)->notifyDocumentEventBestEffort(
            $context,
            'uploaded',
            DocumentReference::fromArray([
                'public_id' => '01900000-0000-7000-8000-000000000811',
                'title' => 'Bridge Document',
                'status' => 'active',
                'visibility' => 'organization',
                'category' => 'general',
                'module_key' => 'demo.core',
                'current_version_number' => 1,
            ]),
        );

        $this->assertTrue(EnterpriseNotification::query()->where('title', 'like', 'Document:%')->exists());
    }

    public function test_entity_bridge_does_not_throw(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        app(NotificationEntityBridge::class)->notifyRecordEventBestEffort(
            $context,
            'updated',
            new EntityRecordReference(
                publicId: '01900000-0000-7000-8000-000000000812',
                moduleKey: 'demo.core',
                entityKey: 'asset',
            ),
        );

        $this->assertTrue(EnterpriseNotification::query()->where('title', 'like', 'Record:%')->exists());
    }

    public function test_report_bridge_does_not_throw(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        app(NotificationReportBridge::class)->notifyExportReadyBestEffort(
            $context,
            'reports.demo',
            'summary',
            '01900000-0000-7000-8000-000000000813',
        );

        $this->assertTrue(EnterpriseNotification::query()->where('title', 'Report export ready')->exists());
    }

    public function test_api_list_notifications(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->sendSampleNotification($context);

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/notifications')
            ->assertOk();
    }

    public function test_api_post_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/notifications', [
                'title' => 'API Notification',
                'body' => 'Sent via API',
                'scope' => 'user',
                'recipient_membership_public_id' => $context->membershipPublicId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'API Notification');
    }

    public function test_api_show_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $notification = $this->sendSampleNotification($context);

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/notifications/'.$notification->publicId)
            ->assertOk()
            ->assertJsonPath('data.public_id', $notification->publicId);
    }

    public function test_api_mark_read(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $notification = $this->sendSampleNotification($context);

        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/notifications/'.$notification->publicId.'/read')
            ->assertOk()
            ->assertJsonPath('data.status', 'read');
    }

    public function test_api_mark_unread(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $notification = $this->sendSampleNotification($context);

        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/notifications/'.$notification->publicId.'/read')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/notifications/'.$notification->publicId.'/unread')
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');
    }

    public function test_api_delete_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $notification = $this->sendSampleNotification($context);

        $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/notifications/'.$notification->publicId)
            ->assertOk();
    }

    public function test_api_templates_list_and_create(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/notifications/templates', [
                'module_key' => 'demo.core',
                'type' => 'api_template',
                'subject' => 'API Template',
                'body' => 'Template from API',
            ])
            ->assertCreated();

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/notifications/templates')
            ->assertOk();
    }

    public function test_api_preferences_get_and_update(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/notifications/preferences')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/notifications/preferences', [
                'channel' => 'email',
                'type' => 'demo.api',
                'enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);
    }

    public function test_api_digests_list_and_create(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/notifications/digests', [
                'frequency' => 'daily',
            ])
            ->assertCreated();

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/notifications/digests')
            ->assertOk();
    }

    public function test_api_schedules_list_and_create(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/notifications/schedules', [
                'title' => 'API Schedule',
                'cron_expression' => '0 6 * * *',
            ])
            ->assertCreated();

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/notifications/schedules')
            ->assertOk();
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $notification = $this->sendSampleNotification($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/notifications/'.$notification->publicId);

        $response->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_platform_notifications_appear_in_api_index(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        app(NotificationService::class)->notify($context, new NotificationRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            recipientMembershipPublicId: $context->membershipPublicId,
            type: 'demo.m4.compat',
            title: 'M4 Platform Notification',
            body: 'Backward compatible listing',
        ));

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/notifications');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('M4 Platform Notification', $titles);
    }

    public function test_permission_catalog_has_one_hundred_permissions(): void
    {
        $this->seedHeosPermissions();
        $this->assertPermissionCatalogComplete();
    }

    public function test_module_doctor_includes_notifications_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('notification_platform', $report->platformSummary['enterprise'] ?? []);
        $this->assertTrue($report->platformSummary['enterprise']['notification_platform']['enabled'] ?? false);
    }

    public function test_config_enables_notifications(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.notifications.enabled'));
        $this->assertContains('in_app', config('heos.enterprise.notifications.channels'));
    }

    public function test_runtime_contribution_includes_notifications(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $contribution = app(NotificationHealthService::class)->runtimeContribution($context);

        $this->assertTrue($contribution['enabled']);
        $this->assertArrayHasKey('notifications', $contribution);
    }

    public function test_email_provider_marks_delivered_with_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $delivery = $this->deliverViaProvider(app(EmailProvider::class), $context, 'email');

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('email', $delivery->metadata['provider']);
        $this->assertTrue($delivery->metadata['simulated']);
    }

    public function test_sms_provider_marks_delivered_with_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $delivery = $this->deliverViaProvider(app(SmsProvider::class), $context, 'sms');

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('sms', $delivery->metadata['provider']);
    }

    public function test_push_provider_marks_delivered_with_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $delivery = $this->deliverViaProvider(app(PushProvider::class), $context, 'push');

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('push', $delivery->metadata['provider']);
    }

    public function test_whatsapp_provider_marks_delivered_with_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $delivery = $this->deliverViaProvider(app(WhatsappProvider::class), $context, 'whatsapp');

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('whatsapp', $delivery->metadata['provider']);
    }

    public function test_slack_provider_marks_delivered_with_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $delivery = $this->deliverViaProvider(app(SlackProvider::class), $context, 'slack');

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('slack', $delivery->metadata['provider']);
    }

    public function test_teams_provider_marks_delivered_with_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $delivery = $this->deliverViaProvider(app(TeamsProvider::class), $context, 'teams');

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('teams', $delivery->metadata['provider']);
    }

    public function test_webhook_provider_marks_delivered_with_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $delivery = $this->deliverViaProvider(app(WebhookProvider::class), $context, 'webhook');

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('webhook', $delivery->metadata['provider']);
    }

    public function test_provider_rejects_unsupported_channel(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $delivery = $this->deliverViaProvider(app(EmailProvider::class), $context, 'sms');

        $this->assertSame('failed', $delivery->status);
        $this->assertSame('unsupported_channel', $delivery->metadata['error']);
    }

    public function test_in_app_provider_bridges_to_platform_notification(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $notification = $this->sendSampleNotification($context);
        $reference = NotificationReference::fromArray($notification->toArray());
        $recipient = new NotificationRecipient(
            membershipPublicId: $context->membershipPublicId,
            userPublicId: $context->user->public_id,
            displayName: $context->user->name,
        );

        $delivery = app(InAppProvider::class)->deliver('in_app', $reference, $recipient, $context);

        $this->assertSame('delivered', $delivery->status);
        $this->assertNotEmpty($delivery->metadata['platform_notification_public_id'] ?? null);
        $this->assertTrue(PlatformNotification::query()
            ->where('public_id', $delivery->metadata['platform_notification_public_id'])
            ->exists());
    }

    public function test_owner_can_send_notifications(): void
    {
        $context = $this->tenantContext();
        $resolver = app(EnterpriseNotificationPermissionService::class);

        $this->assertTrue($resolver->canRead($context));
        $this->assertTrue($resolver->canSend($context));
    }

    public function test_member_can_read_but_not_send(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        $resolver = app(EnterpriseNotificationPermissionService::class);

        $this->assertTrue($resolver->canRead($memberContext));
        $this->assertFalse($resolver->canSend($memberContext));
    }

    public function test_member_can_read_via_api(): void
    {
        $ownerContext = $this->tenantContext();
        app()->instance(TenantContext::class, $ownerContext);
        $this->sendSampleNotification($ownerContext);
        $memberContext = $this->memberContext($ownerContext);

        $this->withHeaders($this->tenantHeaders($memberContext))
            ->getJson('/api/v1/tenant/notifications')
            ->assertOk();
    }

    public function test_member_cannot_send_via_api(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);

        $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/notifications', [
                'title' => 'Denied',
                'body' => 'Should fail',
                'scope' => 'user',
                'recipient_membership_public_id' => $memberContext->membershipPublicId,
            ])
            ->assertForbidden();
    }

    public function test_send_with_template_renders_variables(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $service->createTemplate($context, new NotificationTemplate(
            publicId: '',
            moduleKey: 'demo.core',
            type: 'rendered_welcome',
            templateType: 'module',
            subject: 'Welcome {{user_name}}',
            body: 'Hello {{user_name}} from {{organization}}',
            channels: ['in_app'],
            variables: ['user_name', 'organization'],
        ));

        $notification = $service->send($context, NotificationMessage::fromArray([
            'title' => 'ignored',
            'body' => 'ignored',
            'scope' => 'user',
            'priority' => 'normal',
            'template_key' => 'rendered_welcome',
            'module_key' => 'demo.core',
            'channels' => ['in_app'],
            'recipient_membership_public_id' => $context->membershipPublicId,
            'merge_data' => [],
            'metadata' => ['type' => 'demo.rendered'],
        ]));

        $this->assertStringContainsString($context->user->name, $notification->title);
        $this->assertStringContainsString($context->organization->name, $notification->body);
    }

    public function test_disabled_preference_skips_channel_delivery(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $service->updatePreference($context, new NotificationPreference(
            publicId: '',
            channel: 'email',
            type: 'test.notification',
            enabled: false,
            preferredChannels: [],
            digestFrequency: null,
            quietHours: [],
        ));

        $notification = $this->sendSampleNotification($context, [
            'channels' => ['email', 'in_app'],
            'metadata' => ['type' => 'test.notification'],
        ]);

        $this->assertDatabaseMissing('notification_deliveries', [
            'notification_public_id' => $notification->publicId,
            'channel' => 'email',
        ]);
        $this->assertDatabaseHas('notification_deliveries', [
            'notification_public_id' => $notification->publicId,
            'channel' => 'in_app',
        ]);
    }

    public function test_show_missing_notification_throws_not_found(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(NotificationDevelopmentService::class);

        $this->expectException(NotificationNotFoundException::class);
        $service->show($context, '01900000-0000-7000-8000-000000000999');
    }

    public function test_notification_audit_recorder_records_sent(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $notification = $this->sendSampleNotification($context);

        app(NotificationAuditRecorder::class)->recordSent(
            NotificationReference::fromArray($notification->toArray()),
        );

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::NotificationSent->value)->exists());
    }

    private function sendSampleNotification(
        TenantContext $context,
        array $overrides = [],
    ): NotificationReference {
        app()->instance(TenantContext::class, $context);

        return app(NotificationDevelopmentService::class)->send($context, NotificationMessage::fromArray(array_merge([
            'title' => 'Test Notification',
            'body' => 'Test body content',
            'scope' => 'user',
            'priority' => 'normal',
            'channels' => ['in_app'],
            'recipient_membership_public_id' => $context->membershipPublicId,
            'recipient_membership_public_ids' => [],
            'merge_data' => [],
            'metadata' => ['type' => 'test.notification'],
            'module_key' => 'demo.core',
        ], $overrides)));
    }

    private function deliverViaProvider(
        object $provider,
        TenantContext $context,
        string $channel,
    ): NotificationDelivery {
        $reference = new NotificationReference(
            publicId: '01900000-0000-7000-8000-000000000820',
            title: 'Provider test',
            body: 'Provider body',
            status: 'pending',
            priority: 'normal',
            scope: 'user',
            templateKey: null,
            channels: [$channel],
            mergeData: [],
            metadata: ['type' => 'provider.test'],
            readAt: null,
            createdAt: now()->toIso8601String(),
        );

        $recipient = new NotificationRecipient(
            membershipPublicId: $context->membershipPublicId,
            userPublicId: $context->user->public_id,
            displayName: $context->user->name,
        );

        return $provider->deliver($channel, $reference, $recipient, $context);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'enterprise-notifications-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'member');
    }

    private function viewerContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'viewer');
    }

    private function roleContext(TenantContext $ownerContext, string $roleKey): TenantContext
    {
        $user = $this->createActiveUser();
        $role = Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', $roleKey)
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $user->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $user,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }

    /**
     * @return array<string, string>
     */
    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }
}
