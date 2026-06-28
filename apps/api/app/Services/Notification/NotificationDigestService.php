<?php

namespace App\Services\Notification;

use App\Models\NotificationDigest as NotificationDigestModel;
use App\Models\OrganizationMembership;
use App\Modules\Sdk\Notification\Data\NotificationDigest;
use App\Modules\Sdk\Notification\Exceptions\NotificationException;
use Illuminate\Support\Str;

class NotificationDigestService
{
    public function create(
        string $organizationId,
        string $membershipPublicId,
        string $frequency = 'daily',
        int $notificationCount = 0,
        array $metadata = [],
    ): NotificationDigest {
        $membershipId = $this->resolveMembershipId($organizationId, $membershipPublicId);

        $model = NotificationDigestModel::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'membership_id' => $membershipId,
            'frequency' => $frequency,
            'status' => 'pending',
            'notification_count' => $notificationCount,
            'metadata' => $metadata,
        ]);

        return $this->toDto($model);
    }

    public function find(string $organizationId, string $digestPublicId): ?NotificationDigest
    {
        $model = NotificationDigestModel::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $digestPublicId)
            ->first();

        return $model !== null ? $this->toDto($model) : null;
    }

    /**
     * @return list<NotificationDigest>
     */
    public function list(string $organizationId, string $membershipPublicId, int $limit = 50): array
    {
        $membershipId = $this->resolveMembershipId($organizationId, $membershipPublicId);

        return NotificationDigestModel::query()
            ->where('organization_id', $organizationId)
            ->where('membership_id', $membershipId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (NotificationDigestModel $model) => $this->toDto($model))
            ->all();
    }

    public function markGenerated(string $organizationId, string $digestPublicId, int $notificationCount): NotificationDigest
    {
        $model = NotificationDigestModel::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $digestPublicId)
            ->first();

        if ($model === null) {
            throw new NotificationException(sprintf('Digest [%s] was not found.', $digestPublicId));
        }

        $model->fill([
            'status' => 'generated',
            'notification_count' => $notificationCount,
            'generated_at' => now(),
        ]);
        $model->save();

        return $this->toDto($model->fresh());
    }

    public function delete(string $organizationId, string $digestPublicId): NotificationDigest
    {
        $model = NotificationDigestModel::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $digestPublicId)
            ->first();

        if ($model === null) {
            throw new NotificationException(sprintf('Digest [%s] was not found.', $digestPublicId));
        }

        $dto = $this->toDto($model);
        $model->delete();

        return $dto;
    }

    private function resolveMembershipId(string $organizationId, string $membershipPublicId): string
    {
        $membershipId = OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $membershipPublicId)
            ->value('id');

        if ($membershipId === null) {
            throw new NotificationException(sprintf('Membership [%s] was not found.', $membershipPublicId));
        }

        return $membershipId;
    }

    private function toDto(NotificationDigestModel $model): NotificationDigest
    {
        return new NotificationDigest(
            publicId: $model->public_id,
            frequency: (string) $model->frequency,
            status: (string) $model->status,
            notificationCount: (int) $model->notification_count,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }
}
