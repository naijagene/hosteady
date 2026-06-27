<?php

namespace App\Services\Form;

use App\Models\FormActivityLog;
use App\Models\FormDefinition as FormDefinitionModel;
use App\Models\FormSubmission;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Support\Tenant\TenantContext;

class DynamicFormSearchIndexer
{
    public function indexDefinitionBestEffort(FormDefinitionModel $definition): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                    moduleKey: $definition->module_key,
                ),
                entityType: 'form_definition',
                entityPublicId: $definition->public_id,
                displayName: $definition->name,
                keywords: implode(' ', array_filter([
                    $definition->module_key,
                    $definition->form_key,
                    $definition->entity_key,
                    $definition->name,
                ])),
                metadata: [
                    'module_key' => $definition->module_key,
                    'form_key' => $definition->form_key,
                    'entity_key' => $definition->entity_key,
                    'type' => $definition->type,
                    'status' => $definition->status,
                ],
                entityReference: new EntityReference(
                    type: 'form_definition',
                    publicId: $definition->public_id,
                    moduleKey: $definition->module_key,
                    label: $definition->name,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexSubmissionBestEffort(FormSubmission $submission, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: 'form_submission',
                entityPublicId: $submission->public_id,
                displayName: sprintf('%s submission', $submission->module_key),
                keywords: implode(' ', array_filter([
                    $submission->module_key,
                    $submission->entity_key,
                    $submission->entity_public_id,
                    $submission->status,
                ])),
                metadata: [
                    'module_key' => $submission->module_key,
                    'entity_key' => $submission->entity_key,
                    'status' => $submission->status,
                ],
                entityReference: new EntityReference(
                    type: 'form_submission',
                    publicId: $submission->public_id,
                    moduleKey: $submission->module_key,
                    label: $submission->status,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }

    public function indexActivityBestEffort(FormActivityLog $activity, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true) || ! app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);

            app(SearchIndexService::class)->upsert($context, new SearchIndexUpsertRequest(
                scope: $scope,
                entityType: 'form_activity',
                entityPublicId: $activity->public_id,
                displayName: sprintf('Form %s', $activity->action),
                keywords: implode(' ', array_filter([
                    $activity->action,
                    $activity->form_definition_id,
                    $activity->form_submission_id,
                ])),
                metadata: [
                    'action' => $activity->action,
                    'form_definition_id' => $activity->form_definition_id,
                    'form_submission_id' => $activity->form_submission_id,
                ],
                entityReference: new EntityReference(
                    type: 'form_activity',
                    publicId: $activity->public_id,
                    moduleKey: null,
                    label: $activity->action,
                ),
                visibility: 'organization',
            ));
        } catch (\Throwable) {
        }
    }
}
