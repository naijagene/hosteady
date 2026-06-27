<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\FormDefinitionResource;
use App\Http\Resources\FormDraftResource;
use App\Http\Resources\FormRenderResource;
use App\Http\Resources\FormSubmissionResource;
use App\Http\Resources\FormValidationReportResource;
use App\Models\FormDefinition;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Services\Form\DynamicFormDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DynamicFormController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DynamicFormDevelopmentService $developmentService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', FormDefinition::class);
        $context = app(TenantContext::class);

        return FormDefinitionResource::collection($this->developmentService->listDefinitions($context));
    }

    public function show(string $moduleKey, string $formKey): FormDefinitionResource
    {
        $this->authorize('view', FormDefinition::class);
        $context = app(TenantContext::class);

        return new FormDefinitionResource(
            $this->developmentService->showDefinition($context, $moduleKey, $formKey),
        );
    }

    public function render(Request $request, string $moduleKey, string $formKey): FormRenderResource
    {
        $this->authorize('view', FormDefinition::class);
        $context = app(TenantContext::class);
        $definition = $this->developmentService->showDefinition($context, $moduleKey, $formKey);

        return new FormRenderResource(
            $this->developmentService->renderForm(
                $context,
                $definition,
                $request->input('context', []),
            ),
        );
    }

    public function validateSubmission(Request $request, string $moduleKey, string $formKey): FormValidationReportResource
    {
        $this->authorize('submit', FormDefinition::class);
        $validated = $request->validate([
            'values' => ['required', 'array'],
            'entity_key' => ['nullable', 'string', 'max:128'],
            'entity_public_id' => ['nullable', 'string', 'max:64'],
        ]);

        $context = app(TenantContext::class);

        return new FormValidationReportResource(
            $this->developmentService->validateSubmission($context, new FormSubmissionRequest(
                moduleKey: $moduleKey,
                formKey: $formKey,
                values: $validated['values'],
                entityKey: $validated['entity_key'] ?? null,
                entityPublicId: $validated['entity_public_id'] ?? null,
            )),
        );
    }

    public function submit(Request $request, string $moduleKey, string $formKey): \Illuminate\Http\JsonResponse
    {
        $this->authorize('submit', FormDefinition::class);
        $validated = $request->validate([
            'values' => ['required', 'array'],
            'entity_key' => ['nullable', 'string', 'max:128'],
            'entity_public_id' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new FormSubmissionResource(
            $this->developmentService->submitForm($context, new FormSubmissionRequest(
                moduleKey: $moduleKey,
                formKey: $formKey,
                values: $validated['values'],
                entityKey: $validated['entity_key'] ?? null,
                entityPublicId: $validated['entity_public_id'] ?? null,
                metadata: $validated['metadata'] ?? [],
            )),
        ))->response()->setStatusCode(201);
    }

    public function storeDraft(Request $request, string $moduleKey, string $formKey): \Illuminate\Http\JsonResponse
    {
        $this->authorize('draft', FormDefinition::class);
        $validated = $request->validate([
            'values' => ['required', 'array'],
            'entity_key' => ['nullable', 'string', 'max:128'],
            'entity_public_id' => ['nullable', 'string', 'max:64'],
        ]);

        $context = app(TenantContext::class);
        $definition = $this->developmentService->showDefinition($context, $moduleKey, $formKey);

        return (new FormDraftResource(
            $this->developmentService->saveDraft(
                $context,
                $definition,
                $validated['values'],
                $validated['entity_public_id'] ?? null,
            ),
        ))->response()->setStatusCode(201);
    }

    public function latestDraft(Request $request, string $moduleKey, string $formKey): FormDraftResource|\Illuminate\Http\Response
    {
        $this->authorize('draft', FormDefinition::class);
        $context = app(TenantContext::class);

        $draft = $this->developmentService->loadDraft(
            $context,
            $moduleKey,
            $formKey,
            $request->query('entity_public_id'),
        );

        if ($draft === null) {
            return response()->noContent();
        }

        return new FormDraftResource($draft['reference']);
    }

    public function destroyDraft(string $draftPublicId): \Illuminate\Http\Response
    {
        $this->authorize('draft', FormDefinition::class);
        $context = app(TenantContext::class);

        $this->developmentService->deleteDraftByPublicId($context, $draftPublicId);

        return response()->noContent();
    }

    public function submissions(string $moduleKey, string $formKey): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', FormDefinition::class);
        $context = app(TenantContext::class);

        return FormSubmissionResource::collection(
            $this->developmentService->listSubmissions($context, $moduleKey, $formKey),
        );
    }

    public function showSubmission(string $submissionPublicId): FormSubmissionResource
    {
        $this->authorize('view', FormDefinition::class);
        $context = app(TenantContext::class);

        return new FormSubmissionResource(
            $this->developmentService->showSubmission($context, $submissionPublicId),
        );
    }
}
