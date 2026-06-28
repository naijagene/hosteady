<?php

namespace App\Services\Notification;

use App\Models\NotificationSchedule as NotificationScheduleModel;
use App\Models\OrganizationMembership;
use App\Modules\Sdk\Notification\Contracts\NotificationScheduler;
use App\Modules\Sdk\Notification\Data\NotificationSchedule;
use App\Modules\Sdk\Notification\Exceptions\NotificationException;
use Illuminate\Support\Str;

class NotificationScheduleService implements NotificationScheduler
{
    public function schedule(
        string $organizationId,
        ?string $workspaceId,
        string $membershipPublicId,
        NotificationSchedule $schedule,
    ): NotificationSchedule {
        $membershipId = $this->resolveMembershipId($organizationId, $membershipPublicId);

        $model = NotificationScheduleModel::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'membership_id' => $membershipId,
            'title' => $schedule->title,
            'cron_expression' => $schedule->cronExpression,
            'template_key' => $schedule->templateKey,
            'status' => $schedule->status,
            'metadata' => $schedule->metadata,
        ]);

        return $this->toDto($model);
    }

    /**
     * @return list<NotificationSchedule>
     */
    public function list(string $organizationId, ?string $workspaceId, string $membershipPublicId): array
    {
        $membershipId = $this->resolveMembershipId($organizationId, $membershipPublicId);

        $query = NotificationScheduleModel::query()
            ->where('organization_id', $organizationId)
            ->where('membership_id', $membershipId);

        if ($workspaceId !== null) {
            $query->where(function ($scoped) use ($workspaceId) {
                $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn (NotificationScheduleModel $model) => $this->toDto($model))
            ->all();
    }

    public function cancel(string $organizationId, ?string $workspaceId, string $schedulePublicId): NotificationSchedule
    {
        $model = NotificationScheduleModel::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $schedulePublicId)
            ->first();

        if ($model === null) {
            throw new NotificationException(sprintf('Schedule [%s] was not found.', $schedulePublicId));
        }

        $model->status = 'cancelled';
        $model->save();

        return $this->toDto($model->fresh());
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

    private function toDto(NotificationScheduleModel $model): NotificationSchedule
    {
        return new NotificationSchedule(
            publicId: $model->public_id,
            title: $model->title,
            cronExpression: $model->cron_expression,
            templateKey: $model->template_key,
            status: (string) $model->status,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }
}
