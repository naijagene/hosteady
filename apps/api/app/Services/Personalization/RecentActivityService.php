<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationRecentItem;
use App\Modules\Sdk\Personalization\Data\RecentItem;
use App\Modules\Sdk\Personalization\Exceptions\PersonalizationException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class RecentActivityService
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
        private readonly PersonalizationAuditRecorder $auditRecorder,
    ) {
    }

    /** @return list<RecentItem> */
    public function list(TenantContext $context, ?int $limit = null): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_recent_items')) {
            return [];
        }

        $limit ??= $this->maxHistory();

        $query = PersonalizationRecentItem::query();
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        $query->where('user_id', $context->user->id);

        return $query->orderByDesc('visited_at')->limit($limit)->get()
            ->map(fn (PersonalizationRecentItem $model) => PersonalizationMapper::toRecent($model))
            ->all();
    }

    public function record(TenantContext $context, string $subjectType, string $subjectPublicId, ?string $title = null): RecentItem
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_recent_items')) {
            throw new PersonalizationException('Personalization recent items table is not available.');
        }

        if ($subjectType === '' || $subjectPublicId === '') {
            throw new PersonalizationException('Recent item subject reference is required.');
        }

        $query = PersonalizationRecentItem::query()
            ->where('subject_type', $subjectType)
            ->where('subject_public_id', $subjectPublicId);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        $query->where('user_id', $context->user->id);

        /** @var PersonalizationRecentItem|null $existing */
        $existing = $query->first();

        if ($existing === null) {
            $existing = PersonalizationRecentItem::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'membership_id' => $context->membership->id,
                'user_id' => $context->user->id,
                'subject_type' => $subjectType,
                'subject_public_id' => $subjectPublicId,
                'title' => $title,
                'visited_at' => now(),
                'metadata' => [],
            ]);
        } else {
            $existing->fill([
                'title' => $title ?? $existing->title,
                'visited_at' => now(),
            ]);
            $existing->save();
        }

        $this->prune($context);
        $this->auditRecorder->recordRecentRecorded($existing->public_id);

        return PersonalizationMapper::toRecent($existing->fresh());
    }

    private function maxHistory(): int
    {
        return max(1, (int) config('heos.enterprise.personalization.recent_max', 50));
    }

    private function prune(TenantContext $context): void
    {
        $max = $this->maxHistory();

        $query = PersonalizationRecentItem::query();
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        $query->where('user_id', $context->user->id);

        $idsToKeep = (clone $query)
            ->orderByDesc('visited_at')
            ->limit($max)
            ->pluck('id')
            ->all();

        if ($idsToKeep === []) {
            return;
        }

        (clone $query)->whereNotIn('id', $idsToKeep)->delete();
    }
}
