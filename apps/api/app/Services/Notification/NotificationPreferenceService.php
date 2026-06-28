<?php

namespace App\Services\Notification;

use App\Models\NotificationPreference as NotificationPreferenceModel;
use App\Models\OrganizationMembership;
use App\Modules\Sdk\Notification\Contracts\NotificationPreferenceProvider;
use App\Modules\Sdk\Notification\Data\NotificationPreference;
use App\Modules\Sdk\Notification\Exceptions\NotificationPreferenceException;
use App\Services\Enterprise\Audit\EnterpriseNotificationAuditRecorder;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class NotificationPreferenceService implements NotificationPreferenceProvider
{
    public function __construct(
        private readonly EnterpriseNotificationAuditRecorder $legacyAuditRecorder,
    ) {
    }

    /**
     * @return list<NotificationPreference>
     */
    public function get(string $organizationId, string $membershipPublicId): array
    {
        $membershipId = $this->resolveMembershipId($organizationId, $membershipPublicId);

        return NotificationPreferenceModel::query()
            ->where('organization_id', $organizationId)
            ->where('membership_id', $membershipId)
            ->orderBy('type')
            ->orderBy('channel')
            ->get()
            ->map(fn (NotificationPreferenceModel $model) => $this->toDto($model))
            ->all();
    }

    public function update(string $organizationId, string $membershipPublicId, NotificationPreference $preference): NotificationPreference
    {
        $membershipId = $this->resolveMembershipId($organizationId, $membershipPublicId);

        $model = NotificationPreferenceModel::query()->firstOrNew([
            'organization_id' => $organizationId,
            'membership_id' => $membershipId,
            'channel' => $preference->channel,
            'type' => $preference->type,
        ]);

        if (! $model->exists) {
            $model->id = (string) Str::uuid7();
        }

        $model->fill([
            'enabled' => $preference->enabled,
            'preferred_channels_json' => $preference->preferredChannels,
            'digest_frequency' => $preference->digestFrequency,
            'quiet_hours_json' => $preference->quietHours,
        ]);
        $model->save();

        if (app()->bound(TenantContext::class)) {
            $this->legacyAuditRecorder->recordPreferenceUpdated(
                app(TenantContext::class),
                $preference->type,
                $preference->channel,
                $preference->enabled,
            );
        }

        return $this->toDto($model->fresh());
    }

    public function isChannelEnabled(
        string $organizationId,
        string $membershipPublicId,
        string $type,
        string $channel,
    ): bool {
        $membershipId = OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $membershipPublicId)
            ->value('id');

        if ($membershipId === null) {
            return false;
        }

        $preference = NotificationPreferenceModel::query()
            ->where('membership_id', $membershipId)
            ->where('organization_id', $organizationId)
            ->where('channel', $channel)
            ->where('type', $type)
            ->first();

        return $preference?->enabled ?? true;
    }

    private function resolveMembershipId(string $organizationId, string $membershipPublicId): string
    {
        $membershipId = OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $membershipPublicId)
            ->value('id');

        if ($membershipId === null) {
            throw new NotificationPreferenceException(sprintf('Membership [%s] was not found.', $membershipPublicId));
        }

        return $membershipId;
    }

    private function toDto(NotificationPreferenceModel $model): NotificationPreference
    {
        return new NotificationPreference(
            publicId: $model->public_id,
            channel: (string) $model->channel,
            type: (string) $model->type,
            enabled: (bool) $model->enabled,
            preferredChannels: is_array($model->preferred_channels_json) ? $model->preferred_channels_json : [],
            digestFrequency: $model->digest_frequency,
            quietHours: is_array($model->quiet_hours_json) ? $model->quiet_hours_json : [],
        );
    }
}
