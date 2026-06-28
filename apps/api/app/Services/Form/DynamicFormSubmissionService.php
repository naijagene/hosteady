<?php

namespace App\Services\Form;

use App\Models\FormDefinition as FormDefinitionModel;
use App\Models\FormSubmission;
use App\Modules\Sdk\Form\Contracts\FormSubmissionHandler;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Modules\Sdk\Form\Data\FormSubmissionResult;
use App\Modules\Sdk\Form\Data\FormValidationReport;
use App\Modules\Sdk\Form\Enums\FormSubmissionStatus;
use App\Modules\Sdk\Form\Enums\FormType;
use App\Modules\Sdk\Form\Exceptions\FormNotFoundException;
use App\Modules\Sdk\Document\Data\AttachmentRequest;
use App\Modules\Sdk\Document\Enums\AttachmentSubjectType;
use App\Services\DataRepository\EnterpriseEntityRecordFormBridge;
use App\Services\Document\EnterpriseAttachmentService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DynamicFormSubmissionService implements FormSubmissionHandler
{
    public function __construct(
        private readonly DynamicFormRegistryService $registryService,
        private readonly DynamicFormValidationService $validationService,
        private readonly DynamicFormActivityService $activityService,
        private readonly DynamicFormWorkflowBridge $workflowBridge,
        private readonly DynamicFormAuditRecorder $auditRecorder,
        private readonly DynamicFormDraftService $draftService,
        private readonly EnterpriseEntityRecordFormBridge $formBridge,
        private readonly EnterpriseAttachmentService $attachmentService,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly \App\Services\Rules\RuleFormBridge $ruleFormBridge,
    ) {
    }

    public function submit(
        FormSubmissionRequest $request,
        FormDefinition $definition,
    ): FormSubmissionResult {
        $report = $this->validateOnly($request, $definition);

        if (! $report->valid) {
            return new FormSubmissionResult(
                moduleKey: $definition->moduleKey,
                formKey: $definition->formKey,
                success: false,
                status: FormSubmissionStatus::Rejected->value,
                values: $request->values,
                metadata: ['validation_report' => $report->toArray()],
            );
        }

        if ($request->draft) {
            return $this->saveDraftSubmission($request, $definition);
        }

        return DB::transaction(function () use ($request, $definition, $report) {
            $entityPublicId = $this->bridgeEntityMutation($request, $definition);
            $submission = $this->persistSubmission($request, $definition, $report, $entityPublicId);
            $this->attachSubmissionDocuments($submission, $definition, $request);

            if ($request->draftId !== null) {
                $this->draftService->deleteByPublicId($request->draftId);
            }

            $this->activityService->logSubmission($submission, 'submitted');
            $this->auditRecorder->recordSubmission($submission);
            $this->workflowBridge->triggerSubmissionBestEffort($submission, $definition);
            $this->ruleFormBridge->dispatchSubmittedBestEffort($request, $definition, $submission);

            return new FormSubmissionResult(
                moduleKey: $definition->moduleKey,
                formKey: $definition->formKey,
                success: true,
                status: FormSubmissionStatus::Accepted->value,
                submissionId: $submission->public_id,
                entityPublicId: $entityPublicId,
                values: $request->values,
                metadata: ['validation_report' => $report->toArray()],
            );
        });
    }

    public function validateOnly(
        FormSubmissionRequest $request,
        FormDefinition $definition,
    ): FormValidationReport {
        return $this->validationService->validateSubmission($request, $definition);
    }

    public function resolveDefinition(string $moduleKey, string $formKey): FormDefinition
    {
        $definition = $this->registryService->find($moduleKey, $formKey);

        if ($definition === null) {
            throw new FormNotFoundException(sprintf('Form [%s.%s] was not found.', $moduleKey, $formKey));
        }

        return $definition;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForForm(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $formKey,
    ): array {
        $query = FormSubmission::query()
            ->with('formDefinition')
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->whereHas('formDefinition', fn ($q) => $q->where('form_key', $formKey));

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->orderByDesc('submitted_at')
            ->get()
            ->map(fn (FormSubmission $model) => DynamicFormMapper::toSubmissionReference($model))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function findByPublicId(
        string $organizationId,
        ?string $workspaceId,
        string $submissionPublicId,
    ): array {
        $query = FormSubmission::query()
            ->with('formDefinition')
            ->where('public_id', $submissionPublicId)
            ->where('organization_id', $organizationId);

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        $submission = $query->first();

        if ($submission === null) {
            throw new FormNotFoundException(sprintf('Form submission [%s] was not found.', $submissionPublicId));
        }

        return DynamicFormMapper::toSubmissionReference($submission);
    }

    private function saveDraftSubmission(
        FormSubmissionRequest $request,
        FormDefinition $definition,
    ): FormSubmissionResult {
        if (! app()->bound(TenantContext::class)) {
            return new FormSubmissionResult(
                moduleKey: $definition->moduleKey,
                formKey: $definition->formKey,
                success: false,
                status: FormSubmissionStatus::Failed->value,
                values: $request->values,
                warnings: ['Tenant context is required to save drafts.'],
            );
        }

        $context = app(TenantContext::class);
        $draft = $this->draftService->save(
            $context->organization->id,
            $context->workspace?->id,
            $definition,
            $request->values,
            $request->entityPublicId,
            $context->user->id,
            $context->membership->id,
        );

        return new FormSubmissionResult(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            success: true,
            status: FormSubmissionStatus::Pending->value,
            submissionId: $draft['public_id'],
            entityPublicId: $request->entityPublicId,
            values: $request->values,
            metadata: ['draft' => true],
        );
    }

    private function bridgeEntityMutation(
        FormSubmissionRequest $request,
        FormDefinition $definition,
    ): ?string {
        if ($definition->entityKey === null) {
            return $request->entityPublicId;
        }

        if (! app()->bound(TenantContext::class)) {
            return $request->entityPublicId;
        }

        if (\App\Services\DataRepository\EnterpriseEntityRecordMapper::entityBindingEnabled($definition->metadata)) {
            return $this->formBridge->mutateFromForm(
                app(TenantContext::class),
                $definition,
                $request->values,
                $request->entityPublicId,
            );
        }

        $operation = match ($definition->type) {
            FormType::Create->value => 'create',
            FormType::Edit->value => 'update',
            default => null,
        };

        if ($operation === null) {
            return $request->entityPublicId;
        }

        return $operation === 'create'
            ? (string) Str::uuid7()
            : $request->entityPublicId;
    }

    private function persistSubmission(
        FormSubmissionRequest $request,
        FormDefinition $definition,
        FormValidationReport $report,
        ?string $entityPublicId,
    ): FormSubmission {
        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;
        $formModel = FormDefinitionModel::query()->where('public_id', $definition->publicId)->first();

        return FormSubmission::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $request->organizationId ?? $context?->organization->id,
            'workspace_id' => $request->workspaceId ?? $context?->workspace?->id,
            'form_definition_id' => $formModel?->id,
            'module_key' => $definition->moduleKey,
            'entity_key' => $definition->entityKey ?? $request->entityKey,
            'entity_public_id' => $entityPublicId,
            'status' => FormSubmissionStatus::Submitted->value,
            'submission_data' => $request->values,
            'validation_report' => $report->toArray(),
            'submitted_by_user_id' => $context?->user->id,
            'submitted_membership_id' => $context?->membership->id,
            'submitted_at' => now(),
            'metadata' => $request->metadata,
        ]);
    }

    private function attachSubmissionDocuments(
        FormSubmission $submission,
        FormDefinition $definition,
        FormSubmissionRequest $request,
    ): void {
        if (! app()->bound(TenantContext::class)) {
            return;
        }

        $context = app(TenantContext::class);

        try {
            $this->runtimeBridge->requireCapability($context, 'documents');
        } catch (\Throwable) {
            return;
        }

        foreach ($definition->fields as $field) {
            $fieldType = strtolower($field->type);

            if (! in_array($fieldType, ['file', 'document', 'attachment'], true)) {
                continue;
            }

            $value = $request->values[$field->key] ?? null;

            if (! is_array($value)) {
                continue;
            }

            $documentPublicId = $value['document_public_id']
                ?? $value['public_id']
                ?? $value['file_public_id']
                ?? null;

            if ($documentPublicId === null || $documentPublicId === '') {
                continue;
            }

            $this->attachmentService->attachBestEffort(
                $context->organization->id,
                $context->workspace?->id,
                new AttachmentRequest(
                    documentPublicId: (string) $documentPublicId,
                    subjectType: AttachmentSubjectType::FormSubmission->value,
                    subjectPublicId: $submission->public_id,
                    subjectModuleKey: $definition->moduleKey,
                    subjectEntityKey: $definition->entityKey,
                    metadata: ['field_key' => $field->key],
                ),
            );
        }
    }
}
