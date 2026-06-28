<?php

namespace App\Services\Notification;

use App\Models\EnterpriseNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationDigest;
use App\Models\NotificationPreference;
use App\Models\NotificationSchedule;
use App\Models\NotificationTemplate;
use App\Modules\Sdk\Notification\Data\NotificationDelivery as NotificationDeliveryDto;
use App\Modules\Sdk\Notification\Data\NotificationDigest as NotificationDigestDto;
use App\Modules\Sdk\Notification\Data\NotificationPreference as NotificationPreferenceDto;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Data\NotificationSchedule as NotificationScheduleDto;
use App\Modules\Sdk\Notification\Data\NotificationTemplate as NotificationTemplateDto;
use BackedEnum;

class EnterpriseNotificationMapper
{
    public static function toReference(EnterpriseNotification $model): NotificationReference
    {
        return new NotificationReference(
            publicId: $model->public_id,
            title: $model->title,
            body: $model->body,
            status: self::enumValue($model->status, 'pending'),
            priority: self::enumValue($model->priority, 'normal'),
            scope: self::enumValue($model->scope, 'user'),
            templateKey: $model->template_key,
            channels: is_array($model->channels) ? $model->channels : [],
            mergeData: is_array($model->merge_data) ? $model->merge_data : [],
            metadata: is_array($model->metadata) ? $model->metadata : [],
            readAt: $model->read_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
        );
    }

    public static function toTemplate(NotificationTemplate $model): NotificationTemplateDto
    {
        return new NotificationTemplateDto(
            publicId: $model->public_id,
            moduleKey: $model->module_key,
            type: $model->type,
            templateType: self::enumValue($model->template_type, 'module'),
            subject: $model->subject,
            body: $model->body,
            channels: is_array($model->channels) ? $model->channels : [],
            variables: $model->variables,
            scope: self::enumValue($model->scope, 'organization'),
        );
    }

    public static function toPreference(NotificationPreference $model): NotificationPreferenceDto
    {
        return new NotificationPreferenceDto(
            publicId: $model->public_id,
            channel: $model->channel,
            type: $model->type,
            enabled: (bool) $model->enabled,
            preferredChannels: is_array($model->preferred_channels_json) ? $model->preferred_channels_json : [],
            digestFrequency: $model->digest_frequency,
            quietHours: is_array($model->quiet_hours_json) ? $model->quiet_hours_json : [],
        );
    }

    public static function toDelivery(NotificationDelivery $model, string $recipientMembershipPublicId): NotificationDeliveryDto
    {
        return new NotificationDeliveryDto(
            publicId: $model->public_id,
            notificationPublicId: $model->notification_public_id,
            channel: $model->channel,
            status: self::enumValue($model->status, 'pending'),
            recipientMembershipPublicId: $recipientMembershipPublicId,
            deliveredAt: $model->delivered_at?->toIso8601String(),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toDigest(NotificationDigest $model): NotificationDigestDto
    {
        return new NotificationDigestDto(
            publicId: $model->public_id,
            frequency: $model->frequency,
            status: $model->status,
            notificationCount: (int) $model->notification_count,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toSchedule(NotificationSchedule $model): NotificationScheduleDto
    {
        return new NotificationScheduleDto(
            publicId: $model->public_id,
            title: $model->title,
            cronExpression: $model->cron_expression,
            templateKey: $model->template_key,
            status: $model->status,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    private static function enumValue(mixed $value, string $default): string
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
