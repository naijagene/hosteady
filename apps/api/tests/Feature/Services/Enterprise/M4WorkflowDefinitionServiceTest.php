<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PlatformSearchIndex;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference;
use App\Modules\Sdk\Workflow\Data\WorkflowNodeData;
use App\Modules\Sdk\Workflow\Data\WorkflowTransitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowTriggerData;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;
use App\Modules\Sdk\Workflow\Data\WorkflowVariableData;
use App\Modules\Sdk\Workflow\Enums\WorkflowStatus;
use App\Modules\Sdk\Workflow\Enums\WorkflowVersionStatus;
use App\Modules\Sdk\Workflow\Exceptions\DuplicateWorkflowKeyException;
use App\Services\Enterprise\Workflow\WorkflowCategoryService;
use App\Services\Enterprise\Workflow\WorkflowDefinitionService;
use App\Services\Enterprise\Workflow\WorkflowHealthService;
use App\Services\Enterprise\Workflow\WorkflowValidationService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4WorkflowDefinitionServiceTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_workflow_definition_reference_serializes_to_array(): void
    {
        $reference = new WorkflowDefinitionReference(
            publicId: '01900000-0000-7000-8000-000000000200',
            workflowKey: 'demo.approval',
            name: 'Demo Approval',
            status: 'draft',
        );

        $this->assertSame('demo.approval', $reference->toArray()['workflow_key']);
    }

    public function test_validation_report_serializes_to_array(): void
    {
        $report = new WorkflowValidationReport(valid: true, issues: []);

        $this->assertTrue($report->toArray()['valid']);
    }

    public function test_workflow_creation_creates_draft_definition_and_version(): void
    {
        $context = $this->tenantContext();

        $reference = app(WorkflowDefinitionService::class)->create($context, $this->validDefinition());

        $this->assertSame('draft', $reference->status);
        $this->assertTrue(WorkflowDefinition::query()->where('public_id', $reference->publicId)->exists());
        $this->assertTrue(WorkflowVersion::query()->where('workflow_definition_id', WorkflowDefinition::query()->where('public_id', $reference->publicId)->value('id'))->exists());
    }

    public function test_workflow_creation_records_audit_event(): void
    {
        $context = $this->tenantContext();

        app(WorkflowDefinitionService::class)->create($context, $this->validDefinition(['workflow_key' => 'audit.create']));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowCreated->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowValidated->value)->exists());
    }

    public function test_workflow_update_updates_definition(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowDefinitionService::class);
        $created = $service->create($context, $this->validDefinition(['workflow_key' => 'update.test']));

        $updated = $service->update($context, $created->publicId, $this->validDefinition([
            'workflow_key' => 'update.test',
            'name' => 'Updated Workflow',
        ]));

        $this->assertSame('Updated Workflow', $updated->name);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowUpdated->value)->exists());
    }

    public function test_validation_passes_for_valid_definition(): void
    {
        $report = app(WorkflowValidationService::class)->validate($this->validDefinition());

        $this->assertTrue($report->valid);
    }

    public function test_validation_fails_when_start_node_missing(): void
    {
        $report = app(WorkflowValidationService::class)->validate(new WorkflowDefinitionData(
            workflowKey: 'invalid.start',
            name: 'Invalid',
            nodes: [new WorkflowNodeData('end', 'end')],
            transitions: [],
            triggers: [new WorkflowTriggerData('manual')],
        ));

        $this->assertFalse($report->valid);
        $this->assertNotEmpty($report->issues);
    }

    public function test_publish_draft_marks_definition_published(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowDefinitionService::class);
        $created = $service->create($context, $this->validDefinition(['workflow_key' => 'publish.test']));

        $result = $service->publish($context, $created->publicId);

        $this->assertSame('published', $result->definition->status);
        $this->assertTrue($result->validationReport->valid);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowPublished->value)->exists());
    }

    public function test_publish_archives_previous_published_version(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowDefinitionService::class);
        $created = $service->create($context, $this->validDefinition(['workflow_key' => 'republish.test']));

        $first = $service->publish($context, $created->publicId);
        $service->update($context, $created->publicId, $this->validDefinition([
            'workflow_key' => 'republish.test',
            'name' => 'Version Two',
        ]));
        $second = $service->publish($context, $created->publicId);

        $this->assertSame(2, $second->publishedVersion->versionNumber);
        $firstVersion = WorkflowVersion::query()->where('public_id', $first->publishedVersion->publicId)->firstOrFail();
        $this->assertSame(WorkflowVersionStatus::Archived, $firstVersion->status);
    }

    public function test_only_one_published_version_exists_per_definition(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowDefinitionService::class);
        $created = $service->create($context, $this->validDefinition(['workflow_key' => 'single.published']));
        $service->publish($context, $created->publicId);
        $service->update($context, $created->publicId, $this->validDefinition(['workflow_key' => 'single.published', 'name' => 'V2']));
        $service->publish($context, $created->publicId);

        $definitionId = WorkflowDefinition::query()->where('public_id', $created->publicId)->value('id');

        $this->assertSame(
            1,
            WorkflowVersion::query()
                ->where('workflow_definition_id', $definitionId)
                ->where('status', WorkflowVersionStatus::Published)
                ->count(),
        );
    }

    public function test_archive_marks_definition_archived(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowDefinitionService::class);
        $created = $service->create($context, $this->validDefinition(['workflow_key' => 'archive.test']));
        $service->publish($context, $created->publicId);

        $archived = $service->archive($context, $created->publicId);

        $this->assertSame('archived', $archived->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowArchived->value)->exists());
    }

    public function test_category_creation_and_list(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowCategoryService::class);

        $service->create($context, 'approvals', 'Approvals', 'Approval workflows');
        $categories = $service->list($context);

        $this->assertCount(1, $categories);
        $this->assertSame('approvals', $categories[0]->categoryKey);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowCategoryCreated->value)->exists());
    }

    public function test_statistics_report_counts(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowDefinitionService::class);
        $service->create($context, $this->validDefinition(['workflow_key' => 'stats.one']));
        $published = $service->create($context, $this->validDefinition(['workflow_key' => 'stats.two']));
        $service->publish($context, $published->publicId);

        $stats = $service->statistics($context);

        $this->assertSame(2, $stats->definitions);
        $this->assertSame(1, $stats->published);
        $this->assertSame(1, $stats->drafts);
    }

    public function test_health_service_reports_workflow_metrics(): void
    {
        $context = $this->tenantContext();
        app(WorkflowDefinitionService::class)->create($context, $this->validDefinition(['workflow_key' => 'health.test']));

        $health = app(WorkflowHealthService::class)->assess($context);

        $this->assertTrue($health['enabled']);
        $this->assertSame(1, $health['definitions']);
    }

    public function test_runtime_includes_workflow_capability(): void
    {
        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['workflow']);
        $this->assertTrue($runtime->capabilities['human_tasks']);
        $this->assertTrue($runtime->capabilities['approvals']);
        $this->assertTrue($runtime->capabilities['approval']);
        $this->assertArrayHasKey('workflow', $runtime->runtimeMetadata['enterprise']);
    }

    public function test_doctor_exposes_workflow_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('workflow', $report->platformSummary['enterprise']);
    }

    public function test_duplicate_workflow_key_is_rejected(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowDefinitionService::class);
        $service->create($context, $this->validDefinition(['workflow_key' => 'duplicate.key']));

        $this->expectException(DuplicateWorkflowKeyException::class);
        $service->create($context, $this->validDefinition(['workflow_key' => 'duplicate.key', 'name' => 'Another']));
    }

    public function test_search_indexing_is_best_effort_on_create(): void
    {
        $context = $this->tenantContext();
        $reference = app(WorkflowDefinitionService::class)->create($context, $this->validDefinition(['workflow_key' => 'search.index']));

        $this->assertTrue(PlatformSearchIndex::query()->where('entity_public_id', $reference->publicId)->exists());
    }

    public function test_search_indexing_does_not_fail_when_indexing_disabled(): void
    {
        config(['heos.enterprise.search.enabled' => false]);
        $context = $this->tenantContext();

        $reference = app(WorkflowDefinitionService::class)->create($context, $this->validDefinition(['workflow_key' => 'search.off']));

        $this->assertSame('draft', $reference->status);
    }

    public function test_tenant_isolation_prevents_cross_organization_access(): void
    {
        $ownerA = $this->tenantContext();
        $created = app(WorkflowDefinitionService::class)->create($ownerA, $this->validDefinition(['workflow_key' => 'tenant.a']));

        $ownerB = $this->tenantContext();

        $this->expectException(\App\Modules\Sdk\Workflow\Exceptions\WorkflowNotFoundException::class);
        app(WorkflowDefinitionService::class)->show($ownerB, $created->publicId);
    }

    public function test_workflow_api_create_list_show_publish_archive(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);
        $headers = [
            'Authorization' => 'Bearer '.$token,
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];

        $create = $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/workflows/definitions', [
                'workflow_key' => 'api.workflow',
                'name' => 'API Workflow',
                'nodes' => [
                    ['id' => 'start', 'type' => 'start'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'transitions' => [
                    ['id' => 't1', 'from' => 'start', 'to' => 'end'],
                ],
                'triggers' => [
                    ['type' => 'manual'],
                ],
            ])
            ->assertCreated();

        $publicId = $create->json('data.public_id');

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/workflows/definitions')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/workflows/definitions/'.$publicId)
            ->assertOk()
            ->assertJsonPath('data.public_id', $publicId);

        $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/workflows/definitions/'.$publicId.'/publish')
            ->assertOk()
            ->assertJsonPath('data.definition.status', 'published');

        $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/workflows/definitions/'.$publicId.'/archive')
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');
    }

    public function test_member_cannot_publish_without_permission(): void
    {
        $context = $this->tenantContext();
        $created = app(WorkflowDefinitionService::class)->create($context, $this->validDefinition(['workflow_key' => 'member.publish']));
        $member = $this->memberContext($context);
        $token = $this->issueToken($member->user);

        $this->withBearerToken($token)
            ->withTenantHeaders($member->organizationPublicId, $member->workspacePublicId)
            ->postJson('/api/v1/tenant/workflows/definitions/'.$created->publicId.'/publish')
            ->assertForbidden();
    }

    public function test_list_versions_returns_history(): void
    {
        $context = $this->tenantContext();
        $service = app(WorkflowDefinitionService::class);
        $created = $service->create($context, $this->validDefinition(['workflow_key' => 'versions.test']));
        $service->publish($context, $created->publicId);

        $versions = $service->listVersions($context, $created->publicId);

        $this->assertCount(1, $versions);
        $this->assertSame('published', $versions[0]->status);
    }

    public function test_workflow_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.workflow.enabled' => false]);
        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);

        app(WorkflowDefinitionService::class)->list($context);
    }

    public function test_invalid_node_type_fails_validation(): void
    {
        $report = app(WorkflowValidationService::class)->validate(new WorkflowDefinitionData(
            workflowKey: 'invalid.node',
            name: 'Invalid Node',
            nodes: [
                new WorkflowNodeData('start', 'start'),
                new WorkflowNodeData('bad', 'not_a_real_type'),
                new WorkflowNodeData('end', 'end'),
            ],
            transitions: [
                new WorkflowTransitionData('t1', 'start', 'bad'),
                new WorkflowTransitionData('t2', 'bad', 'end'),
            ],
            triggers: [new WorkflowTriggerData('manual')],
        ));

        $this->assertFalse($report->valid);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'workflow-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        $member = $this->createActiveUser();
        $memberRole = \App\Models\Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', 'member')
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $member->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $member,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function validDefinition(array $overrides = []): WorkflowDefinitionData
    {
        return new WorkflowDefinitionData(
            workflowKey: $overrides['workflow_key'] ?? 'demo.approval',
            name: $overrides['name'] ?? 'Demo Approval Workflow',
            description: $overrides['description'] ?? null,
            moduleKey: $overrides['module_key'] ?? 'demo',
            nodes: [
                new WorkflowNodeData('start', 'start', 'Start'),
                new WorkflowNodeData('review', 'approval', 'Review'),
                new WorkflowNodeData('end', 'end', 'End'),
            ],
            transitions: [
                new WorkflowTransitionData('to_review', 'start', 'review'),
                new WorkflowTransitionData('to_end', 'review', 'end'),
            ],
            triggers: [
                new WorkflowTriggerData('manual', 'manual_trigger', 'Manual Start'),
            ],
            variables: [
                new WorkflowVariableData('comment', 'Comment', 'string'),
            ],
        );
    }
}
