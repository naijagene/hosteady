<?php

namespace App\Services\Personalization;

use App\Models\Organization;
use App\Models\PersonalizationFavorite;
use App\Models\PersonalizationOnboardingState;
use App\Models\PersonalizationPreference;
use App\Models\PersonalizationProfile;
use App\Models\PersonalizationRecentItem;
use App\Models\PersonalizationShortcut;
use App\Models\Workspace;

class PersonalizationStatisticsService
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function statisticsForScope(Organization $organization, ?Workspace $workspace): array
    {
        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return [
                'profiles' => 0,
                'preferences' => 0,
                'favorites' => 0,
                'recent_items' => 0,
                'shortcuts' => 0,
                'onboarding_states' => 0,
            ];
        }

        return [
            'profiles' => $this->countScoped(PersonalizationProfile::query(), $organization, $workspace),
            'preferences' => $this->countScoped(PersonalizationPreference::query(), $organization, $workspace),
            'favorites' => $this->countScoped(PersonalizationFavorite::query(), $organization, $workspace),
            'recent_items' => $this->countScoped(PersonalizationRecentItem::query(), $organization, $workspace),
            'shortcuts' => $this->countScoped(PersonalizationShortcut::query(), $organization, $workspace),
            'onboarding_states' => $this->countScoped(PersonalizationOnboardingState::query(), $organization, $workspace),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function countScoped($query, Organization $organization, ?Workspace $workspace): int
    {
        PersonalizationMapper::applyOrganizationScope($query, $organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $workspace?->id);

        return $query->count();
    }
}
