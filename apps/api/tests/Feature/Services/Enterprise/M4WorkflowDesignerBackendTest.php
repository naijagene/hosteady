<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Enums\WorkspaceStatus;
use App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException;
use App\Models\AuditLog;
use App\Models\Workspace;
use App\Models\WorkflowCanvasSnapshot;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowNodeTemplate;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowNodeData;
use App\Modules\Sdk\Workflow\Data\WorkflowTransitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowTriggerData;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvas;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasEdge;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasNode;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowCanvasViewport;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerMetadata;
use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerSnapshot;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowExportFormat;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowImportFormat;
use App\Modules\Sdk\Workflow\Exceptions\WorkflowNotFoundException;
use App\Services\Enterprise\Search\SearchIndexService;
use App\Services\Enterprise\Workflow\Designer\WorkflowCanvasDiffService;
use App\Services\Enterprise\Workflow\Designer\WorkflowCanvasNormalizationService;
use App\Services\Enterprise\Workflow\Designer\WorkflowDesignerHealthService;
use App\Services\Enterprise\Workflow\Designer\WorkflowDesignerService;
use App\Services\Enterprise\Workflow\Designer\WorkflowNodeTemplateService;
use App\Services\Enterprise\Workflow\WorkflowDefinitionService;
use App\Services\Enterprise\Workflow\WorkflowHealthService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4WorkflowDesignerBackendTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_canvas_node_serializes_to_array(): void
    {
        $node = new WorkflowCanvasNode('n1', 'task', 'Do Work', 100, 200, 140, 60);

        $this->assertSame('task', $node->toArray()['type']);
        $this->assertSame(100.0, $node->toArray()['x']);
    }

    public function test_canvas_edge_serializes_to_array(): void
    {
        $edge = new WorkflowCanvasEdge('e1', 'start', 'end', 'Go', 'true');

        $this->assertSame('start', $edge->toArray()['source']);
        $this->assertSame('true', $edge->toArray()['condition']);
    }

    public function test_canvas_from_array_roundtrip(): void
    {
        $canvas = WorkflowCanvas::fromArray([
            'nodes' => [['id' => 'start', 'type' => 'start', 'x' => 0, 'y' => 0]],
            'edges' => [['id' => 'e1', 'source' => 'start', 'target' => 'end']],
            'viewport' => ['x' => 10, 'y' => 20, 'zoom' => 1.5],
            'metadata' => ['designer_version' => '1.0'],
        ]);

        $this->assertCount(1, $canvas->nodes);
        $this->assertSame(1.5, $canvas->viewport?->zoom);
    }

    public function test_snapshot_dto_serializes(): void
    {
        $snapshot = new WorkflowDesignerSnapshot(
            publicId: '01900000-0000-7000-8000-000000000600',
            workflowDefinitionPublicId: '01900000-0000-7000-8000-000000000601',
            status: 'saved',
            canvas: new WorkflowCanvas(),
        );

        $this->assertSame('saved', $snapshot->toArray()['status']);
    }

    public function test_normalizer_removes_orphan_edges(): void
    {
        $normalizer = app(WorkflowCanvasNormalizationService::class);
        $canvas = new WorkflowCanvas(
            nodes: [new WorkflowCanvasNode('start', 'start')],
            edges: [new WorkflowCanvasEdge('e1', 'start', 'missing')],
        );

        $result = $normalizer->normalize($canvas);

        $this->assertCount(0, $result['canvas']->edges);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_normalizer_ensures_unique_node_ids(): void
    {
        $normalizer = app(WorkflowCanvasNormalizationService::class);
        $canvas = new WorkflowCanvas(
            nodes: [
                new WorkflowCanvasNode('dup', 'start'),
                new WorkflowCanvasNode('dup', 'end'),
            ],
        );

        $result = $normalizer->normalize($canvas);
        $ids = array_map(fn ($n) => $n->id, $result['canvas']->nodes);

        $this->assertCount(2, array_unique($ids));
    }

    public function test_normalizer_syncs_with_definition_nodes(): void
    {
        $normalizer = app(WorkflowCanvasNormalizationService::class);
        $canvas = new WorkflowCanvas(nodes: [new WorkflowCanvasNode('start', 'start')]);

        $result = $normalizer->normalize($canvas, [
            ['id' => 'start', 'type' => 'start'],
            ['id' => 'end', 'type' => 'end'],
        ]);

        $this->assertCount(2, $result['canvas']->nodes);
    }

    public function test_diff_detects_added_and_removed_nodes(): void
    {
        $diffService = app(WorkflowCanvasDiffService::class);
        $from = new WorkflowCanvas(nodes: [new WorkflowCanvasNode('start', 'start', x: 0, y: 0)]);
        $to = new WorkflowCanvas(nodes: [
            new WorkflowCanvasNode('start', 'start', x: 0, y: 0),
            new WorkflowCanvasNode('end', 'end', x: 200, y: 0),
        ]);

        $diff = $diffService->diff('snap-a', 'snap-b', $from, $to);

        $this->assertCount(1, $diff->addedNodes);
        $this->assertSame('end', $diff->addedNodes[0]['id']);
    }

    public function test_diff_detects_moved_nodes(): void
    {
        $diffService = app(WorkflowCanvasDiffService::class);
        $from = new WorkflowCanvas(nodes: [new WorkflowCanvasNode('start', 'start', x: 0, y: 0)]);
        $to = new WorkflowCanvas(nodes: [new WorkflowCanvasNode('start', 'start', x: 300, y: 100)]);

        $diff = $diffService->diff('snap-a', 'snap-b', $from, $to);

        $this->assertCount(1, $diff->movedNodes);
    }

    public function test_diff_detects_metadata_changes(): void
    {
        $diffService = app(WorkflowCanvasDiffService::class);
        $from = new WorkflowCanvas(metadata: new WorkflowDesignerMetadata(designerVersion: '1.0'));
        $to = new WorkflowCanvas(metadata: new WorkflowDesignerMetadata(designerVersion: '2.0'));

        $diff = $diffService->diff('snap-a', 'snap-b', $from, $to);

        $this->assertArrayHasKey('designer_version', $diff->metadataChanges);
    }

    public function test_system_node_templates_are_seeded(): void
    {
        app(WorkflowNodeTemplateService::class)->ensureSystemTemplates();

        $this->assertSame(10, WorkflowNodeTemplate::query()->where('is_system', true)->count());
        $this->assertTrue(
            WorkflowNodeTemplate::query()->where('node_type', 'start')->where('is_system', true)->exists()
        );
    }

    public function test_list_node_templates_returns_all_types(): void
    {
        $context = $this->tenantContext();
        $templates = app(WorkflowDesignerService::class)->listNodeTemplates($context);

        $types = array_map(fn ($t) => $t->nodeType, $templates);
        $this->assertContains('approval', $types);
        $this->assertContains('condition', $types);
        $this->assertContains('parallel', $types);
    }

    public function test_get_canvas_returns_default_from_definition(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.canvas.get');

        $snapshot = app(WorkflowDesignerService::class)->getCanvas($context, $definition->publicId);

        $this->assertCount(2, $snapshot->canvas->nodes);
        $this->assertSame('start', $snapshot->canvas->nodes[0]->id);
    }

    public function test_save_canvas_creates_snapshot(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.canvas.save');
        $canvas = $this->sampleCanvas();

        $saved = app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $canvas);

        $this->assertNotEmpty($saved->publicId);
        $this->assertTrue(WorkflowCanvasSnapshot::query()->where('public_id', $saved->publicId)->exists());
    }

    public function test_save_canvas_records_audit(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.canvas.audit');
        app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowDesignerCanvasSaved->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowDesignerSnapshotCreated->value)->exists());
    }

    public function test_list_snapshots_returns_history(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.snapshots');
        $service = app(WorkflowDesignerService::class);
        $service->saveCanvas($context, $definition->publicId, $this->sampleCanvas());
        $service->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $snapshots = $service->listSnapshots($context, $definition->publicId);

        $this->assertCount(2, $snapshots);
    }

    public function test_get_snapshot_by_public_id(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.snapshot.show');
        $saved = app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $snapshot = app(WorkflowDesignerService::class)->getSnapshot($context, $saved->publicId);

        $this->assertSame($saved->publicId, $snapshot->publicId);
    }

    public function test_diff_snapshots_records_audit(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.diff');
        $service = app(WorkflowDesignerService::class);
        $first = $service->saveCanvas($context, $definition->publicId, $this->sampleCanvas());
        $second = $service->saveCanvas($context, $definition->publicId, new WorkflowCanvas(
            nodes: [
                new WorkflowCanvasNode('start', 'start', x: 0, y: 0),
                new WorkflowCanvasNode('end', 'end', x: 300, y: 0),
            ],
            edges: [new WorkflowCanvasEdge('e1', 'start', 'end')],
        ));

        $service->diffSnapshots($context, $first->publicId, $second->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowDesignerSnapshotDiffed->value)->exists());
    }

    public function test_preview_returns_definition_and_canvas(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.preview');
        app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $preview = app(WorkflowDesignerService::class)->preview($context, $definition->publicId);

        $this->assertSame($definition->publicId, $preview->workflowDefinitionPublicId);
        $this->assertNotEmpty($preview->definition);
        $this->assertNotEmpty($preview->canvas);
        $this->assertNotEmpty($preview->templates);
    }

    public function test_preview_records_audit(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.preview.audit');
        app(WorkflowDesignerService::class)->preview($context, $definition->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowDesignerPreviewGenerated->value)->exists());
    }

    public function test_clone_workflow_creates_draft_copy(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.clone.source');
        app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $result = app(WorkflowDesignerService::class)->cloneWorkflow($context, $definition->publicId);

        $this->assertNotSame($definition->publicId, $result->clonedDefinitionPublicId);
        $this->assertStringContainsString('copy', $result->workflowKey);
        $this->assertTrue(
            WorkflowDefinition::query()->where('public_id', $result->clonedDefinitionPublicId)->where('status', 'draft')->exists()
        );
    }

    public function test_clone_workflow_copies_canvas(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.clone.canvas');
        app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $result = app(WorkflowDesignerService::class)->cloneWorkflow($context, $definition->publicId);

        $this->assertNotNull($result->snapshotPublicId);
        $this->assertTrue(WorkflowCanvasSnapshot::query()->where('public_id', $result->snapshotPublicId)->exists());
    }

    public function test_clone_workflow_records_audit(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.clone.audit');
        app(WorkflowDesignerService::class)->cloneWorkflow($context, $definition->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowCloned->value)->exists());
    }

    public function test_export_workflow_heos_json(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.export');
        app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $export = app(WorkflowDesignerService::class)->exportWorkflow($context, $definition->publicId);

        $this->assertSame(WorkflowExportFormat::HeosJson->value, $export->format);
        $this->assertSame('designer.export', $export->payload['workflow']['workflow_key']);
        $this->assertNotNull($export->payload['canvas']);
    }

    public function test_export_records_audit(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.export.audit');
        app(WorkflowDesignerService::class)->exportWorkflow($context, $definition->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowExported->value)->exists());
    }

    public function test_import_workflow_creates_draft(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.import.source');
        $export = app(WorkflowDesignerService::class)->exportWorkflow($context, $definition->publicId);

        $imported = app(WorkflowDesignerService::class)->importWorkflow($context, $export->payload);

        $this->assertSame('draft', $imported->status);
        $this->assertSame(WorkflowImportFormat::HeosJson->value, $imported->format);
        $this->assertTrue(WorkflowDefinition::query()->where('public_id', $imported->workflowDefinitionPublicId)->exists());
    }

    public function test_import_records_audit(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.import.audit');
        $export = app(WorkflowDesignerService::class)->exportWorkflow($context, $definition->publicId);
        app(WorkflowDesignerService::class)->importWorkflow($context, $export->payload);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowImported->value)->exists());
    }

    public function test_designer_health_service_reports_status(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.health');
        app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $health = app(WorkflowDesignerHealthService::class)->assess($context);

        $this->assertTrue($health['enabled']);
        $this->assertGreaterThan(0, $health['canvases']);
        $this->assertGreaterThan(0, $health['templates']);
        $this->assertContains('heos_json', $health['supported_import_formats']);
    }

    public function test_workflow_health_includes_designer_key(): void
    {
        $context = $this->tenantContext();
        $health = app(WorkflowHealthService::class)->assess($context);

        $this->assertArrayHasKey('designer', $health);
        $this->assertTrue($health['designer']['enabled']);
    }

    public function test_runtime_manifest_includes_designer(): void
    {
        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['workflow_designer']);
        $this->assertArrayHasKey('designer', $runtime->runtimeMetadata['enterprise']['workflow']);
    }

    public function test_member_can_read_and_export(): void
    {
        $ownerContext = $this->tenantContext();
        $definition = $this->draftWorkflow($ownerContext, 'designer.member');
        app(WorkflowDesignerService::class)->saveCanvas($ownerContext, $definition->publicId, $this->sampleCanvas());
        $memberContext = $this->memberContext($ownerContext);
        $service = app(WorkflowDesignerService::class);

        $this->assertNotEmpty($service->getCanvas($memberContext, $definition->publicId)->canvas->nodes);
        $this->assertSame(WorkflowExportFormat::HeosJson->value, $service->exportWorkflow($memberContext, $definition->publicId)->format);
    }

    public function test_member_cannot_save_canvas(): void
    {
        $ownerContext = $this->tenantContext();
        $definition = $this->draftWorkflow($ownerContext, 'designer.member.denied');
        $memberContext = $this->memberContext($ownerContext);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(WorkflowDesignerService::class)->saveCanvas($memberContext, $definition->publicId, $this->sampleCanvas());
    }

    public function test_viewer_cannot_export(): void
    {
        $ownerContext = $this->tenantContext();
        $definition = $this->draftWorkflow($ownerContext, 'designer.viewer');
        $viewerContext = $this->viewerContext($ownerContext);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(WorkflowDesignerService::class)->exportWorkflow($viewerContext, $definition->publicId);
    }

    public function test_api_get_canvas_endpoint(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.api.canvas');
        app(WorkflowDesignerService::class)->saveCanvas($context, $definition->publicId, $this->sampleCanvas());

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson("/api/v1/tenant/workflows/designer/definitions/{$definition->publicId}/canvas");

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.canvas.nodes') ?? $response->json('canvas.nodes'));
    }

    public function test_api_post_canvas_endpoint(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.api.save');

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->postJson("/api/v1/tenant/workflows/designer/definitions/{$definition->publicId}/canvas", $this->sampleCanvas()->toArray());

        $response->assertCreated();
    }

    public function test_api_node_templates_endpoint(): void
    {
        $context = $this->tenantContext();

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/workflows/designer/node-templates');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(10, count($response->json('data') ?? $response->json()));
    }

    public function test_api_preview_endpoint(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.api.preview');

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson("/api/v1/tenant/workflows/designer/definitions/{$definition->publicId}/preview");

        $response->assertOk();
    }

    public function test_api_clone_endpoint(): void
    {
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.api.clone');

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->postJson("/api/v1/tenant/workflows/designer/definitions/{$definition->publicId}/clone");

        $response->assertCreated();
    }

    public function test_permission_catalog_has_fifty_permissions(): void
    {
        $this->seedHeosPermissions();
        $this->assertPermissionCatalogComplete();
    }

    public function test_tenant_isolation_prevents_cross_organization_canvas_access(): void
    {
        $ownerA = $this->tenantContext();
        $definition = $this->draftWorkflow($ownerA, 'designer.tenant.a');
        app(WorkflowDesignerService::class)->saveCanvas($ownerA, $definition->publicId, $this->sampleCanvas());

        $ownerB = $this->tenantContext();

        $this->expectException(WorkflowNotFoundException::class);
        app(WorkflowDesignerService::class)->getCanvas($ownerB, $definition->publicId);
    }

    public function test_workspace_isolation_prevents_cross_workspace_canvas_access(): void
    {
        $contextA = $this->tenantContext();
        $definition = $this->draftWorkflow($contextA, 'designer.workspace.a');
        app(WorkflowDesignerService::class)->saveCanvas($contextA, $definition->publicId, $this->sampleCanvas());

        $secondaryWorkspace = Workspace::query()->create([
            'organization_id' => $contextA->organization->id,
            'name' => 'Secondary',
            'slug' => 'secondary-'.uniqid(),
            'is_default' => false,
            'status' => WorkspaceStatus::Active,
        ]);

        $contextB = TenantContext::fromModels(
            $contextA->user,
            $contextA->organization,
            $contextA->membership,
            $secondaryWorkspace,
        );

        $this->expectException(WorkflowNotFoundException::class);
        app(WorkflowDesignerService::class)->getCanvas($contextB, $definition->publicId);
    }

    public function test_designer_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.workflow_designer.enabled' => false]);
        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.capability.disabled');

        $this->expectException(EnterpriseCapabilityDisabledException::class);
        app(WorkflowDesignerService::class)->getCanvas($context, $definition->publicId);
    }

    public function test_designer_health_reports_missing_canvas_snapshots_table(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('workflow_canvas_snapshots');

        $health = app(WorkflowDesignerHealthService::class)->assess($this->tenantContext());

        $this->assertSame('warning', $health['status']);
        $this->assertContains('workflow_canvas_snapshots', $health['missing_tables']);
        $this->assertStringContainsString('Run php artisan migrate.', $health['warnings'][0]);
    }

    public function test_search_indexing_is_best_effort_for_designer_operations(): void
    {
        $this->mock(SearchIndexService::class, function ($mock): void {
            $mock->shouldReceive('upsert')->andThrow(new \RuntimeException('search unavailable'));
        });

        $context = $this->tenantContext();
        $definition = $this->draftWorkflow($context, 'designer.search.best.effort');
        $service = app(WorkflowDesignerService::class);

        $saved = $service->saveCanvas($context, $definition->publicId, $this->sampleCanvas());
        $this->assertNotEmpty($saved->publicId);

        $cloned = $service->cloneWorkflow($context, $definition->publicId);
        $this->assertNotSame($definition->publicId, $cloned->clonedDefinitionPublicId);

        $export = $service->exportWorkflow($context, $definition->publicId);
        $imported = $service->importWorkflow($context, $export->payload);
        $this->assertSame('draft', $imported->status);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'designer-'.uniqid()]);

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

    private function viewerContext(TenantContext $ownerContext): TenantContext
    {
        $viewer = $this->createActiveUser();
        $viewerRole = \App\Models\Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', 'viewer')
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $viewer->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $viewerRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $viewer,
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
        $token = $this->issueToken($context->user);

        return [
            'Authorization' => 'Bearer '.$token,
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }

    private function draftWorkflow(TenantContext $context, string $workflowKey): \App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference
    {
        return app(WorkflowDefinitionService::class)->create($context, new WorkflowDefinitionData(
            workflowKey: $workflowKey,
            name: 'Designer Workflow',
            nodes: [
                new WorkflowNodeData('start', 'start'),
                new WorkflowNodeData('end', 'end'),
            ],
            transitions: [
                new WorkflowTransitionData('t1', 'start', 'end'),
            ],
            triggers: [new WorkflowTriggerData('manual')],
        ));
    }

    private function sampleCanvas(): WorkflowCanvas
    {
        return new WorkflowCanvas(
            nodes: [
                new WorkflowCanvasNode('start', 'start', 'Start', 0, 100, 80, 80),
                new WorkflowCanvasNode('end', 'end', 'End', 200, 100, 80, 80),
            ],
            edges: [
                new WorkflowCanvasEdge('e1', 'start', 'end', 'Next'),
            ],
            viewport: new WorkflowCanvasViewport(0, 0, 1),
            metadata: new WorkflowDesignerMetadata(
                layout: ['direction' => 'horizontal'],
                designerVersion: '1.0',
                lastSavedAt: now()->toIso8601String(),
            ),
        );
    }
}
