<?php

namespace App\Services\Form;

use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormHealthReport;
use App\Modules\Sdk\Form\Data\FormStatistics;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Modules\Sdk\Form\Data\FormSubmissionResult;
use App\Modules\Sdk\Form\Data\FormValidationReport;
use App\Modules\Sdk\Form\Exceptions\FormNotFoundException;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DynamicFormDevelopmentService
{
    public function __construct(
        private readonly DynamicFormRegistryService $registryService,
        private readonly DynamicFormDefinitionService $definitionService,
        private readonly DynamicFormGeneratorService $generatorService,
        private readonly DynamicFormRendererService $rendererService,
        private readonly DynamicFormValidationService $validationService,
        private readonly DynamicFormSubmissionService $submissionService,
        private readonly DynamicFormDraftService $draftService,
        private readonly DynamicFormActivityService $activityService,
        private readonly DynamicFormHealthService $healthService,
        private readonly DynamicFormStatisticsService $statisticsService,
        private readonly DynamicFormAuditRecorder $auditRecorder,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    /**
     * @return list<FormDefinition>
     */
    public function listDefinitions(TenantContext $context, ?string $moduleKey = null): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->list($moduleKey);
    }

    public function showDefinition(TenantContext $context, string $moduleKey, string $formKey): FormDefinition
    {
        return $this->findDefinition($context, $moduleKey, $formKey);
    }

    public function findDefinition(TenantContext $context, string $moduleKey, string $formKey): FormDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->registryService->find($moduleKey, $formKey);

        if ($definition === null) {
            throw new FormNotFoundException(sprintf('Form [%s.%s] was not found.', $moduleKey, $formKey));
        }

        return $definition;
    }

    public function findDefinitionByPublicId(TenantContext $context, string $publicId): FormDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->registryService->findByPublicId($publicId);

        if ($definition === null) {
            throw new FormNotFoundException(sprintf('Form [%s] was not found.', $publicId));
        }

        return $definition;
    }

    /**
     * @return list<FormDefinition>
     */
    public function listByEntity(TenantContext $context, string $moduleKey, string $entityKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->findByEntity($moduleKey, $entityKey);
    }

    /**
     * @param  FormDefinition|array<string, mixed>  $source
     */
    public function registerDefinition(TenantContext $context, mixed $source): FormDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->register($source);
    }

    public function updateDefinition(TenantContext $context, FormDefinition $definition): FormDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->update($definition);
    }

    public function deleteDefinition(TenantContext $context, string $moduleKey, string $formKey): void
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $this->definitionService->delete($moduleKey, $formKey);
    }

    /**
     * @param  list<array<string, mixed>>  $forms
     * @return list<FormDefinition>
     */
    public function registerFromManifestForms(TenantContext $context, array $forms, string $moduleKey): array
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->registerFromManifestForms($forms, $moduleKey);
    }

    public function generateCreateForm(TenantContext $context, string $moduleKey, string $entityKey): FormDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->generatorService->generateCreateForm($moduleKey, $entityKey);
    }

    public function generateEditForm(TenantContext $context, string $moduleKey, string $entityKey): FormDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->generatorService->generateEditForm($moduleKey, $entityKey);
    }

    public function generateViewForm(TenantContext $context, string $moduleKey, string $entityKey): FormDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->generatorService->generateViewForm($moduleKey, $entityKey);
    }

    /**
     * @param  FormDefinition|array<string, mixed>  $source
     */
    public function validateDefinition(mixed $source): FormValidationReport
    {
        $definition = $source instanceof FormDefinition
            ? $source
            : FormDefinition::fromArray($source);
        $report = $this->validationService->validate($definition);
        $this->auditRecorder->recordValidated($definition);

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function renderForm(TenantContext $context, FormDefinition $definition, array $renderContext = []): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->rendererService->render($definition, $renderContext);
    }

    public function validateSubmission(
        TenantContext $context,
        FormSubmissionRequest $request,
        ?FormDefinition $definition = null,
    ): FormValidationReport {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition ??= $this->findDefinition($context, $request->moduleKey, $request->formKey);

        return $this->submissionService->validateOnly($request, $definition);
    }

    public function submitForm(
        TenantContext $context,
        FormSubmissionRequest $request,
        ?FormDefinition $definition = null,
    ): FormSubmissionResult {
        $this->requireCapability($context);
        $this->assertSubmit($context);

        $definition ??= $this->findDefinition($context, $request->moduleKey, $request->formKey);

        return $this->submissionService->submit($request, $definition);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function loadDraft(
        TenantContext $context,
        string $moduleKey,
        string $formKey,
        ?string $entityPublicId = null,
    ): ?array {
        $this->requireCapability($context);
        $this->assertDraft($context);

        return $this->draftService->loadLatest(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $formKey,
            $entityPublicId,
            $context->user->id,
        );
    }

    public function saveDraft(
        TenantContext $context,
        FormDefinition $definition,
        array $draftData,
        ?string $entityPublicId = null,
        ?\DateTimeInterface $expiresAt = null,
    ): array {
        $this->requireCapability($context);
        $this->assertDraft($context);

        $draft = $this->draftService->save(
            $context->organization->id,
            $context->workspace?->id,
            $definition,
            $draftData,
            $entityPublicId,
            $context->user->id,
            $context->membership->id,
            $expiresAt,
        );

        $this->auditRecorder->recordDraftSaved($definition->publicId);

        return $draft;
    }

    public function deleteDraft(
        TenantContext $context,
        string $moduleKey,
        string $formKey,
        ?string $entityPublicId = null,
    ): void {
        $this->requireCapability($context);
        $this->assertDraft($context);

        $this->draftService->delete(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $formKey,
            $entityPublicId,
            $context->user->id,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActivity(TenantContext $context, string $formPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->activityService->listForForm(
            $context->organization->id,
            $context->workspace?->id,
            $formPublicId,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSubmissions(
        TenantContext $context,
        string $moduleKey,
        string $formKey,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);

        $this->findDefinition($context, $moduleKey, $formKey);

        return $this->submissionService->listForForm(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $formKey,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function showSubmission(TenantContext $context, string $submissionPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->submissionService->findByPublicId(
            $context->organization->id,
            $context->workspace?->id,
            $submissionPublicId,
        );
    }

    public function deleteDraftByPublicId(TenantContext $context, string $draftPublicId): void
    {
        $this->requireCapability($context);
        $this->assertDraft($context);

        $draft = \App\Models\FormDraft::query()
            ->where('public_id', $draftPublicId)
            ->where('organization_id', $context->organization->id)
            ->when($context->workspace?->id !== null, fn ($q) => $q->where('workspace_id', $context->workspace->id))
            ->first();

        if ($draft === null) {
            throw new FormNotFoundException(sprintf('Form draft [%s] was not found.', $draftPublicId));
        }

        $this->draftService->deleteByPublicId($draftPublicId);
    }

    public function health(TenantContext $context): FormHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): FormStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope(
            $context->organization,
            $context->workspace,
        );
    }

    private function requireCapability(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'forms');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'forms.read')) {
            throw new HttpException(403, 'You do not have permission to read forms.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'forms.manage')) {
            throw new HttpException(403, 'You do not have permission to manage forms.');
        }
    }

    private function assertSubmit(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'forms.submit')) {
            throw new HttpException(403, 'You do not have permission to submit forms.');
        }
    }

    private function assertDraft(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'forms.draft')) {
            throw new HttpException(403, 'You do not have permission to manage form drafts.');
        }
    }
}
