<?php

namespace App\Services\Form;

use App\Models\FormDefinition;
use App\Models\FormDraft;
use App\Models\FormSubmission;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Form\Data\FormStatistics;

class DynamicFormStatisticsService
{
    public function statistics(
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): FormStatistics {
        $definitionQuery = FormDefinition::query();
        $submissionQuery = FormSubmission::query();
        $draftQuery = FormDraft::query();

        if ($organizationId !== null) {
            $definitionQuery->where(function ($query) use ($organizationId, $workspaceId) {
                $query->where(function ($scoped) use ($organizationId, $workspaceId) {
                    $scoped->where('organization_id', $organizationId);
                    if ($workspaceId !== null) {
                        $scoped->where('workspace_id', $workspaceId);
                    } else {
                        $scoped->whereNull('workspace_id');
                    }
                })->orWhere(function ($global) {
                    $global->whereNull('organization_id')->whereNull('workspace_id');
                });
            });
            $submissionQuery->where('organization_id', $organizationId);
            $draftQuery->where('organization_id', $organizationId);
        }

        if ($workspaceId !== null) {
            $submissionQuery->where('workspace_id', $workspaceId);
            $draftQuery->where('workspace_id', $workspaceId);
        } elseif ($organizationId !== null) {
            $submissionQuery->whereNull('workspace_id');
            $draftQuery->whereNull('workspace_id');
        }

        $registeredModules = FormDefinition::query()
            ->when($organizationId !== null, function ($query) use ($organizationId, $workspaceId) {
                $query->where(function ($scoped) use ($organizationId, $workspaceId) {
                    $scoped->where('organization_id', $organizationId);
                    if ($workspaceId !== null) {
                        $scoped->where('workspace_id', $workspaceId);
                    } else {
                        $scoped->whereNull('workspace_id');
                    }
                })->orWhere(function ($global) {
                    $global->whereNull('organization_id')->whereNull('workspace_id');
                });
            })
            ->distinct()
            ->orderBy('module_key')
            ->pluck('module_key')
            ->values()
            ->all();

        return new FormStatistics(
            definitions: $definitionQuery->count(),
            submissions: $submissionQuery->count(),
            drafts: $draftQuery->count(),
            registeredModules: $registeredModules,
        );
    }

    public function statisticsForScope(?Organization $organization = null, ?Workspace $workspace = null): FormStatistics
    {
        return $this->statistics($organization?->id, $workspace?->id);
    }
}
