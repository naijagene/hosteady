<?php

namespace App\Services\Notification;

use App\Models\EnterpriseNotification;
use App\Models\NotificationDelivery as NotificationDeliveryModel;
use App\Models\NotificationTemplate as NotificationTemplateModel;
use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Modules\Sdk\Notification\Data\NotificationTemplate;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;

class NotificationMapper
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

    public static function toDelivery(NotificationDeliveryModel $model): NotificationDelivery
    {
        return new NotificationDelivery(
            publicId: $model->public_id,
            notificationPublicId: $model->notification_public_id,
            channel: (string) $model->channel,
            status: self::enumValue($model->status, 'pending'),
            recipientMembershipPublicId: $model->recipientMembership?->public_id
                ?? (string) ($model->recipient_membership_id ?? ''),
            deliveredAt: $model->delivered_at?->toIso8601String(),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toTemplate(NotificationTemplateModel $model): NotificationTemplate
    {
        return new NotificationTemplate(
            publicId: $model->public_id,
            moduleKey: (string) $model->module_key,
            type: (string) $model->type,
            templateType: self::enumValue($model->template_type, 'module'),
            subject: $model->subject,
            body: (string) $model->body,
            channels: is_array($model->channels) ? $model->channels : [],
            variables: is_array($model->variables) ? $model->variables : [],
            scope: self::enumValue($model->scope, 'organization'),
        );
    }

    /**
     * @param  Builder<EnterpriseNotification>  $query
     */
    public static function applyWorkspaceScope(Builder $query, ?string $workspaceId): Builder
    {
        if ($workspaceId === null) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($workspaceId) {
            $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
        });
    }

    public static function enumValue(mixed $value, string $default): string
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
