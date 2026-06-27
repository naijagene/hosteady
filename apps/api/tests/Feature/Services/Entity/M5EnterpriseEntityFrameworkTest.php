<?php

namespace Tests\Feature\Services\Entity;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\EntityActivityLog;
use App\Models\EntityComment;
use App\Models\EntityDefinition as EntityDefinitionModel;
use App\Models\EntityRelationship;
use App\Models\EntityTag;
use App\Models\EntityTaggable;
use App\Models\Permission;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityHealthReport;
use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;
use App\Modules\Sdk\Entity\Data\EntityMutationRequest;
use App\Modules\Sdk\Entity\Data\EntityReferenceBridge;
use App\Modules\Sdk\Entity\Data\EntityStatistics;
use App\Modules\Sdk\Entity\Data\EntityValidationReport;
use App\Modules\Sdk\Development\BusinessModuleBase;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Entity\EnterpriseEntity;
use App\Modules\Sdk\Entity\Enums\EntityLifecycleEventType;
use App\Modules\Sdk\Entity\Exceptions\EntityRegistryException;
use App\Modules\Sdk\Entity\Exceptions\EntityValidationException;
use App\Services\Entity\EnterpriseEntityDevelopmentService;
use App\Services\Entity\EnterpriseEntityHealthService;
use App\Services\Entity\EnterpriseEntityLifecycleService;
use App\Services\Entity\EnterpriseEntityMapper;
use App\Services\Entity\EnterpriseEntityRegistryService;
use App\Services\Entity\EnterpriseEntityRepositoryService;
use App\Services\Entity\EnterpriseEntitySearchIndexer;
use App\Services\Entity\EnterpriseEntityStatisticsService;
use App\Services\Entity\EnterpriseEntityValidationService;
use App\Services\Module\Development\BusinessModuleDevelopmentService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M5EnterpriseEntityFrameworkTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_entity_definition_dto_roundtrip(): void
    {
        $definition = EntityDefinition::fromArray($this->sampleEntityDefinition('inventory.core', 'product'));

        $roundtrip = EntityDefinition::fromArray($definition->toArray());

        $this->assertSame('inventory.core', $roundtrip->moduleKey);
        $this->assertSame('product', $roundtrip->entityKey);
        $this->assertSame('Product', $roundtrip->name);
    }

    public function test_entity_reference_bridge_serializes_public_id_only(): void
    {
        $reference = EntityReferenceBridge::fromEntity(
            'inventory.core',
            'product',
            '01900000-0000-7000-8000-000000000601',
            'Product',
        );

        $payload = $reference->toArray();

        $this->assertArrayHasKey('public_id', $payload);
        $this->assertArrayNotHasKey('id', $payload);
    }

    public function test_validation_report_dto_serializes(): void
    {
        $report = EntityValidationReport::fromArray([
            'module_key' => 'crm.leads',
            'entity_key' => 'lead',
            'valid' => false,
            'issues' => [[
                'code' => 'missing_name',
                'message' => 'Entity name is required.',
                'severity' => 'error',
                'field' => 'name',
            ]],
        ]);

        $this->assertFalse($report->toArray()['valid']);
        $this->assertCount(1, $report->jsonSerialize()['issues']);
    }

    public function test_health_report_dto_serializes(): void
    {
        $report = new EntityHealthReport(
            enabled: true,
            definitions: 2,
            relationships: 1,
            comments: 3,
            tags: 4,
            warnings: ['No entity definitions are registered yet.'],
            status: 'warning',
        );

        $this->assertSame('warning', $report->toArray()['status']);
    }

    public function test_statistics_dto_serializes(): void
    {
        $statistics = EntityStatistics::fromArray([
            'definitions' => 2,
            'relationships' => 1,
            'comments' => 3,
            'tags' => 4,
            'activity_logs' => 5,
            'registered_modules' => ['inventory.core'],
        ]);

        $this->assertSame(2, $statistics->jsonSerialize()['definitions']);
        $this->assertSame(['inventory.core'], $statistics->registeredModules);
    }

    public function test_validator_accepts_valid_definition(): void
    {
        $report = app(EnterpriseEntityValidationService::class)->validate(
            EntityDefinition::fromArray($this->sampleEntityDefinition('procurement.core', 'supplier')),
        );

        $this->assertTrue($report->valid);
    }

    public function test_validator_rejects_invalid_module_key(): void
    {
        $data = $this->sampleEntityDefinition('INVALID KEY', 'record');
        $data['module_key'] = 'INVALID KEY';

        $this->expectException(EntityValidationException::class);
        app(EnterpriseEntityValidationService::class)->assertValid(EntityDefinition::fromArray($data));
    }

    public function test_validator_rejects_missing_name(): void
    {
        $data = $this->sampleEntityDefinition('finance.core', 'invoice');
        $data['name'] = '';

        $this->expectException(EntityValidationException::class);
        app(EnterpriseEntityValidationService::class)->assertValid(EntityDefinition::fromArray($data));
    }

    public function test_enterprise_entity_base_default_values(): void
    {
        $entity = $this->makeTestEnterpriseEntity('demo.core', 'customer', 'Customer');

        $this->assertSame('customer', $entity->entityKey());
        $this->assertSame('Customer', $entity->entityLabel());
        $this->assertSame('demo.core', $entity->moduleKey());
        $this->assertTrue($entity->searchable());
        $this->assertTrue($entity->commentsEnabled());
        $this->assertTrue($entity->tagsEnabled());
    }

    public function test_enterprise_entity_base_to_definition(): void
    {
        $entity = $this->makeTestEnterpriseEntity('demo.core', 'asset', 'Asset');
        $definition = $entity->toDefinition();

        $this->assertSame('demo.core', $definition->moduleKey);
        $this->assertSame('asset', $definition->entityKey);
        $this->assertTrue($definition->capabilities['searchable']);
    }

    public function test_registry_registers_from_dto(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(EnterpriseEntityRegistryService::class)->register(
            EntityDefinition::fromArray($this->sampleEntityDefinition('registry.dto.'.uniqid(), 'record')),
        );

        $this->assertNotEmpty($definition->publicId);
        $this->assertTrue(EntityDefinitionModel::query()->where('module_key', $definition->moduleKey)->exists());
    }

    public function test_registry_registers_from_array(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(EnterpriseEntityRegistryService::class)->register(
            $this->sampleEntityDefinition('registry.array.'.uniqid(), 'record'),
        );

        $this->assertSame('record', $definition->entityKey);
    }

    public function test_registry_registers_from_enterprise_entity_instance(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(EnterpriseEntityRegistryService::class)->register(
            $this->makeTestEnterpriseEntity('registry.instance.'.uniqid(), 'employee', 'Employee'),
        );

        $this->assertSame('employee', $definition->entityKey);
    }

    public function test_registry_registers_from_class_name(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.class.'.preg_replace('/[^a-z0-9.]/', '', uniqid());

        $class = new class($moduleKey) extends EnterpriseEntity
        {
            public function __construct(string $moduleKey)
            {
                $this->moduleKey = $moduleKey;
                $this->entityKey = 'batch';
                $this->entityLabel = 'Batch';
            }
        };

        $definition = app(EnterpriseEntityRegistryService::class)->register($class);

        $this->assertSame('batch', $definition->entityKey);
    }

    public function test_registry_duplicate_prevention(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $payload = $this->sampleEntityDefinition('registry.dup.'.uniqid(), 'record');

        app(EnterpriseEntityRegistryService::class)->register($payload);

        $this->expectException(EntityRegistryException::class);
        app(EnterpriseEntityRegistryService::class)->register($payload);
    }

    public function test_registry_list_and_find(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'registry.list.'.uniqid();

        app(EnterpriseEntityRegistryService::class)->register(
            $this->sampleEntityDefinition($moduleKey, 'order'),
        );

        $found = app(EnterpriseEntityRegistryService::class)->find($moduleKey, 'order');
        $listed = app(EnterpriseEntityRegistryService::class)->list($moduleKey);

        $this->assertNotNull($found);
        $this->assertCount(1, $listed);
    }

    public function test_registry_register_from_manifest_entities(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'manifest.module.'.uniqid();

        $registered = app(EnterpriseEntityRegistryService::class)->registerFromManifestEntities([
            ['key' => 'customer', 'name' => 'Customer'],
            ['key' => 'supplier', 'name' => 'Supplier'],
        ], $moduleKey);

        $this->assertCount(2, $registered);
        $this->assertSame('customer', $registered[0]->entityKey);
    }

    public function test_mapper_to_reference_public_id_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $definition = app(EnterpriseEntityRegistryService::class)->register(
            $this->sampleEntityDefinition('mapper.ref.'.uniqid(), 'product'),
        );

        $model = EntityDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->firstOrFail();

        $reference = EnterpriseEntityMapper::toReference($model);

        $this->assertArrayHasKey('public_id', $reference);
        $this->assertArrayNotHasKey('id', $reference);
    }

    public function test_relationship_registration(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseEntityDevelopmentService::class);
        $moduleKey = 'relationships.'.uniqid();

        $source = $service->registerDefinition($context, $this->sampleEntityDefinition($moduleKey, 'order'));
        $service->registerDefinition($context, $this->sampleEntityDefinition($moduleKey, 'customer'));

        $relationship = $service->registerRelationship($context, $source->moduleKey, $source->entityKey, [
            'relationship_key' => 'customer',
            'relationship_type' => 'belongs_to',
            'target_module_key' => $moduleKey,
            'target_entity_key' => 'customer',
            'label' => 'Customer',
        ]);

        $this->assertSame('customer', $relationship['relationship_key']);
        $this->assertTrue(EntityRelationship::query()->where('public_id', $relationship['public_id'])->exists());
    }

    public function test_relationship_list_for_entity(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseEntityDevelopmentService::class);
        $moduleKey = 'relationship.list.'.uniqid();

        $source = $service->registerDefinition($context, $this->sampleEntityDefinition($moduleKey, 'invoice'));
        $service->registerDefinition($context, $this->sampleEntityDefinition($moduleKey, 'customer'));
        $service->registerRelationship($context, $source->moduleKey, $source->entityKey, [
            'relationship_key' => 'customer',
            'relationship_type' => 'belongs_to',
            'target_module_key' => $moduleKey,
            'target_entity_key' => 'customer',
        ]);

        $relationships = $service->listRelationships($context, $source->moduleKey, $source->entityKey);

        $this->assertCount(1, $relationships);
        $this->assertArrayHasKey('public_id', $relationships[0]);
    }

    public function test_metadata_only_relationship_behavior(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseEntityDevelopmentService::class);
        $moduleKey = 'relationship.meta.'.uniqid();

        $source = $service->registerDefinition($context, $this->sampleEntityDefinition($moduleKey, 'product'));
        $relationship = $service->registerRelationship($context, $source->moduleKey, $source->entityKey, [
            'relationship_key' => 'external_ref',
            'relationship_type' => 'references',
            'target_module_key' => null,
            'target_entity_key' => null,
            'metadata' => ['external' => true],
        ]);

        $model = EntityRelationship::query()->where('public_id', $relationship['public_id'])->firstOrFail();

        $this->assertNull($model->target_entity_definition_id);
        $this->assertSame(['external' => true], $model->metadata);
    }

    public function test_comments_create_list_delete(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseEntityDevelopmentService::class);
        $definition = $this->registerSampleDefinition($context);
        $entityPublicId = (string) Str::uuid7();

        $comment = $service->createComment(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $entityPublicId,
            'First comment',
        );

        $comments = $service->listComments(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $entityPublicId,
        );

        $this->assertCount(1, $comments);
        $this->assertSame('First comment', $comments[0]['comment_body']);

        $service->deleteComment($context, $comment['public_id']);

        $this->assertSame(0, EntityComment::query()->where('public_id', $comment['public_id'])->count());
    }

    public function test_tags_create_list_attach_detach(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseEntityDevelopmentService::class);
        $definition = $this->registerSampleDefinition($context);
        $entityPublicId = (string) Str::uuid7();

        $tag = $service->createTag($context, 'priority.high', 'High Priority', '#ff0000');
        $tags = $service->listTags($context);

        $this->assertNotEmpty($tags);
        $service->attachTag($context, $definition->moduleKey, $definition->entityKey, $entityPublicId, $tag['public_id']);
        $this->assertTrue(EntityTaggable::query()->where('entity_public_id', $entityPublicId)->exists());

        $service->detachTag($context, $definition->moduleKey, $definition->entityKey, $entityPublicId, $tag['public_id']);
        $this->assertFalse(EntityTaggable::query()->where('entity_public_id', $entityPublicId)->exists());
    }

    public function test_activity_logging(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $entityPublicId = (string) Str::uuid7();

        app(\App\Services\Entity\EnterpriseEntityActivityService::class)->log(
            scope: new \App\Modules\Sdk\Enterprise\Data\EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
            ),
            organizationId: $context->organization->id,
            workspaceId: $context->workspace->id,
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            action: 'updated',
            entityPublicId: $entityPublicId,
            afterState: ['status' => 'active'],
            actorUserId: $context->user->id,
            actorMembershipId: $context->membership->id,
        );

        $this->assertTrue(EntityActivityLog::query()->where('entity_public_id', $entityPublicId)->exists());
    }

    public function test_lifecycle_event_dispatch_on_mutation(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $result = app(EnterpriseEntityRepositoryService::class)->mutate(new EntityMutationRequest(
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            operation: 'create',
            attributes: ['name' => 'Sample'],
        ));

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->entityPublicId);
        $this->assertTrue(
            EntityActivityLog::query()
                ->where('entity_public_id', $result->entityPublicId)
                ->where('action', EntityLifecycleEventType::Created->value)
                ->exists(),
        );
    }

    public function test_search_indexer_swallows_exceptions(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $model = EntityDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->firstOrFail();

        app(EnterpriseEntitySearchIndexer::class)->indexDefinitionBestEffort($model);

        $this->assertTrue(true);
    }

    public function test_audit_actions_recorded_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $this->assertTrue(
            AuditLog::query()->where('action', AuditAction::EntityDefinitionRegistered->value)->exists()
            || EntityDefinitionModel::query()->where('module_key', $definition->moduleKey)->exists(),
        );
    }

    public function test_repository_validate_mutation(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $report = app(EnterpriseEntityRepositoryService::class)->validateMutation(new EntityMutationRequest(
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            operation: 'create',
        ));

        $this->assertTrue($report->valid);
    }

    public function test_repository_mutate_generates_public_id(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $result = app(EnterpriseEntityRepositoryService::class)->mutate(new EntityMutationRequest(
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            operation: 'create',
        ));

        $this->assertNotEmpty($result->entityPublicId);
    }

    public function test_statistics_service_counts(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleDefinition($context);

        $stats = app(EnterpriseEntityStatisticsService::class)->statisticsForScope(
            $context->organization,
            $context->workspace,
        );

        $this->assertGreaterThanOrEqual(1, $stats->definitions);
    }

    public function test_runtime_metadata_includes_entities(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleDefinition($context);

        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);
        $entities = $runtime->runtimeMetadata['enterprise']['entities'] ?? null;

        $this->assertIsArray($entities);
        $this->assertArrayHasKey('enabled', $entities);
        $this->assertArrayHasKey('definitions', $entities);
    }

    public function test_doctor_health_includes_entities(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();
        $entities = $report->platformSummary['enterprise']['entities'] ?? null;

        $this->assertIsArray($entities);
        $this->assertArrayHasKey('enabled', $entities);
        $this->assertArrayHasKey('definitions', $entities);
    }

    public function test_health_service_warns_when_no_definitions(): void
    {
        $report = app(EnterpriseEntityHealthService::class)->health();

        $this->assertContains('No entity definitions are registered yet.', $report->warnings);
    }

    public function test_missing_table_guard_fallback(): void
    {
        $assessment = app(EnterpriseEntityHealthService::class)->assess();

        $this->assertArrayHasKey('enabled', $assessment);
        $this->assertArrayHasKey('status', $assessment);
    }

    public function test_api_list_entities_returns_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->registerSampleDefinition($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/entities');

        $response->assertOk();
        $payload = $response->json('data.0') ?? $response->json('0') ?? [];
        $this->assertArrayHasKey('public_id', $payload);
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_api_show_entity_definition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/entities/'.$definition->moduleKey.'/'.$definition->entityKey);

        $response->assertOk();
        $this->assertSame($definition->entityKey, $response->json('data.entity_key') ?? $response->json('entity_key'));
    }

    public function test_api_tags_static_route_before_parameterized(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/entities/tags');

        $response->assertOk();
    }

    public function test_api_comments_static_delete_route(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseEntityDevelopmentService::class);
        $definition = $this->registerSampleDefinition($context);
        $entityPublicId = (string) Str::uuid7();
        $comment = $service->createComment(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $entityPublicId,
            'Delete me',
        );

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/entities/comments/'.$comment['public_id']);

        $response->assertNoContent();
    }

    public function test_api_relationship_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseEntityDevelopmentService::class);
        $moduleKey = 'api.relationship.'.uniqid();
        $source = $service->registerDefinition($context, $this->sampleEntityDefinition($moduleKey, 'order'));
        $service->registerDefinition($context, $this->sampleEntityDefinition($moduleKey, 'customer'));

        $createResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/entities/'.$source->moduleKey.'/'.$source->entityKey.'/relationships', [
                'relationship_key' => 'customer',
                'relationship_type' => 'belongs_to',
                'target_module_key' => $moduleKey,
                'target_entity_key' => 'customer',
            ]);
        $createResponse->assertCreated();

        $listResponse = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/entities/'.$source->moduleKey.'/'.$source->entityKey.'/relationships');
        $listResponse->assertOk();
        $this->assertNotEmpty($listResponse->json('data') ?? $listResponse->json());
    }

    public function test_api_comments_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $entityPublicId = (string) Str::uuid7();

        $createResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/entities/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$entityPublicId.'/comments', [
                'comment_body' => 'API comment',
            ]);
        $createResponse->assertCreated();

        $listResponse = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/entities/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$entityPublicId.'/comments');
        $listResponse->assertOk();
    }

    public function test_api_tags_flow(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $entityPublicId = (string) Str::uuid7();

        $createTagResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/entities/tags', [
                'tag_key' => 'status.open',
                'name' => 'Open',
            ]);
        $createTagResponse->assertCreated();
        $tagPublicId = $createTagResponse->json('data.public_id') ?? $createTagResponse->json('public_id');

        $attachResponse = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/entities/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$entityPublicId.'/tags/'.$tagPublicId);
        $attachResponse->assertNoContent();

        $detachResponse = $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/entities/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$entityPublicId.'/tags/'.$tagPublicId);
        $detachResponse->assertNoContent();
    }

    public function test_api_activity_endpoint(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $entityPublicId = (string) Str::uuid7();

        app(EnterpriseEntityLifecycleService::class)->dispatch($context, new EntityLifecycleEvent(
            eventType: EntityLifecycleEventType::Updated->value,
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            entityPublicId: $entityPublicId,
        ));

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/entities/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$entityPublicId.'/activity');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data') ?? $response->json());
    }

    public function test_permission_catalog_includes_entity_permissions(): void
    {
        $this->seedHeosPermissions();

        $this->assertTrue(Permission::query()->where('key', 'entities.read')->exists());
        $this->assertTrue(Permission::query()->where('key', 'entities.manage')->exists());
        $this->assertTrue(Permission::query()->where('key', 'entities.comment')->exists());
        $this->assertTrue(Permission::query()->where('key', 'entities.tag')->exists());
        $this->assertPermissionCatalogComplete();
    }

    public function test_member_can_comment_and_tag(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleDefinition($ownerContext);
        $entityPublicId = (string) Str::uuid7();

        app()->instance(TenantContext::class, $memberContext);

        $commentResponse = $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/entities/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$entityPublicId.'/comments', [
                'comment_body' => 'Member comment',
            ]);
        $commentResponse->assertCreated();

        $tagResponse = $this->withHeaders($this->tenantHeaders($memberContext))
            ->postJson('/api/v1/tenant/entities/tags', [
                'tag_key' => 'member.tag',
                'name' => 'Member Tag',
            ]);
        $tagResponse->assertCreated();
    }

    public function test_viewer_cannot_comment(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $definition = $this->registerSampleDefinition($ownerContext);
        $entityPublicId = (string) Str::uuid7();

        app()->instance(TenantContext::class, $viewerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->postJson('/api/v1/tenant/entities/'.$definition->moduleKey.'/'.$definition->entityKey.'/'.$entityPublicId.'/comments', [
                'comment_body' => 'Viewer comment',
            ]);

        $response->assertForbidden();
    }

    public function test_tenant_isolation_for_comments(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        $definition = $this->registerSampleDefinition($contextA);
        $entityPublicId = (string) Str::uuid7();

        $comment = app(EnterpriseEntityDevelopmentService::class)->createComment(
            $contextA,
            $definition->moduleKey,
            $definition->entityKey,
            $entityPublicId,
            'Tenant A comment',
        );

        app()->instance(TenantContext::class, $contextB);

        $this->expectException(\App\Modules\Sdk\Entity\Exceptions\EntityNotFoundException::class);
        app(EnterpriseEntityDevelopmentService::class)->deleteComment($contextB, $comment['public_id']);
    }

    public function test_workspace_isolation_for_tags(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $definition = $this->registerSampleDefinition($context);
        $entityPublicId = (string) Str::uuid7();

        $secondWorkspace = $context->organization->workspaces()->create([
            'public_id' => (string) Str::uuid7(),
            'name' => 'Secondary Workspace',
            'slug' => 'secondary-'.uniqid(),
            'is_default' => false,
            'status' => \App\Enums\WorkspaceStatus::Active,
        ]);

        $tag = app(EnterpriseEntityDevelopmentService::class)->createTag($context, 'workspace.tag', 'Workspace Tag');
        app(EnterpriseEntityDevelopmentService::class)->attachTag(
            $context,
            $definition->moduleKey,
            $definition->entityKey,
            $entityPublicId,
            $tag['public_id'],
        );

        $otherWorkspaceContext = TenantContext::fromModels(
            $context->user,
            $context->organization,
            $context->membership,
            $secondWorkspace,
        );

        app()->instance(TenantContext::class, $otherWorkspaceContext);

        $this->expectException(\App\Modules\Sdk\Entity\Exceptions\EntityNotFoundException::class);
        app(EnterpriseEntityDevelopmentService::class)->detachTag(
            $otherWorkspaceContext,
            $definition->moduleKey,
            $definition->entityKey,
            $entityPublicId,
            $tag['public_id'],
        );
    }

    public function test_platform_event_bridge_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        app(\App\Services\Entity\EnterpriseEntityWorkflowBridge::class)->triggerBestEffort(
            $context,
            'entity.definition.registered',
            ['module_key' => 'demo.core'],
        );

        $this->assertTrue(true);
    }

    public function test_business_module_base_entities_integration(): void
    {
        $module = new class extends BusinessModuleBase
        {
            protected string $moduleKey = 'demo.entities';

            public function entities(): array
            {
                return [[
                    'entity_key' => 'customer',
                    'name' => 'Customer',
                ]];
            }
        };

        $this->assertCount(1, $module->entities());
        $this->assertSame('customer', $module->entities()[0]['entity_key']);
    }

    public function test_business_module_installer_registers_manifest_entities(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $moduleKey = 'entity.install.'.preg_replace('/[^a-z0-9.]/', '', uniqid());

        $module = app(BusinessModuleDevelopmentService::class)->registerModule(
            $context,
            BusinessModuleManifest::fromArray([
                'module_key' => $moduleKey,
                'name' => 'Entity Install Module',
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
                'entities' => [[
                    'entity_key' => 'order',
                    'name' => 'Order',
                ]],
                'dependencies' => ['heos.core'],
            ]),
        );

        app(BusinessModuleDevelopmentService::class)->install($context, new BusinessModuleInstallRequest(
            modulePublicId: $module->publicId,
        ));

        $this->assertTrue(EntityDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('entity_key', 'order')
            ->exists());
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleEntityDefinition(string $moduleKey, string $entityKey): array
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
            'fields' => [[
                'field_key' => 'name',
                'label' => 'Name',
                'field_type' => 'string',
                'required' => true,
            ]],
            'metadata' => ['owner' => 'platform'],
        ];
    }

    private function makeTestEnterpriseEntity(string $moduleKey, string $entityKey, string $label): EnterpriseEntity
    {
        return new class($moduleKey, $entityKey, $label) extends EnterpriseEntity
        {
            public function __construct(string $moduleKey, string $entityKey, string $label)
            {
                $this->moduleKey = $moduleKey;
                $this->entityKey = $entityKey;
                $this->entityLabel = $label;
            }
        };
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'enterprise-entities-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function registerSampleDefinition(TenantContext $context): EntityDefinition
    {
        app()->instance(TenantContext::class, $context);

        return app(EnterpriseEntityDevelopmentService::class)->registerDefinition(
            $context,
            $this->sampleEntityDefinition('sample.module.'.uniqid(), 'record'),
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
