<?php

namespace Tests\Feature\Services\DataRepository;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\EnterpriseEntityRecord;
use App\Models\Permission;
use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordCreateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordDeleteRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordFilter;
use App\Modules\Sdk\DataRepository\Data\EntityRecordHealthReport;
use App\Modules\Sdk\DataRepository\Data\EntityRecordMutationResult;
use App\Modules\Sdk\DataRepository\Data\EntityRecordQueryRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordRestoreRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordSort;
use App\Modules\Sdk\DataRepository\Data\EntityRecordStatistics;
use App\Modules\Sdk\DataRepository\Data\EntityRecordUpdateRequest;
use App\Modules\Sdk\DataRepository\Data\EntityRecordValidationReport;
use App\Modules\Sdk\DataRepository\Enums\EntityRecordFilterOperator;
use App\Modules\Sdk\DataRepository\Exceptions\EntityRecordNotFoundException;
use App\Modules\Sdk\DataRepository\Exceptions\EntityRecordValidationException;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Services\DataRepository\EnterpriseEntityRecordDevelopmentService;
use App\Services\DataRepository\EnterpriseEntityRecordFormBridge;
use App\Services\DataRepository\EnterpriseEntityRecordHealthService;
use App\Services\DataRepository\EnterpriseEntityRecordMapper;
use App\Services\DataRepository\EnterpriseEntityRecordMutationService;
use App\Services\DataRepository\EnterpriseEntityRecordQueryService;
use App\Services\DataRepository\EnterpriseEntityRecordRepositoryService;
use App\Services\DataRepository\EnterpriseEntityRecordValidationService;
use App\Services\Entity\EnterpriseEntityDevelopmentService;
use App\Services\Module\ModuleDoctorService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M6EnterpriseDataRepositoryTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_entity_record_dto_roundtrip(): void
    {
        $record = EntityRecord::fromArray([
            'module_key' => 'inventory.core',
            'entity_key' => 'product',
            'public_id' => '01900000-0000-7000-8000-000000000601',
            'record_data' => ['values' => ['name' => 'Widget']],
            'version' => 2,
        ]);

        $roundtrip = EntityRecord::fromArray($record->toArray());

        $this->assertSame('inventory.core', $roundtrip->moduleKey);
        $this->assertSame('Widget', $roundtrip->recordData->values['name']);
    }

    public function test_create_request_dto_serializes(): void
    {
        $request = EntityRecordCreateRequest::fromArray([
            'module_key' => 'crm.leads',
            'entity_key' => 'lead',
            'values' => ['name' => 'Acme'],
        ]);

        $this->assertSame('Acme', $request->toArray()['values']['name']);
    }

    public function test_update_request_dto_serializes(): void
    {
        $request = EntityRecordUpdateRequest::fromArray([
            'module_key' => 'crm.leads',
            'entity_key' => 'lead',
            'record_public_id' => (string) Str::uuid7(),
            'values' => ['name' => 'Updated'],
        ]);

        $this->assertSame('Updated', $request->jsonSerialize()['values']['name']);
    }

    public function test_delete_request_dto_serializes(): void
    {
        $request = EntityRecordDeleteRequest::fromArray([
            'module_key' => 'crm.leads',
            'entity_key' => 'lead',
            'record_public_id' => (string) Str::uuid7(),
        ]);

        $this->assertSame('lead', $request->toArray()['entity_key']);
    }

    public function test_restore_request_dto_serializes(): void
    {
        $request = EntityRecordRestoreRequest::fromArray([
            'module_key' => 'crm.leads',
            'entity_key' => 'lead',
            'record_public_id' => (string) Str::uuid7(),
        ]);

        $this->assertSame('crm.leads', $request->toArray()['module_key']);
    }

    public function test_mutation_result_dto_serializes(): void
    {
        $result = EntityRecordMutationResult::fromArray([
            'module_key' => 'demo.core',
            'entity_key' => 'asset',
            'mutation_type' => 'create',
            'success' => true,
            'record_public_id' => (string) Str::uuid7(),
        ]);

        $this->assertTrue($result->jsonSerialize()['success']);
    }

    public function test_query_request_dto_serializes_filters_and_sorts(): void
    {
        $request = EntityRecordQueryRequest::fromArray([
            'module_key' => 'demo.core',
            'entity_key' => 'asset',
            'filters' => [['field' => 'name', 'operator' => 'eq', 'value' => 'A']],
            'sorts' => [['field' => 'name', 'direction' => 'asc']],
            'page' => 2,
            'per_page' => 10,
        ]);

        $this->assertCount(1, $request->filters);
        $this->assertSame(2, $request->page);
    }

    public function test_query_result_dto_serializes(): void
    {
        $result = \App\Modules\Sdk\DataRepository\Data\EntityRecordQueryResult::fromArray([
            'module_key' => 'demo.core',
            'entity_key' => 'asset',
            'records' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 25,
            'total_pages' => 0,
        ]);

        $this->assertSame(0, $result->total);
    }

    public function test_filter_dto_serializes(): void
    {
        $filter = EntityRecordFilter::fromArray([
            'field' => 'status',
            'operator' => EntityRecordFilterOperator::Equals->value,
            'value' => 'active',
        ]);

        $this->assertSame('status', $filter->toArray()['field']);
    }

    public function test_sort_dto_serializes(): void
    {
        $sort = EntityRecordSort::fromArray(['field' => 'name', 'direction' => 'desc']);

        $this->assertSame('desc', $sort->jsonSerialize()['direction']);
    }

    public function test_projection_dto_serializes(): void
    {
        $projection = \App\Modules\Sdk\DataRepository\Data\EntityRecordProjection::fromArray([
            'fields' => ['name', 'code'],
            'include_metadata' => true,
        ]);

        $this->assertTrue($projection->includeMetadata);
    }

    public function test_statistics_dto_serializes(): void
    {
        $statistics = EntityRecordStatistics::fromArray([
            'records' => 3,
            'versions' => 5,
            'links' => 2,
            'activity_logs' => 4,
            'registered_modules' => ['demo.core'],
        ]);

        $this->assertSame(3, $statistics->jsonSerialize()['records']);
    }

    public function test_health_report_dto_serializes(): void
    {
        $report = EntityRecordHealthReport::fromArray([
            'enabled' => true,
            'records' => 1,
            'versions' => 1,
            'links' => 0,
            'activity_logs' => 1,
            'status' => 'healthy',
        ]);

        $this->assertSame('healthy', $report->toArray()['status']);
    }

    public function test_validation_report_dto_serializes(): void
    {
        $report = EntityRecordValidationReport::fromArray([
            'module_key' => 'demo.core',
            'entity_key' => 'asset',
            'valid' => false,
            'issues' => [[
                'code' => 'required_field',
                'message' => 'Field [name] is required.',
                'severity' => 'error',
                'field' => 'name',
            ]],
        ]);

        $this->assertFalse($report->valid);
    }

    public function test_validator_accepts_valid_create_payload(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $report = app(EnterpriseEntityRecordValidationService::class)->validateCreate(
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: ['name' => 'Valid Record'],
            ),
            $definition,
        );

        $this->assertTrue($report->valid);
    }

    public function test_validator_rejects_missing_required_field(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $report = app(EnterpriseEntityRecordValidationService::class)->validateCreate(
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: [],
            ),
            $definition,
        );

        $this->assertFalse($report->valid);
    }

    public function test_validator_rejects_unknown_field_without_allow_extra_fields(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $report = app(EnterpriseEntityRecordValidationService::class)->validateCreate(
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: ['name' => 'Ok', 'unexpected' => 'nope'],
            ),
            $definition,
        );

        $this->assertFalse($report->valid);
    }

    public function test_validator_rejects_invalid_string_type(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context, fields: [[
            'field_key' => 'amount',
            'label' => 'Amount',
            'field_type' => 'integer',
            'required' => true,
        ]]);

        $report = app(EnterpriseEntityRecordValidationService::class)->validateCreate(
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: ['amount' => 'not-a-number'],
            ),
            $definition,
        );

        $this->assertFalse($report->valid);
    }

    public function test_validator_rejects_read_only_field_on_update(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context, fields: [[
            'field_key' => 'code',
            'label' => 'Code',
            'field_type' => 'string',
            'required' => true,
            'metadata' => ['read_only' => true],
        ]]);

        $report = app(EnterpriseEntityRecordValidationService::class)->validateUpdate(
            new EntityRecordUpdateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: (string) Str::uuid7(),
                values: ['code' => 'NEW'],
            ),
            $definition,
            ['code' => 'OLD'],
        );

        $this->assertFalse($report->valid);
    }

    public function test_validator_assert_valid_throws(): void
    {
        $this->expectException(EntityRecordValidationException::class);
        app(EnterpriseEntityRecordValidationService::class)->assertValid(new EntityRecordValidationReport(
            moduleKey: 'demo',
            entityKey: 'x',
            valid: false,
        ));
    }

    public function test_repository_resolves_definition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $resolved = app(EnterpriseEntityRecordRepositoryService::class)->resolveDefinition(
            $definition->moduleKey,
            $definition->entityKey,
        );

        $this->assertSame($definition->entityKey, $resolved->entityKey);
    }

    public function test_repository_create_persists_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $record = app(EnterpriseEntityRecordRepositoryService::class)->create(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: ['name' => 'Stored Record'],
            ),
        );

        $this->assertTrue(EnterpriseEntityRecord::query()->where('public_id', $record->publicId)->exists());
    }

    public function test_repository_find_returns_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Find Me']);

        $found = app(EnterpriseEntityRecordRepositoryService::class)->find(
            $context->organization->id,
            $context->workspace->id,
            $definition->moduleKey,
            $definition->entityKey,
            $created->publicId,
        );

        $this->assertNotNull($found);
        $this->assertSame('Find Me', $found->recordData->values['name']);
    }

    public function test_repository_update_merges_values_and_increments_version(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Before']);

        $updated = app(EnterpriseEntityRecordRepositoryService::class)->update(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordUpdateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: $created->publicId,
                values: ['name' => 'After'],
            ),
        );

        $this->assertSame('After', $updated->recordData->values['name']);
        $this->assertSame(2, $updated->version);
    }

    public function test_repository_delete_soft_deletes_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Delete Me']);

        app(EnterpriseEntityRecordRepositoryService::class)->delete(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordDeleteRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: $created->publicId,
            ),
        );

        $this->assertSoftDeleted('enterprise_entity_records', ['public_id' => $created->publicId]);
    }

    public function test_repository_restore_restores_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Restore Me']);

        app(EnterpriseEntityRecordRepositoryService::class)->delete(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordDeleteRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: $created->publicId,
            ),
        );

        $restored = app(EnterpriseEntityRecordRepositoryService::class)->restore(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordRestoreRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: $created->publicId,
            ),
        );

        $this->assertNull($restored->deletedAt);
    }

    public function test_mutation_service_create_returns_success(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $result = app(EnterpriseEntityRecordMutationService::class)->create(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: ['name' => 'Mutation Create'],
            ),
        );

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->recordPublicId);
    }

    public function test_mutation_service_update_returns_success(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Old']);

        $result = app(EnterpriseEntityRecordMutationService::class)->update(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordUpdateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: $created->publicId,
                values: ['name' => 'New'],
            ),
        );

        $this->assertTrue($result->success);
    }

    public function test_mutation_service_delete_returns_success(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Delete']);

        $result = app(EnterpriseEntityRecordMutationService::class)->delete(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordDeleteRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: $created->publicId,
            ),
        );

        $this->assertTrue($result->success);
    }

    public function test_mutation_service_restore_returns_success(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Restore']);

        app(EnterpriseEntityRecordMutationService::class)->delete(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordDeleteRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: $created->publicId,
            ),
        );

        $result = app(EnterpriseEntityRecordMutationService::class)->restore(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordRestoreRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                recordPublicId: $created->publicId,
            ),
        );

        $this->assertTrue($result->success);
    }

    public function test_query_service_filters_records_in_php(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Alpha']);
        $this->createSampleRecord($context, $definition, ['name' => 'Beta']);

        $result = app(EnterpriseEntityRecordQueryService::class)->query(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordQueryRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                filters: [new EntityRecordFilter('name', EntityRecordFilterOperator::Equals->value, 'Alpha')],
            ),
        );

        $this->assertSame(1, $result->total);
    }

    public function test_query_service_applies_search(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Searchable Item']);
        $this->createSampleRecord($context, $definition, ['name' => 'Other']);

        $result = app(EnterpriseEntityRecordQueryService::class)->query(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordQueryRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                search: 'searchable',
            ),
        );

        $this->assertSame(1, $result->total);
    }

    public function test_query_service_paginates_results(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        foreach (range(1, 3) as $i) {
            $this->createSampleRecord($context, $definition, ['name' => 'Item '.$i]);
        }

        $result = app(EnterpriseEntityRecordQueryService::class)->query(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordQueryRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                page: 1,
                perPage: 2,
            ),
        );

        $this->assertSame(3, $result->total);
        $this->assertCount(2, $result->records);
    }

    public function test_version_service_creates_snapshot_on_mutation(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        app(EnterpriseEntityRecordMutationService::class)->create(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: ['name' => 'Versioned'],
            ),
        );

        $this->assertDatabaseHas('enterprise_entity_record_versions', [
            'module_key' => $definition->moduleKey,
            'entity_key' => $definition->entityKey,
            'version_number' => 1,
        ]);
    }

    public function test_development_service_lists_versions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Version List']);

        $versions = app(EnterpriseEntityRecordDevelopmentService::class)->listVersions(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $created->publicId,
        );

        $this->assertNotEmpty($versions);
    }

    public function test_development_service_lists_activity(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Activity']);

        $activity = app(EnterpriseEntityRecordDevelopmentService::class)->listActivity(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $created->publicId,
        );

        $this->assertNotEmpty($activity);
    }

    public function test_link_and_list_links(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $source = $this->createSampleRecord($context, $definition, ['name' => 'Source']);
        $target = $this->createSampleRecord($context, $definition, ['name' => 'Target']);

        $link = app(EnterpriseEntityRecordDevelopmentService::class)->createLink(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $source->publicId,
            [
                'target_module_key' => $definition->moduleKey,
                'target_entity_key' => $definition->entityKey,
                'target_record_public_id' => $target->publicId,
                'relationship_key' => 'related',
            ],
        );

        $links = app(EnterpriseEntityRecordDevelopmentService::class)->listLinks(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $source->publicId,
        );

        $this->assertSame($link['public_id'], $links[0]['public_id']);
    }

    public function test_delete_link(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $source = $this->createSampleRecord($context, $definition, ['name' => 'Source']);
        $target = $this->createSampleRecord($context, $definition, ['name' => 'Target']);

        $link = app(EnterpriseEntityRecordDevelopmentService::class)->createLink(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $source->publicId,
            [
                'target_module_key' => $definition->moduleKey,
                'target_entity_key' => $definition->entityKey,
                'target_record_public_id' => $target->publicId,
                'relationship_key' => 'related',
            ],
        );

        app(EnterpriseEntityRecordDevelopmentService::class)->deleteLink($context, $link['public_id']);

        $this->assertSoftDeleted('enterprise_entity_record_links', ['public_id' => $link['public_id']]);
    }

    public function test_mapper_entity_binding_enabled(): void
    {
        $this->assertTrue(EnterpriseEntityRecordMapper::entityBindingEnabled([
            'entity_binding' => ['enabled' => true],
        ]));
        $this->assertFalse(EnterpriseEntityRecordMapper::entityBindingEnabled([]));
    }

    public function test_health_service_reports_healthy_state(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Healthy']);

        $report = app(EnterpriseEntityRecordHealthService::class)->health($context);

        $this->assertTrue($report->enabled);
        $this->assertGreaterThanOrEqual(1, $report->records);
    }

    public function test_statistics_service_counts_records(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Counted']);

        $stats = app(EnterpriseEntityRecordDevelopmentService::class)->statistics($context);

        $this->assertGreaterThanOrEqual(1, $stats->records);
    }

    public function test_permission_catalog_includes_data_records_permissions(): void
    {
        $this->seedHeosPermissions();

        $this->assertTrue(Permission::query()->where('key', 'data.records.read')->exists());
        $this->assertTrue(Permission::query()->where('key', 'data.records.create')->exists());
        $this->assertTrue(Permission::query()->where('key', 'data.records.manage')->exists());
        $this->assertPermissionCatalogComplete();
    }

    public function test_api_create_and_show_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $createResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey, [
                'values' => ['name' => 'API Record'],
            ]);

        $createResponse->assertCreated();
        $recordPublicId = $createResponse->json('data.record_public_id') ?? $createResponse->json('record_public_id');

        $showResponse = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$recordPublicId);

        $showResponse->assertOk();
    }

    public function test_api_query_records(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Listed']);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey);

        $response->assertOk();
    }

    public function test_api_update_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Before']);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$created->publicId, [
                'values' => ['name' => 'After'],
            ]);

        $response->assertOk();
    }

    public function test_api_delete_and_restore_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Lifecycle']);

        $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$created->publicId)
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$created->publicId.'/restore')
            ->assertOk();
    }

    public function test_api_versions_and_activity_endpoints(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'History']);

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$created->publicId.'/versions')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$created->publicId.'/activity')
            ->assertOk();
    }

    public function test_api_create_and_delete_link(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $source = $this->createSampleRecord($context, $definition, ['name' => 'Source']);
        $target = $this->createSampleRecord($context, $definition, ['name' => 'Target']);

        $createLink = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$source->publicId.'/links', [
                'target_module_key' => $definition->moduleKey,
                'target_entity_key' => $definition->entityKey,
                'target_record_public_id' => $target->publicId,
                'relationship_key' => 'related',
            ]);

        $createLink->assertCreated();
        $linkPublicId = $createLink->json('data.public_id') ?? $createLink->json('public_id');

        $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/data/links/'.$linkPublicId)
            ->assertNoContent();
    }

    public function test_viewer_cannot_create_record(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleDefinition($ownerContext);

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->postJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey, [
                'values' => ['name' => 'Denied'],
            ]);

        $response->assertForbidden();
    }

    public function test_member_can_create_record(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleDefinition($ownerContext);

        app()->instance(TenantContext::class, $memberContext);

        $response = $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/data/'.$definition->moduleKey.'/'.$definition->entityKey, [
                'values' => ['name' => 'Member Record'],
            ]);

        $response->assertCreated();
    }

    public function test_audit_log_written_for_record_created(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $this->createSampleRecord($context, $definition, ['name' => 'Audited']);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DataRecordCreated->value)->exists());
    }

    public function test_audit_log_written_for_record_queried(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Query Audit']);

        app(EnterpriseEntityRecordDevelopmentService::class)->query($context, new EntityRecordQueryRequest(
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DataRecordQueried->value)->exists());
    }

    public function test_module_doctor_includes_data_repository_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('data_repository', $report->platformSummary['enterprise'] ?? $report->toArray()['platform_summary']['enterprise'] ?? []);
    }

    public function test_record_comment_and_tag_with_real_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Social']);

        $comment = app(EnterpriseEntityRecordDevelopmentService::class)->createComment(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $created->publicId,
            'Record comment',
        );

        $tag = app(EnterpriseEntityRecordDevelopmentService::class)->createTag($context, 'record.tag', 'Record Tag');
        app(EnterpriseEntityRecordDevelopmentService::class)->attachTag(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $created->publicId,
            $tag['public_id'],
        );

        $this->assertNotEmpty($comment['public_id']);
        $this->assertDatabaseHas('entity_taggables', [
            'module_key' => $definition->moduleKey,
            'entity_key' => $definition->entityKey,
            'entity_public_id' => $created->publicId,
        ]);
    }

    public function test_form_bridge_creates_record_when_entity_binding_enabled(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $formDefinition = \App\Modules\Sdk\Form\Data\FormDefinition::fromArray([
            'module_key' => $definition->moduleKey,
            'form_key' => 'create-record',
            'name' => 'Create Record',
            'entity_key' => $definition->entityKey,
            'type' => 'create',
            'metadata' => [
                'entity_binding' => ['enabled' => true, 'mode' => 'create'],
            ],
        ]);

        $publicId = app(EnterpriseEntityRecordFormBridge::class)->mutateFromForm(
            $context,
            $formDefinition,
            ['name' => 'Form Bridge Record'],
        );

        $this->assertNotEmpty($publicId);
        $this->assertDatabaseHas('enterprise_entity_records', [
            'public_id' => $publicId,
            'module_key' => $definition->moduleKey,
        ]);
    }

    public function test_tenant_isolation_for_records(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        $definition = $this->registerSampleDefinition($contextA);
        $created = $this->createSampleRecord($contextA, $definition, ['name' => 'Tenant A']);

        app()->instance(TenantContext::class, $contextB);

        $this->expectException(EntityRecordNotFoundException::class);
        app(EnterpriseEntityRecordDevelopmentService::class)->show(
            $contextB,
            $definition->moduleKey,
            $definition->entityKey,
            $created->publicId,
        );
    }

    public function test_development_service_show_throws_when_missing(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $this->expectException(EntityRecordNotFoundException::class);
        app(EnterpriseEntityRecordDevelopmentService::class)->show(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            (string) Str::uuid7(),
        );
    }

    public function test_query_service_sorts_records(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'B']);
        $this->createSampleRecord($context, $definition, ['name' => 'A']);

        $result = app(EnterpriseEntityRecordQueryService::class)->query(
            $context->organization->id,
            $context->workspace->id,
            new EntityRecordQueryRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                sorts: [new EntityRecordSort('name', 'asc')],
            ),
        );

        $this->assertSame('A', $result->records[0]->recordData->values['name']);
    }

    public function test_mapper_builds_search_text(): void
    {
        $text = EnterpriseEntityRecordMapper::buildSearchText(['name' => 'Hello', 'code' => 'X1']);

        $this->assertStringContainsString('hello', (string) $text);
    }

    public function test_health_assess_returns_array(): void
    {
        $assessment = app(EnterpriseEntityRecordHealthService::class)->assess();

        $this->assertArrayHasKey('enabled', $assessment);
        $this->assertArrayHasKey('records', $assessment);
    }

    public function test_runtime_contribution_includes_data_repository(): void
    {
        $context = $this->tenantContext();
        $contribution = app(EnterpriseEntityRecordHealthService::class)->runtimeContribution($context);

        $this->assertArrayHasKey('enabled', $contribution);
        $this->assertArrayHasKey('records', $contribution);
    }

    public function test_audit_actions_exist_for_all_record_events(): void
    {
        $this->assertSame('data.record.created', AuditAction::DataRecordCreated->value);
        $this->assertSame('data.record.updated', AuditAction::DataRecordUpdated->value);
        $this->assertSame('data.record.deleted', AuditAction::DataRecordDeleted->value);
        $this->assertSame('data.record.restored', AuditAction::DataRecordRestored->value);
        $this->assertSame('data.record.versioned', AuditAction::DataRecordVersioned->value);
        $this->assertSame('data.record.linked', AuditAction::DataRecordLinked->value);
        $this->assertSame('data.record.unlinked', AuditAction::DataRecordUnlinked->value);
        $this->assertSame('data.record.activity.logged', AuditAction::DataRecordActivityLogged->value);
        $this->assertSame('data.record.queried', AuditAction::DataRecordQueried->value);
    }

    public function test_config_enables_data_repository(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.data_repository.enabled'));
    }

    public function test_reference_dto_serializes_public_id_only(): void
    {
        $reference = \App\Modules\Sdk\DataRepository\Data\EntityRecordReference::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000701',
            'module_key' => 'demo.core',
            'entity_key' => 'asset',
        ]);

        $payload = $reference->toArray();

        $this->assertArrayHasKey('public_id', $payload);
    }

    public function test_record_data_dto_serializes_values(): void
    {
        $data = \App\Modules\Sdk\DataRepository\Data\EntityRecordData::fromArray([
            'values' => ['name' => 'Nested'],
        ]);

        $this->assertSame('Nested', $data->toArray()['values']['name']);
    }

    public function test_validator_allows_extra_fields_when_enabled(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            array_merge($this->sampleEntityDefinition('extra.module.'.uniqid(), 'record'), [
                'metadata' => ['allow_extra_fields' => true],
            ]),
        );

        $report = app(EnterpriseEntityRecordValidationService::class)->validateCreate(
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: ['name' => 'Ok', 'extra' => 'allowed'],
            ),
            $definition,
        );

        $this->assertTrue($report->valid);
    }

    public function test_projection_service_limits_fields(): void
    {
        $record = EntityRecord::fromArray([
            'module_key' => 'demo.core',
            'entity_key' => 'asset',
            'public_id' => (string) Str::uuid7(),
            'record_data' => ['values' => ['name' => 'A', 'code' => 'B']],
        ]);

        $projected = app(\App\Services\DataRepository\EnterpriseEntityRecordProjectionService::class)->project(
            $record,
            \App\Modules\Sdk\DataRepository\Data\EntityRecordProjection::fromArray(['fields' => ['name']]),
        );

        $this->assertArrayHasKey('name', $projected['values']);
        $this->assertArrayNotHasKey('code', $projected['values']);
    }

    public function test_data_provider_finds_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $created = $this->createSampleRecord($context, $definition, ['name' => 'Provider']);

        $found = app(\App\Services\DataRepository\EnterpriseEntityRecordDataProviderService::class)->findRecord(
            $context->organization->id,
            $context->workspace->id,
            $definition->moduleKey,
            $definition->entityKey,
            $created->publicId,
        );

        $this->assertNotNull($found);
    }

    public function test_table_bridge_returns_empty_without_entity_binding(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $rows = app(\App\Services\DataRepository\EnterpriseEntityRecordTableBridge::class)->fetchRows(
            $context->organization->id,
            $context->workspace->id,
            \App\Modules\Sdk\Table\Data\TableDefinition::fromArray([
                'module_key' => $definition->moduleKey,
                'table_key' => 'records',
                'name' => 'Records',
                'entity_key' => $definition->entityKey,
            ]),
        );

        $this->assertSame([], $rows);
    }

    public function test_table_bridge_returns_rows_with_entity_binding(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Table Row']);

        $rows = app(\App\Services\DataRepository\EnterpriseEntityRecordTableBridge::class)->fetchRows(
            $context->organization->id,
            $context->workspace->id,
            \App\Modules\Sdk\Table\Data\TableDefinition::fromArray([
                'module_key' => $definition->moduleKey,
                'table_key' => 'records',
                'name' => 'Records',
                'entity_key' => $definition->entityKey,
                'metadata' => ['entity_binding' => ['enabled' => true]],
            ]),
        );

        $this->assertCount(1, $rows);
    }

    public function test_report_bridge_counts_records(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Report Row']);

        $count = app(\App\Services\DataRepository\EnterpriseEntityRecordReportBridge::class)->count(
            $context->organization->id,
            $context->workspace->id,
            \App\Modules\Sdk\Report\Data\ReportDefinition::fromArray([
                'module_key' => $definition->moduleKey,
                'report_key' => 'summary',
                'name' => 'Summary',
                'entity_key' => $definition->entityKey,
            ]),
        );

        $this->assertSame(1, $count);
    }

    public function test_dashboard_bridge_counts_records(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $this->createSampleRecord($context, $definition, ['name' => 'Dashboard Row']);

        $count = app(\App\Services\DataRepository\EnterpriseEntityRecordDashboardBridge::class)->entityCount(
            $context->organization->id,
            $context->workspace->id,
            \App\Modules\Sdk\Dashboard\Data\DashboardWidget::fromArray([
                'widget_key' => 'entity_total',
                'widget_type' => 'metric',
                'data_source_type' => 'entity_count',
                'data_source_config' => [
                    'module_key' => $definition->moduleKey,
                    'entity_key' => $definition->entityKey,
                ],
            ]),
        );

        $this->assertSame(1, $count);
    }

    public function test_policy_resolver_member_permissions(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        $resolver = app(\App\Services\DataRepository\EnterpriseEntityRecordPolicyResolverService::class);

        $this->assertTrue($resolver->canRead($memberContext));
        $this->assertTrue($resolver->canCreate($memberContext));
        $this->assertFalse($resolver->canDelete($memberContext));
    }

    public function test_audit_log_written_for_record_linked(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $source = $this->createSampleRecord($context, $definition, ['name' => 'Source']);
        $target = $this->createSampleRecord($context, $definition, ['name' => 'Target']);

        app(EnterpriseEntityRecordDevelopmentService::class)->createLink(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $source->publicId,
            [
                'target_module_key' => $definition->moduleKey,
                'target_entity_key' => $definition->entityKey,
                'target_record_public_id' => $target->publicId,
                'relationship_key' => 'related',
            ],
        );

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DataRecordLinked->value)->exists());
    }

    /**
     * @param  list<array<string, mixed>>|null  $fields
     */
    private function registerSampleDefinition(TenantContext $context, ?array $fields = null): EntityDefinition
    {
        app()->instance(TenantContext::class, $context);

        return app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition('data.module.'.uniqid(), 'record', $fields),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function createSampleRecord(TenantContext $context, EntityDefinition $definition, array $values): EntityRecord
    {
        app()->instance(TenantContext::class, $context);

        $result = app(EnterpriseEntityRecordDevelopmentService::class)->create(
            $context,
            new EntityRecordCreateRequest(
                moduleKey: $definition->moduleKey,
                entityKey: $definition->entityKey,
                values: $values,
            ),
        );

        return $result->record ?? app(EnterpriseEntityRecordRepositoryService::class)->find(
            $context->organization->id,
            $context->workspace->id,
            $definition->moduleKey,
            $definition->entityKey,
            (string) $result->recordPublicId,
        );
    }

    /**
     * @param  list<array<string, mixed>>|null  $fields
     * @return array<string, mixed>
     */
    private function sampleEntityDefinition(string $moduleKey, string $entityKey, ?array $fields = null): array
    {
        return [
            'module_key' => $moduleKey,
            'entity_key' => $entityKey,
            'name' => ucwords(str_replace(['.', '-', '_'], ' ', $entityKey)),
            'description' => 'Sample entity definition.',
            'status' => 'registered',
            'visibility' => 'organization',
            'ownership_scope' => 'organization',
            'capabilities' => [
                'searchable' => true,
                'comments_enabled' => true,
                'tags_enabled' => true,
            ],
            'fields' => $fields ?? [[
                'field_key' => 'name',
                'label' => 'Name',
                'field_type' => 'string',
                'required' => true,
            ]],
            'metadata' => ['owner' => 'platform'],
        ];
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'enterprise-data-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
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
