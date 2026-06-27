<?php

namespace Tests\Feature\Services\Form;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\FormDefinition as FormDefinitionModel;
use App\Models\FormDraft;
use App\Models\FormSubmission;
use App\Models\Permission;
use App\Modules\Sdk\Development\BusinessModuleBase;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormDraftReference;
use App\Modules\Sdk\Form\Data\FormHealthReport;
use App\Modules\Sdk\Form\Data\FormStatistics;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Modules\Sdk\Form\Data\FormSubmissionResult;
use App\Modules\Sdk\Form\Data\FormValidationReport;
use App\Modules\Sdk\Form\Exceptions\FormRegistryException;
use App\Modules\Sdk\Form\Exceptions\FormValidationException;
use App\Services\Form\DynamicFormDevelopmentService;
use App\Services\Form\DynamicFormHealthService;
use App\Services\Form\DynamicFormMapper;
use App\Services\Form\DynamicFormRegistryService;
use App\Services\Form\DynamicFormRendererService;
use App\Services\Form\DynamicFormStatisticsService;
use App\Services\Form\DynamicFormValidationService;
use App\Services\Module\Development\BusinessModuleDevelopmentService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M5DynamicFormFrameworkTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_form_definition_dto_roundtrip(): void
    {
        $definition = FormDefinition::fromArray($this->sampleFormDefinition('crm.core', 'lead_create'));

        $roundtrip = FormDefinition::fromArray($definition->toArray());

        $this->assertSame('crm.core', $roundtrip->moduleKey);
        $this->assertSame('lead_create', $roundtrip->formKey);
        $this->assertSame('Lead Create', $roundtrip->name);
    }

    public function test_validation_report_dto_serializes(): void
    {
        $report = FormValidationReport::fromArray([
            'module_key' => 'crm.core',
            'form_key' => 'lead_create',
            'valid' => false,
            'issues' => [[
                'code' => 'missing_name',
                'message' => 'Form name is required.',
                'severity' => 'error',
                'field' => 'name',
            ]],
        ]);

        $this->assertFalse($report->toArray()['valid']);
        $this->assertCount(1, $report->jsonSerialize()['issues']);
    }

    public function test_health_report_dto_serializes(): void
    {
        $report = new FormHealthReport(
            enabled: true,
            definitions: 2,
            submissions: 5,
            drafts: 1,
            warnings: ['No form definitions are registered yet.'],
            status: 'warning',
        );

        $this->assertSame('warning', $report->toArray()['status']);
    }

    public function test_statistics_dto_serializes(): void
    {
        $statistics = FormStatistics::fromArray([
            'definitions' => 2,
            'submissions' => 5,
            'drafts' => 1,
            'registered_modules' => ['crm.core'],
        ]);

        $this->assertSame(2, $statistics->jsonSerialize()['definitions']);
        $this->assertSame(['crm.core'], $statistics->registeredModules);
    }

    public function test_draft_reference_serializes_public_id(): void
    {
        $reference = new FormDraftReference(
            formKey: 'lead_create',
            draftId: '01900000-0000-7000-8000-000000000701',
            publicId: '01900000-0000-7000-8000-000000000701',
            moduleKey: 'crm.core',
        );

        $payload = $reference->toArray();

        $this->assertArrayHasKey('public_id', $payload);
        $this->assertSame('01900000-0000-7000-8000-000000000701', $payload['public_id']);
    }

    public function test_submission_result_serializes(): void
    {
        $result = FormSubmissionResult::fromArray([
            'module_key' => 'crm.core',
            'form_key' => 'lead_create',
            'success' => true,
            'status' => 'submitted',
            'submission_id' => '01900000-0000-7000-8000-000000000702',
            'values' => ['name' => 'Acme'],
        ]);

        $this->assertTrue($result->toArray()['success']);
        $this->assertSame('Acme', $result->values['name']);
    }

    public function test_validator_accepts_valid_definition(): void
    {
        $report = app(DynamicFormValidationService::class)->validate(
            FormDefinition::fromArray($this->sampleFormDefinition('procurement.core', 'supplier_create')),
        );

        $this->assertTrue($report->valid);
    }

    public function test_validator_rejects_invalid_module_key(): void
    {
        $data = $this->sampleFormDefinition('INVALID KEY', 'record');
        $data['module_key'] = 'INVALID KEY';

        $this->expectException(FormValidationException::class);
        app(DynamicFormValidationService::class)->assertValid(FormDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_name(): void
    {
        $data = $this->sampleFormDefinition('finance.core', 'invoice_create');
        $data['name'] = '';

        $this->expectException(FormValidationException::class);
        app(DynamicFormValidationService::class)->assertValid(FormDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_form_key(): void
    {
        $data = $this->sampleFormDefinition('finance.core', '');
        $data['form_key'] = '';

        $this->expectException(FormValidationException::class);
        app(DynamicFormValidationService::class)->assertValid(FormDefinition::fromArray($data));
    }

    public function test_registry_registers_from_dto(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicFormRegistryService::class)->register(
            FormDefinition::fromArray($this->sampleFormDefinition('registry.dto.'.uniqid(), 'record')),
        );

        $this->assertNotEmpty($definition->publicId);
        $this->assertTrue(FormDefinitionModel::query()->where('module_key', $definition->moduleKey)->exists());
    }

    public function test_registry_registers_from_array(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicFormRegistryService::class)->register(
            $this->sampleFormDefinition('registry.array.'.uniqid(), 'record'),
        );

        $this->assertSame('record', $definition->formKey);
    }

    public function test_registry_duplicate_prevention(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $payload = $this->sampleFormDefinition('registry.dup.'.uniqid(), 'record');

        app(DynamicFormRegistryService::class)->register($payload);

        $this->expectException(FormRegistryException::class);
        app(DynamicFormRegistryService::class)->register($payload);
    }

    public function test_registry_list_and_find(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.list.'.uniqid();

        app(DynamicFormRegistryService::class)->register(
            $this->sampleFormDefinition($moduleKey, 'order_create'),
        );

        $found = app(DynamicFormRegistryService::class)->find($moduleKey, 'order_create');
        $listed = app(DynamicFormRegistryService::class)->list($moduleKey);

        $this->assertNotNull($found);
        $this->assertCount(1, $listed);
    }

    public function test_registry_register_from_manifest_forms(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'manifest.module.'.uniqid();

        $registered = app(DynamicFormRegistryService::class)->registerFromManifestForms([
            ['key' => 'customer_create', 'name' => 'Create Customer'],
            ['key' => 'supplier_create', 'name' => 'Create Supplier'],
        ], $moduleKey);

        $this->assertCount(2, $registered);
        $this->assertSame('customer_create', $registered[0]->formKey);
    }

    public function test_registry_find_by_public_id(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicFormRegistryService::class)->register(
            $this->sampleFormDefinition('registry.public.'.uniqid(), 'record'),
        );

        $found = app(DynamicFormRegistryService::class)->findByPublicId((string) $definition->publicId);

        $this->assertNotNull($found);
        $this->assertSame($definition->formKey, $found->formKey);
    }

    public function test_mapper_to_reference_public_id_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(DynamicFormRegistryService::class)->register(
            $this->sampleFormDefinition('mapper.ref.'.uniqid(), 'product_create'),
        );

        $model = FormDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->firstOrFail();

        $reference = DynamicFormMapper::toReference($model);

        $this->assertArrayHasKey('public_id', $reference);
        $this->assertArrayNotHasKey('id', $reference);
    }

    public function test_submission_validation_accepts_valid_values(): void
    {
        $definition = FormDefinition::fromArray($this->sampleFormDefinition('validation.ok.'.uniqid(), 'record'));
        $request = new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'Acme Corp'],
        );

        $report = app(DynamicFormValidationService::class)->validateSubmission($request, $definition);

        $this->assertTrue($report->valid);
    }

    public function test_submission_validation_rejects_missing_required_field(): void
    {
        $definition = FormDefinition::fromArray($this->sampleFormDefinition('validation.fail.'.uniqid(), 'record'));
        $request = new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: [],
        );

        $report = app(DynamicFormValidationService::class)->validateSubmission($request, $definition);

        $this->assertFalse($report->valid);
        $this->assertNotEmpty($report->issues);
    }

    public function test_renderer_returns_form_structure(): void
    {
        $definition = FormDefinition::fromArray($this->sampleFormDefinition('render.'.uniqid(), 'record'));
        $rendered = app(DynamicFormRendererService::class)->render($definition, ['mode' => 'create']);

        $this->assertSame($definition->moduleKey, $rendered['metadata']['module_key']);
        $this->assertSame($definition->formKey, $rendered['metadata']['form_key']);
        $this->assertArrayHasKey('fields', $rendered);
    }

    public function test_submit_creates_submission_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleFormDefinition('submit.'.uniqid(), 'record'),
        );

        $result = $service->submitForm($context, new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'Submitted Name'],
        ));

        $this->assertTrue($result->success);
        $this->assertTrue(FormSubmission::query()->where('public_id', $result->submissionId)->exists());
    }

    public function test_draft_save_and_latest(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleFormDefinition('draft.'.uniqid(), 'record'),
        );

        $saved = $service->saveDraft($context, $definition, ['name' => 'Draft Name']);
        $latest = $service->loadDraft($context, $definition->moduleKey, $definition->formKey);

        $this->assertSame($saved['public_id'], $latest['reference']['public_id']);
        $this->assertTrue(FormDraft::query()->where('public_id', $saved['public_id'])->exists());
    }

    public function test_draft_delete(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleFormDefinition('draft.delete.'.uniqid(), 'record'),
        );

        $saved = $service->saveDraft($context, $definition, ['name' => 'Draft']);
        $service->deleteDraftByPublicId($context, (string) $saved['public_id']);

        $this->assertSame(0, FormDraft::query()->where('public_id', $saved['public_id'])->count());
    }

    public function test_list_submissions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleFormDefinition('submissions.list.'.uniqid(), 'record'),
        );

        $service->submitForm($context, new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'One'],
        ));

        $submissions = $service->listSubmissions($context, $definition->moduleKey, $definition->formKey);

        $this->assertCount(1, $submissions);
        $this->assertArrayHasKey('public_id', $submissions[0]);
    }

    public function test_show_submission_by_public_id(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleFormDefinition('submissions.show.'.uniqid(), 'record'),
        );

        $result = $service->submitForm($context, new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'Show Me'],
        ));

        $submission = $service->showSubmission($context, (string) $result->submissionId);

        $this->assertSame((string) $result->submissionId, $submission['public_id']);
    }

    public function test_health_service_reports_status(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $health = app(DynamicFormHealthService::class)->health($context);

        $this->assertTrue($health->enabled);
        $this->assertContains('No form definitions are registered yet.', $health->warnings);
    }

    public function test_statistics_service_counts(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $definition = $service->registerDefinition(
            $context,
            $this->sampleFormDefinition('stats.'.uniqid(), 'record'),
        );

        $service->submitForm($context, new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'Stats'],
        ));
        $service->saveDraft($context, $definition, ['name' => 'Draft']);

        $stats = app(DynamicFormStatisticsService::class)->statisticsForScope(
            $context->organization,
            $context->workspace,
        );

        $this->assertGreaterThanOrEqual(1, $stats->definitions);
        $this->assertGreaterThanOrEqual(1, $stats->submissions);
        $this->assertGreaterThanOrEqual(1, $stats->drafts);
    }

    public function test_development_service_list_definitions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $service->registerDefinition($context, $this->sampleFormDefinition('dev.list.'.uniqid(), 'record'));

        $definitions = $service->listDefinitions($context);

        $this->assertNotEmpty($definitions);
    }

    public function test_development_service_show_definition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $registered = $service->registerDefinition($context, $this->sampleFormDefinition('dev.show.'.uniqid(), 'record'));

        $definition = $service->showDefinition($context, $registered->moduleKey, $registered->formKey);

        $this->assertSame($registered->formKey, $definition->formKey);
    }

    public function test_development_service_render_form(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $registered = $service->registerDefinition($context, $this->sampleFormDefinition('dev.render.'.uniqid(), 'record'));

        $rendered = $service->renderForm($context, $registered, ['mode' => 'create']);

        $this->assertSame($registered->formKey, $rendered['metadata']['form_key']);
    }

    public function test_development_service_validate_submission(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(DynamicFormDevelopmentService::class);
        $registered = $service->registerDefinition($context, $this->sampleFormDefinition('dev.validate.'.uniqid(), 'record'));

        $report = $service->validateSubmission($context, new FormSubmissionRequest(
            moduleKey: $registered->moduleKey,
            formKey: $registered->formKey,
            values: ['name' => 'Valid'],
        ));

        $this->assertTrue($report->valid);
    }

    public function test_development_service_health(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $health = app(DynamicFormDevelopmentService::class)->health($context);

        $this->assertInstanceOf(FormHealthReport::class, $health);
    }

    public function test_development_service_statistics(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $statistics = app(DynamicFormDevelopmentService::class)->statistics($context);

        $this->assertInstanceOf(FormStatistics::class, $statistics);
    }

    public function test_api_index_forms(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleForm($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/forms');

        $response->assertOk();
    }

    public function test_api_show_form(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey);

        $response->assertOk();
        $this->assertSame($definition->formKey, $response->json('data.form_key') ?? $response->json('form_key'));
    }

    public function test_api_render_form(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/render');

        $response->assertOk();
        $this->assertArrayHasKey('fields', $response->json('data') ?? $response->json());
    }

    public function test_api_validate_submission(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/validate', [
                'values' => ['name' => 'Valid Name'],
            ]);

        $response->assertOk();
        $this->assertTrue($response->json('data.valid') ?? $response->json('valid'));
    }

    public function test_api_submit_form(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/submit', [
                'values' => ['name' => 'API Submission'],
            ]);

        $response->assertCreated();
    }

    public function test_api_draft_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        $createResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/drafts', [
                'values' => ['name' => 'Draft Value'],
            ]);
        $createResponse->assertCreated();

        $latestResponse = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/drafts/latest');
        $latestResponse->assertOk();
    }

    public function test_api_list_submissions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        app(DynamicFormDevelopmentService::class)->submitForm($context, new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'Listed'],
        ));

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/submissions');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data') ?? $response->json());
    }

    public function test_api_show_submission_static_route(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        $result = app(DynamicFormDevelopmentService::class)->submitForm($context, new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'Static Route'],
        ));

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/forms/submissions/'.$result->submissionId);

        $response->assertOk();
    }

    public function test_api_delete_draft_static_route(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        $draft = app(DynamicFormDevelopmentService::class)->saveDraft(
            $context,
            $definition,
            ['name' => 'Delete Me'],
        );

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/forms/drafts/'.$draft['public_id']);

        $response->assertNoContent();
    }

    public function test_permission_catalog_includes_form_permissions(): void
    {
        $this->seedHeosPermissions();

        $this->assertTrue(Permission::query()->where('key', 'forms.read')->exists());
        $this->assertTrue(Permission::query()->where('key', 'forms.manage')->exists());
        $this->assertTrue(Permission::query()->where('key', 'forms.submit')->exists());
        $this->assertTrue(Permission::query()->where('key', 'forms.draft')->exists());
        $this->assertPermissionCatalogComplete();
    }

    public function test_member_can_submit_and_draft(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleForm($ownerContext);

        app()->instance(TenantContext::class, $memberContext);

        $submitResponse = $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/submit', [
                'values' => ['name' => 'Member Submission'],
            ]);
        $submitResponse->assertCreated();

        $draftResponse = $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/drafts', [
                'values' => ['name' => 'Member Draft'],
            ]);
        $draftResponse->assertCreated();
    }

    public function test_viewer_cannot_submit(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleForm($ownerContext);

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->postJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/submit', [
                'values' => ['name' => 'Viewer Submission'],
            ]);

        $response->assertForbidden();
    }

    public function test_viewer_cannot_draft(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleForm($ownerContext);

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->postJson('/api/v1/tenant/forms/'.$definition->moduleKey.'/'.$definition->formKey.'/drafts', [
                'values' => ['name' => 'Viewer Draft'],
            ]);

        $response->assertForbidden();
    }

    public function test_tenant_isolation_for_submissions(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        $definition = $this->registerSampleForm($contextA);

        $result = app(DynamicFormDevelopmentService::class)->submitForm($contextA, new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'Tenant A'],
        ));

        app()->instance(TenantContext::class, $contextB);

        $this->expectException(\App\Modules\Sdk\Form\Exceptions\FormNotFoundException::class);
        app(DynamicFormDevelopmentService::class)->showSubmission($contextB, (string) $result->submissionId);
    }

    public function test_module_doctor_includes_forms_health(): void
    {
        $this->seedHeosPlatform();

        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('forms', $report->platformSummary['enterprise']);
    }

    public function test_workspace_runtime_includes_forms(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['forms'] ?? false);
        $this->assertArrayHasKey('forms', $runtime->runtimeMetadata['enterprise'] ?? []);
    }

    public function test_config_forms_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.forms.enabled', true));
    }

    public function test_audit_action_recorded_on_submit(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleForm($context);

        app(DynamicFormDevelopmentService::class)->submitForm($context, new FormSubmissionRequest(
            moduleKey: $definition->moduleKey,
            formKey: $definition->formKey,
            values: ['name' => 'Audit Me'],
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::FormSubmitted->value)->exists());
    }

    public function test_business_module_base_forms_integration(): void
    {
        $module = new class extends BusinessModuleBase
        {
            protected string $moduleKey = 'demo.forms';

            public function forms(): array
            {
                return [[
                    'form_key' => 'customer_create',
                    'name' => 'Create Customer',
                ]];
            }
        };

        $this->assertCount(1, $module->forms());
        $this->assertSame('customer_create', $module->forms()[0]['form_key']);
    }

    public function test_business_module_installer_registers_manifest_forms(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'form.install.'.preg_replace('/[^a-z0-9.]/', '', uniqid());

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray([
                'module_key' => $moduleKey,
                'name' => 'Form Install Module',
                'version' => '1.0.0',
                'permissions' => [[
                    'key' => $moduleKey.'.records.read',
                    'name' => 'Read Records',
                    'domain' => 'business',
                ]],
                'routes' => [[
                    'name' => $moduleKey.'.records.index',
                    'method' => 'GET',
                    'uri' => '/records',
                    'action' => 'index',
                ]],
                'forms' => [[
                    'form_key' => 'order_create',
                    'name' => 'Create Order',
                ]],
                'dependencies' => ['heos.core'],
            ]),
        );

        app(BusinessModuleDevelopmentService::class)->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $this->assertTrue(FormDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('form_key', 'order_create')
            ->exists());
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleFormDefinition(string $moduleKey, string $formKey): array
    {
        return [
            'module_key' => $moduleKey,
            'form_key' => $formKey,
            'name' => $formKey === '' ? '' : ucwords(str_replace(['.', '-', '_'], ' ', $formKey)),
            'description' => 'Sample form definition.',
            'type' => 'create',
            'status' => 'registered',
            'visibility' => 'organization',
            'fields' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
            ]],
            'metadata' => ['owner' => 'platform'],
        ];
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'dynamic-forms-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function registerSampleForm(TenantContext $context): FormDefinition
    {
        app()->instance(TenantContext::class, $context);

        return app(DynamicFormDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleFormDefinition('sample.forms.'.uniqid(), 'record'),
        );
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'member');
    }

    private function viewerContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'viewer');
    }

    private function roleContext(TenantContext $ownerContext, string $roleKey): TenantContext
    {
        $user = $this->createActiveUser();
        $role = \App\Models\Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', $roleKey)
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $user,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }

    /**
     * @return array<string, string>
     */
    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }
}
