<?php

namespace App\Services\Form;

use App\Models\FormActivityLog;
use App\Models\FormDefinition as FormDefinitionModel;
use App\Models\FormSubmission;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Support\Tenant\TenantContext;

class DynamicFormActivityService
{
    public function __construct(
        private readonly DynamicFormAuditRecorder $auditRecorder,
        private readonly DynamicFormSearchIndexer $searchIndexer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function log(
        EnterpriseScope $scope,
        string $organizationId,
        ?string $workspaceId,
        ?string $formDefinitionId,
        string $action,
        ?string $formSubmissionId = null,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $actorUserId = null,
        ?string $actorMembershipId = null,
        array $metadata = [],
    ): array {
        $model = FormActivityLog::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'form_definition_id' => $formDefinitionId,
            'form_submission_id' => $formSubmissionId,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'actor_user_id' => $actorUserId,
            'actor_membership_id' => $actorMembershipId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $this->auditRecorder->recordActivityLogged($action, $formDefinitionId, $formSubmissionId);
        $this->searchIndexer->indexActivityBestEffort($model, $scope);

        return DynamicFormMapper::toActivityReference($model);
    }

    public function logSubmission(FormSubmission $submission, string $action): array
    {
        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        return $this->log(
            scope: new EnterpriseScope(
                organizationPublicId: $context?->organizationPublicId ?? (string) $submission->organization_id,
                workspacePublicId: $context?->workspacePublicId,
                moduleKey: $submission->module_key,
            ),
            organizationId: $submission->organization_id,
            workspaceId: $submission->workspace_id,
            formDefinitionId: $submission->form_definition_id,
            action: $action,
            formSubmissionId: $submission->id,
            beforeState: null,
            afterState: [
                'status' => $submission->status,
                'entity_public_id' => $submission->entity_public_id,
            ],
            actorUserId: $submission->submitted_by_user_id,
            actorMembershipId: $submission->submitted_membership_id,
            metadata: is_array($submission->metadata) ? $submission->metadata : [],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForForm(
        string $organizationId,
        ?string $workspaceId,
        string $formDefinitionPublicId,
    ): array {
        $form = FormDefinitionModel::query()->where('public_id', $formDefinitionPublicId)->first();

        if ($form === null) {
            return [];
        }

        $query = FormActivityLog::query()
            ->where('organization_id', $organizationId)
            ->where('form_definition_id', $form->id)
            ->orderByDesc('created_at');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(fn (FormActivityLog $model) => DynamicFormMapper::toActivityReference($model))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForSubmission(
        string $organizationId,
        ?string $workspaceId,
        string $submissionPublicId,
    ): array {
        $submission = FormSubmission::query()->where('public_id', $submissionPublicId)->first();

        if ($submission === null) {
            return [];
        }

        $query = FormActivityLog::query()
            ->where('organization_id', $organizationId)
            ->where('form_submission_id', $submission->id)
            ->orderByDesc('created_at');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(fn (FormActivityLog $model) => DynamicFormMapper::toActivityReference($model))
            ->all();
    }
}
