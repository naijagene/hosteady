<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationFavorite;
use App\Modules\Sdk\Personalization\Data\FavoriteItem;
use App\Modules\Sdk\Personalization\Exceptions\FavoriteException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class FavoriteService implements \App\Modules\Sdk\Personalization\Contracts\PersonalizationFavoriteStore
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
        private readonly PersonalizationAuditRecorder $auditRecorder,
    ) {
    }

    /** @return list<FavoriteItem> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_favorites')) {
            return [];
        }

        $query = PersonalizationFavorite::query();
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        $query->where('user_id', $context->user->id);

        return $query->get()
            ->sortBy(fn (PersonalizationFavorite $model) => (int) (is_array($model->metadata) ? ($model->metadata['sort_order'] ?? 9999) : 9999))
            ->values()
            ->map(fn (PersonalizationFavorite $model) => PersonalizationMapper::toFavorite($model))
            ->all();
    }

    public function add(
        TenantContext $context,
        string $subjectType,
        string $subjectPublicId,
        ?string $label = null,
        ?int $sortOrder = null,
    ): FavoriteItem {
        if (! $this->tableHealthSupport->isTablePresent('personalization_favorites')) {
            throw new FavoriteException('Personalization favorites table is not available.');
        }

        if ($subjectType === '' || $subjectPublicId === '') {
            throw new FavoriteException('Favorite subject reference is required.');
        }

        $query = PersonalizationFavorite::query()
            ->where('subject_type', $subjectType)
            ->where('subject_public_id', $subjectPublicId);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        $query->where('user_id', $context->user->id);

        /** @var PersonalizationFavorite|null $existing */
        $existing = $query->first();

        if ($existing === null) {
            $metadata = $sortOrder !== null ? ['sort_order' => $sortOrder] : [];
            $existing = PersonalizationFavorite::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'membership_id' => $context->membership->id,
                'user_id' => $context->user->id,
                'subject_type' => $subjectType,
                'subject_public_id' => $subjectPublicId,
                'label' => $label,
                'metadata' => $metadata,
            ]);
            $this->auditRecorder->recordFavoriteAdded($existing->public_id);
        } else {
            if ($label !== null) {
                $existing->label = $label;
            }
            $metadata = is_array($existing->metadata) ? $existing->metadata : [];
            if ($sortOrder !== null) {
                $metadata['sort_order'] = $sortOrder;
            }
            $existing->metadata = $metadata;
            $existing->save();
        }

        return PersonalizationMapper::toFavorite($existing->fresh());
    }

    public function remove(TenantContext $context, string $favoritePublicId): void
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_favorites')) {
            return;
        }

        $query = PersonalizationFavorite::query()->where('public_id', $favoritePublicId);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        $query->where('user_id', $context->user->id);

        $model = $query->first();
        if ($model !== null) {
            $model->delete();
            $this->auditRecorder->recordFavoriteRemoved($favoritePublicId);
        }
    }
}
