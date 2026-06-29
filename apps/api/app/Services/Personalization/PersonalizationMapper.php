<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationFavorite;
use App\Models\PersonalizationOnboardingState;
use App\Models\PersonalizationPreference;
use App\Models\PersonalizationRecentItem;
use App\Models\PersonalizationShortcut;
use App\Modules\Sdk\Personalization\Data\FavoriteItem;
use App\Modules\Sdk\Personalization\Data\OnboardingState;
use App\Modules\Sdk\Personalization\Data\PreferenceItem;
use App\Modules\Sdk\Personalization\Data\RecentItem;
use App\Modules\Sdk\Personalization\Data\ShortcutItem;
use App\Support\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PersonalizationMapper
{
    public const SCOPE_PRECEDENCE = ['global', 'organization', 'application', 'workspace', 'membership', 'user'];

    public static function toPreference(PersonalizationPreference $model): PreferenceItem
    {
        return new PreferenceItem(
            publicId: $model->public_id,
            preferenceKey: $model->preference_key,
            preferenceType: $model->value_type,
            value: self::decodePreferenceValue($model),
            scope: $model->scope,
        );
    }

    public static function toFavorite(PersonalizationFavorite $model): FavoriteItem
    {
        return new FavoriteItem(
            publicId: $model->public_id,
            favoriteType: $model->subject_type,
            subjectPublicId: $model->subject_public_id,
            label: $model->label,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toRecent(PersonalizationRecentItem $model): RecentItem
    {
        return new RecentItem(
            publicId: $model->public_id,
            itemType: $model->subject_type,
            subjectPublicId: $model->subject_public_id,
            label: $model->title,
            visitedAt: $model->visited_at?->toIso8601String(),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toShortcut(PersonalizationShortcut $model): ShortcutItem
    {
        return new ShortcutItem(
            publicId: $model->public_id,
            shortcutKey: $model->shortcut_key,
            label: $model->label,
            route: $model->route,
            target: $model->target,
            isActive: (bool) $model->is_active,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toOnboarding(PersonalizationOnboardingState $model): OnboardingState
    {
        return new OnboardingState(
            publicId: $model->public_id,
            onboardingKey: $model->flow_key,
            status: $model->status,
            currentStep: $model->current_step,
            completedSteps: is_array($model->completed_steps) ? $model->completed_steps : [],
            dismissedTips: is_array($model->dismissed_tips) ? $model->dismissed_tips : [],
            completedAt: $model->completed_at?->toIso8601String(),
        );
    }

    /**
     * @param  Builder<Model>  $query
     */
    public static function applyOrganizationScope(Builder $query, string $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * @param  Builder<Model>  $query
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

    /**
     * @param  Builder<Model>  $query
     */
    public static function applyMembershipScope(Builder $query, TenantContext $context): Builder
    {
        return $query
            ->where('membership_id', $context->membership->id)
            ->where('user_id', $context->user->id);
    }

    /**
     * @return array<string, mixed>
     */
    public static function scopeAttributes(TenantContext $context, string $scope = 'membership'): array
    {
        return [
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'membership_id' => $context->membership->id,
            'user_id' => $context->user->id,
            'scope' => $scope,
        ];
    }

    /**
     * @param  list<PreferenceItem>  $items
     * @return array<string, mixed>
     */
    public static function resolvePreferences(array $items): array
    {
        $resolved = [];
        $ordered = collect($items)->sortBy(function (PreferenceItem $item) {
            $index = array_search($item->scope, self::SCOPE_PRECEDENCE, true);

            return $index === false ? 999 : $index;
        });

        foreach ($ordered as $item) {
            if (! self::isExplicitPreferenceValue($item->value)) {
                continue;
            }

            $resolved[$item->preferenceKey] = $item->value;
        }

        return $resolved;
    }

    public static function isExplicitPreferenceValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if ($value === '') {
            return false;
        }

        return true;
    }

    private static function decodePreferenceValue(PersonalizationPreference $model): mixed
    {
        return match ($model->value_type) {
            'boolean' => $model->value_boolean,
            'integer' => $model->value_integer,
            'decimal' => $model->value_decimal !== null ? (float) $model->value_decimal : null,
            'json', 'map', 'list', 'enum' => is_array($model->value_payload) ? $model->value_payload : [],
            default => $model->value_string,
        };
    }
}
