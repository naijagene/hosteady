<?php

namespace Tests\Feature\Services\Ui;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\UiActivityLog;
use App\Models\UiPage;
use App\Modules\Sdk\Ui\Contracts\UiPageRegistry;
use App\Modules\Sdk\Ui\Contracts\UiRenderer;
use App\Modules\Sdk\Ui\Contracts\UiRuntimeComposer;
use App\Modules\Sdk\Ui\Data\UiComponent;
use App\Modules\Sdk\Ui\Data\UiLayout;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Enums\UiActionType;
use App\Modules\Sdk\Ui\Enums\UiBindingType;
use App\Modules\Sdk\Ui\Enums\UiBreakpointSize;
use App\Modules\Sdk\Ui\Enums\UiConditionOperator;
use App\Modules\Sdk\Ui\Exceptions\UiRegistryException;
use App\Services\Module\ModuleDoctorService;
use App\Services\Ui\UiComponentService;
use App\Services\Ui\UiConditionEvaluatorService;
use App\Services\Ui\UiDevelopmentService;
use App\Services\Ui\UiLayoutService;
use App\Services\Ui\UiPageRegistryService;
use App\Services\Ui\UiRendererService;
use App\Services\Ui\UiRuntimeComposerService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M7UiMetadataLayoutTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_UiPageStatus_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiPageStatus $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiPageStatus::cases());
        $this->assertContains('draft', $cases);
        $this->assertContains('published', $cases);
        $this->assertContains('archived', $cases);
    }

    public function test_UiPageType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiPageType $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiPageType::cases());
        $this->assertContains('module_home', $cases);
        $this->assertContains('entity_list', $cases);
        $this->assertContains('entity_detail', $cases);
        $this->assertContains('entity_create', $cases);
        $this->assertContains('entity_edit', $cases);
        $this->assertContains('dashboard', $cases);
        $this->assertContains('report', $cases);
        $this->assertContains('workflow', $cases);
        $this->assertContains('document', $cases);
        $this->assertContains('settings', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_UiVisibility_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiVisibility $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiVisibility::cases());
        $this->assertContains('public', $cases);
        $this->assertContains('private', $cases);
        $this->assertContains('organization', $cases);
        $this->assertContains('workspace', $cases);
        $this->assertContains('role', $cases);
    }

    public function test_UiLayoutType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiLayoutType $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiLayoutType::cases());
        $this->assertContains('single_column', $cases);
        $this->assertContains('two_column', $cases);
        $this->assertContains('three_column', $cases);
        $this->assertContains('sidebar', $cases);
        $this->assertContains('header_content', $cases);
        $this->assertContains('dashboard_grid', $cases);
        $this->assertContains('tabbed', $cases);
        $this->assertContains('wizard', $cases);
        $this->assertContains('split_pane', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_UiRegionType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiRegionType $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiRegionType::cases());
        $this->assertContains('header', $cases);
        $this->assertContains('sidebar', $cases);
        $this->assertContains('content', $cases);
        $this->assertContains('footer', $cases);
        $this->assertContains('toolbar', $cases);
        $this->assertContains('card', $cases);
        $this->assertContains('tab', $cases);
        $this->assertContains('modal', $cases);
        $this->assertContains('drawer', $cases);
        $this->assertContains('widget_area', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_UiComponentType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiComponentType $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiComponentType::cases());
        $this->assertContains('form', $cases);
        $this->assertContains('table', $cases);
        $this->assertContains('dashboard', $cases);
        $this->assertContains('report', $cases);
        $this->assertContains('chart', $cases);
        $this->assertContains('metric', $cases);
        $this->assertContains('document_list', $cases);
        $this->assertContains('notification_list', $cases);
        $this->assertContains('workflow_queue', $cases);
        $this->assertContains('approval_queue', $cases);
        $this->assertContains('activity_feed', $cases);
        $this->assertContains('navigation_menu', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_UiBindingType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiBindingType $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiBindingType::cases());
        $this->assertContains('form', $cases);
        $this->assertContains('table', $cases);
        $this->assertContains('dashboard', $cases);
        $this->assertContains('report', $cases);
        $this->assertContains('entity', $cases);
        $this->assertContains('workflow', $cases);
        $this->assertContains('document', $cases);
        $this->assertContains('notification', $cases);
        $this->assertContains('static', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_UiActionType_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiActionType $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiActionType::cases());
        $this->assertContains('navigate', $cases);
        $this->assertContains('submit_form', $cases);
        $this->assertContains('refresh_table', $cases);
        $this->assertContains('open_modal', $cases);
        $this->assertContains('download_report', $cases);
        $this->assertContains('start_workflow', $cases);
        $this->assertContains('upload_document', $cases);
        $this->assertContains('send_notification', $cases);
        $this->assertContains('custom', $cases);
    }

    public function test_UiBreakpointSize_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiBreakpointSize $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiBreakpointSize::cases());
        $this->assertContains('xs', $cases);
        $this->assertContains('sm', $cases);
        $this->assertContains('md', $cases);
        $this->assertContains('lg', $cases);
        $this->assertContains('xl', $cases);
        $this->assertContains('xxl', $cases);
    }

    public function test_UiConditionOperator_enum_has_expected_cases(): void
    {
        $cases = array_map(static fn (\App\Modules\Sdk\Ui\Enums\UiConditionOperator $case) => $case->value, \App\Modules\Sdk\Ui\Enums\UiConditionOperator::cases());
        $this->assertContains('equals', $cases);
        $this->assertContains('not_equals', $cases);
        $this->assertContains('contains', $cases);
        $this->assertContains('greater_than', $cases);
        $this->assertContains('less_than', $cases);
        $this->assertContains('is_empty', $cases);
        $this->assertContains('is_not_empty', $cases);
        $this->assertContains('has_permission', $cases);
        $this->assertContains('has_role', $cases);
        $this->assertContains('feature_enabled', $cases);
    }

    public function test_UiPageDefinition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiPageDefinition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiPageDefinition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiPageReference_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiPageReference::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiPageReference::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiLayout_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiLayout::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiLayout::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiLayoutRegion_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiLayoutRegion::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiLayoutRegion::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiComponent_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiComponent::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiComponent::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiComponentBinding_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiComponentBinding::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiComponentBinding::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiComponentAction_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiComponentAction::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiComponentAction::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiPageAction_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiPageAction::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiPageAction::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiCondition_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiCondition::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiCondition::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiBreakpoint_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiBreakpoint::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiBreakpoint::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiTheme_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiTheme::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiTheme::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiPersonalization_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiPersonalization::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiPersonalization::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiRenderPayload_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiRenderPayload::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiRenderPayload::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiRenderContext_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiRenderContext::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiRenderContext::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiStatistics_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiStatistics::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiStatistics::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_UiHealthReport_dto_roundtrip(): void
    {
        $sample = \App\Modules\Sdk\Ui\Data\UiHealthReport::fromArray([]);
        $roundtrip = \App\Modules\Sdk\Ui\Data\UiHealthReport::fromArray($sample->toArray());
        $this->assertSame($sample->toArray(), $roundtrip->toArray());
    }

    public function test_ui_contracts_bound(): void
    {
        $this->assertInstanceOf(UiPageRegistryService::class, app(UiPageRegistry::class));
        $this->assertInstanceOf(UiRendererService::class, app(UiRenderer::class));
        $this->assertInstanceOf(UiRuntimeComposerService::class, app(UiRuntimeComposer::class));
    }

    public function test_ui_metadata_config_enabled(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.ui_metadata.enabled', true));
    }

    public function test_permission_catalog_has_ui_permissions(): void
    {
        $this->seedHeosPlatform();
        $this->assertSame(134, Permission::query()->count());
        foreach (['ui.read', 'ui.manage', 'ui.render', 'ui.personalize'] as $key) {
            $this->assertNotNull(Permission::query()->where('key', $key)->first());
        }
    }

    public function test_module_doctor_includes_ui_metadata(): void
    {
        $this->seedHeosPlatform();
        $report = app(ModuleDoctorService::class)->diagnose();
        $this->assertArrayHasKey('ui_metadata', $report->platformSummary['enterprise']);
    }

    public function test_register_page_from_dto(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $page = app(UiDevelopmentService::class)->registerPage($context, UiPageDefinition::fromArray([
            'module_key' => 'demo',
            'page_key' => 'home',
            'name' => 'Home',
            'page_type' => 'module_home',
        ]));
        $this->assertSame('demo', $page->moduleKey);
        $this->assertSame('home', $page->pageKey);
        $this->assertNotEmpty($page->publicId);
    }

    public function test_register_page_from_array(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $page = app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo',
            'page_key' => 'list',
            'name' => 'List',
        ]);
        $this->assertSame('list', $page->pageKey);
    }

    public function test_duplicate_page_prevention(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(UiPageRegistryService::class);
        $service->registerFromSource($context->organization->id, $context->workspace->id, null, [
            'module_key' => 'demo', 'page_key' => 'dup', 'name' => 'Dup',
        ]);
        $this->expectException(UiRegistryException::class);
        $service->registerFromSource($context->organization->id, $context->workspace->id, null, [
            'module_key' => 'demo', 'page_key' => 'dup', 'name' => 'Dup 2',
        ]);
    }

    public function test_register_layout(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $layout = app(UiDevelopmentService::class)->registerLayout($context, UiLayout::fromArray([
            'layout_key' => 'main', 'name' => 'Main', 'layout_type' => 'single_column',
        ]));
        $this->assertSame('main', $layout->layoutKey);
    }

    public function test_register_component(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $component = app(UiDevelopmentService::class)->registerComponent($context, UiComponent::fromArray([
            'component_key' => 'table', 'name' => 'Table', 'component_type' => 'table',
            'binding_type' => 'table', 'binding_config' => ['module_key' => 'demo', 'table_key' => 'items'],
        ]));
        $this->assertSame('table', $component->componentKey);
    }

    public function test_find_page_by_route_path(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiPageRegistryService::class)->registerFromSource($context->organization->id, $context->workspace->id, null, [
            'module_key' => 'demo', 'page_key' => 'route', 'name' => 'Route', 'route_path' => '/demo/route',
        ]);
        $found = app(UiPageRegistryService::class)->findByRoutePath(
            $context->organization->id, $context->workspace->id, '/demo/route',
        );
        $this->assertSame('route', $found->pageKey);
    }

    public function test_runtime_composition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'runtime', 'name' => 'Runtime',
        ]);
        $payload = app(UiDevelopmentService::class)->composeRuntime($context);
        $this->assertArrayHasKey('page', $payload->toArray());
    }

    public function test_render_payload(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'render', 'name' => 'Render',
            'layout' => ['layout_type' => 'single_column'],
            'components' => [['component_key' => 'c1', 'name' => 'C1', 'component_type' => 'custom']],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'render');
        $data = $payload->toArray();
        $this->assertArrayHasKey('layout', $data);
        $this->assertArrayHasKey('components', $data);
        $this->assertArrayHasKey('permissions', $data);
    }

    public function test_form_binding_resolution(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'form', 'name' => 'Form Page',
            'components' => [[
                'component_key' => 'form1', 'name' => 'Form', 'component_type' => 'form',
                'binding_type' => 'form', 'binding_config' => ['module_key' => 'demo', 'form_key' => 'entry'],
            ]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'form');
        $this->assertNotEmpty($payload->components);
    }

    public function test_table_binding_resolution(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'table', 'name' => 'Table Page',
            'components' => [[
                'component_key' => 'tbl', 'name' => 'Table', 'component_type' => 'table',
                'binding_type' => 'table', 'binding_config' => ['module_key' => 'demo', 'table_key' => 'items'],
            ]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'table');
        $this->assertNotEmpty($payload->components);
    }

    public function test_dashboard_binding_resolution(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'dash', 'name' => 'Dash Page',
            'components' => [[
                'component_key' => 'dash', 'name' => 'Dash', 'component_type' => 'dashboard',
                'binding_type' => 'dashboard', 'binding_config' => ['module_key' => 'demo', 'dashboard_key' => 'main'],
            ]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'dash');
        $this->assertNotEmpty($payload->components);
    }

    public function test_report_binding_resolution(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'report', 'name' => 'Report Page',
            'components' => [[
                'component_key' => 'rep', 'name' => 'Report', 'component_type' => 'report',
                'binding_type' => 'report', 'binding_config' => ['module_key' => 'demo', 'report_key' => 'summary'],
            ]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'report');
        $this->assertNotEmpty($payload->components);
    }

    public function test_condition_operators(): void
    {
        $evaluator = app(UiConditionEvaluatorService::class);
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::Equals->value, 'field' => 'status', 'value' => 'active']], ['status' => 'active']));
        $this->assertFalse($evaluator->evaluate($context, [['operator' => UiConditionOperator::NotEquals->value, 'field' => 'status', 'value' => 'active']], ['status' => 'active']));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::Contains->value, 'field' => 'name', 'value' => 'dem']], ['name' => 'demo']));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::GreaterThan->value, 'field' => 'count', 'value' => 1]], ['count' => 2]));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::LessThan->value, 'field' => 'count', 'value' => 5]], ['count' => 2]));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::IsEmpty->value, 'field' => 'note']], ['note' => '']));
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::IsNotEmpty->value, 'field' => 'note']], ['note' => 'x']));
    }

    public function test_has_permission_condition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $evaluator = app(UiConditionEvaluatorService::class);
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::HasPermission->value, 'value' => 'ui.read']]));
    }

    public function test_has_role_condition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $evaluator = app(UiConditionEvaluatorService::class);
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::HasRole->value, 'value' => 'owner']]));
    }

    public function test_feature_enabled_condition(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $evaluator = app(UiConditionEvaluatorService::class);
        $this->assertTrue($evaluator->evaluate($context, [['operator' => UiConditionOperator::FeatureEnabled->value, 'value' => 'ui_metadata']]));
    }

    public function test_actions_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'actions', 'name' => 'Actions',
            'actions' => [['action_key' => 'go', 'action_type' => UiActionType::Navigate->value, 'label' => 'Go']],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'actions');
        $this->assertNotEmpty($payload->actions);
    }

    public function test_breakpoint_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'bp', 'name' => 'Breakpoints',
            'breakpoints' => [UiBreakpointSize::Md->value => ['columns' => 2]],
        ]);
        $payload = app(UiDevelopmentService::class)->renderPage($context, 'demo', 'bp');
        $this->assertArrayHasKey(UiBreakpointSize::Md->value, $payload->breakpoints);
    }

    public function test_personalization_update(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $page = app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'pers', 'name' => 'Personalize',
        ]);
        $personalization = app(UiDevelopmentService::class)->updatePersonalization($context, $page->publicId, [
            'column_order' => ['a', 'b'],
        ]);
        $this->assertSame(['column_order' => ['a', 'b']], $personalization->personalization);
    }

    public function test_business_module_manifest_ui_integration(): void
    {
        $manifest = \App\Modules\Sdk\Development\Data\BusinessModuleManifest::fromArray([
            'module_key' => 'ui.demo',
            'name' => 'UI Demo',
            'ui' => [
                'pages' => [['page_key' => 'home', 'name' => 'Home']],
                'layouts' => [['layout_key' => 'main', 'name' => 'Main']],
                'components' => [['component_key' => 'list', 'name' => 'List', 'component_type' => 'table']],
            ],
        ]);
        $this->assertCount(1, $manifest->uiPages);
        $this->assertCount(1, $manifest->uiLayouts);
        $this->assertCount(1, $manifest->uiComponents);
    }

    public function test_business_module_base_pages_hook(): void
    {
        $module = new class extends \App\Modules\Sdk\Development\BusinessModuleBase {
            protected string $moduleKey = 'ui.hooks';
        };
        $this->assertIsArray($module->pages());
        $this->assertIsArray($module->layouts());
        $this->assertIsArray($module->components());
    }

    public function test_search_indexing_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'search', 'name' => 'Search',
        ]);
        $this->assertTrue(true);
    }

    public function test_audit_actions_recorded(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'audit', 'name' => 'Audit',
        ]);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::UiPageRegistered->value)->exists());
        $this->assertTrue(UiActivityLog::query()->where('action', 'page.registered')->exists());
    }

    public function test_platform_event_best_effort(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'event', 'name' => 'Event',
        ]);
        $this->assertTrue(true);
    }

    public function test_runtime_metadata_includes_ui_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $runtime = app(\App\Services\WorkspaceApplication\WorkspaceRuntimeResolver::class)->resolve($context);
        $this->assertTrue($runtime->capabilities['ui_metadata'] ?? false);
        $this->assertArrayHasKey('ui_metadata', $runtime->runtimeMetadata['enterprise'] ?? []);
    }

    public function test_doctor_health_section(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $health = app(UiDevelopmentService::class)->health($context);
        $this->assertTrue($health->enabled);
        $this->assertContains('No UI pages registered.', $health->warnings);
    }

    public function test_health_reports_missing_ui_tables(): void
    {
        Schema::drop('ui_pages');
        Schema::drop('ui_layouts');
        Schema::drop('ui_components');
        Schema::drop('ui_personalizations');

        $health = app(\App\Services\Ui\UiHealthService::class)->health();

        $this->assertSame('warning', $health->status);
        $this->assertContains('ui_pages', $health->missingTables);
        $this->assertStringContainsString(
            'Required table [ui_pages] is missing. Run php artisan migrate.',
            $health->warnings[0],
        );
    }

    public function test_runtime_composer_returns_safe_empty_payload_when_tables_missing(): void
    {
        Schema::drop('ui_pages');
        Schema::drop('ui_layouts');
        Schema::drop('ui_components');
        Schema::drop('ui_personalizations');

        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $payload = app(UiRuntimeComposerService::class)->compose($context);
        $data = $payload->toArray();

        $this->assertSame([], $data['page']);
        $this->assertSame([], $data['components']);
        $this->assertSame('warning', $data['runtime_context']['status']);
        $this->assertContains('ui_pages', $data['runtime_context']['missing_tables']);
    }

    public function test_page_registry_list_does_not_crash_when_ui_pages_missing(): void
    {
        Schema::drop('ui_pages');

        $context = $this->tenantContext();

        $pages = app(UiPageRegistryService::class)->list(
            $context->organization->id,
            $context->workspace->id,
        );

        $this->assertSame([], $pages);
    }

    public function test_layout_service_list_does_not_crash_when_ui_layouts_missing(): void
    {
        Schema::drop('ui_layouts');

        $context = $this->tenantContext();

        $layouts = app(UiLayoutService::class)->list(
            $context->organization->id,
            $context->workspace->id,
        );

        $this->assertSame([], $layouts);
    }

    public function test_component_service_list_does_not_crash_when_ui_components_missing(): void
    {
        Schema::drop('ui_components');

        $context = $this->tenantContext();

        $components = app(UiComponentService::class)->list(
            $context->organization->id,
            $context->workspace->id,
        );

        $this->assertSame([], $components);
    }

    public function test_heos_doctor_includes_ui_metadata_missing_table_warning(): void
    {
        Schema::drop('ui_pages');

        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('ui_metadata', $report->platformSummary['enterprise']);
        $this->assertContains('ui_pages', $report->platformSummary['enterprise']['ui_metadata']['missing_tables']);

        $this->assertTrue(collect($report->warnings)->contains(
            fn (string $warning): bool => str_contains($warning, 'enterprise.ui_metadata')
                && str_contains($warning, 'ui_pages')
                && str_contains($warning, 'Run php artisan migrate.'),
        ));
    }

    public function test_missing_table_guard(): void
    {
        Schema::drop('ui_pages');

        $health = app(\App\Services\Ui\UiHealthService::class)->health();

        $this->assertSame('warning', $health->status);
        $this->assertNotEmpty($health->missingTables);
        $this->assertStringContainsString('Run php artisan migrate.', $health->warnings[0]);
    }

    public function test_binding_types_enum(): void
    {
        $this->assertSame('form', UiBindingType::Form->value);
        $this->assertSame('table', UiBindingType::Table->value);
    }

    public function test_member_can_render_ui(): void
    {
        $owner = $this->tenantContext();
        $member = $this->memberContext($owner);
        $this->assertTrue(app(\App\Services\Ui\UiPermissionBridge::class)->canRender($member));
        $this->assertTrue(app(\App\Services\Ui\UiPermissionBridge::class)->canPersonalize($member));
    }

    public function test_viewer_cannot_manage_ui(): void
    {
        $owner = $this->tenantContext();
        $viewer = $this->viewerContext($owner);
        $this->assertFalse(app(\App\Services\Ui\UiPermissionBridge::class)->canManage($viewer));
    }

    public function test_tenant_isolation(): void
    {
        $contextA = $this->tenantContext();
        app()->instance(TenantContext::class, $contextA);
        app(UiDevelopmentService::class)->registerPage($contextA, [
            'module_key' => 'demo', 'page_key' => 'iso', 'name' => 'Iso',
        ]);
        $contextB = $this->tenantContext();
        app()->instance(TenantContext::class, $contextB);
        $pages = app(UiDevelopmentService::class)->listPages($contextB);
        $this->assertSame([], $pages);
    }

    public function test_api_list_pages(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/pages')
            ->assertOk();
    }

    public function test_api_store_page(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/ui/pages', [
                'module_key' => 'demo', 'page_key' => 'api', 'name' => 'API Page',
            ])
            ->assertCreated()
            ->assertJsonPath('data.page_key', 'api');
    }

    public function test_api_show_page(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'show', 'name' => 'Show',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/pages/demo/show')
            ->assertOk()
            ->assertJsonPath('data.page_key', 'show');
    }

    public function test_api_render_page(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'render-api', 'name' => 'Render API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/pages/demo/render-api/render')
            ->assertOk()
            ->assertJsonStructure(['data' => ['layout', 'components', 'permissions']]);
    }

    public function test_api_runtime(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/runtime')
            ->assertOk();
    }

    public function test_api_health(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/health')
            ->assertOk();
    }

    public function test_api_statistics(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/statistics')
            ->assertOk();
    }

    public function test_api_store_layout_and_component(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/ui/layouts', ['layout_key' => 'api', 'name' => 'API Layout'])
            ->assertCreated();
        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/ui/components', ['component_key' => 'api', 'name' => 'API Component'])
            ->assertCreated();
    }

    public function test_api_update_personalization(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $page = app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'pers-api', 'name' => 'Pers API',
        ]);
        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/ui/personalization/'.$page->publicId, [
                'personalization' => ['hidden' => []],
            ])
            ->assertOk();
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        app(UiDevelopmentService::class)->registerPage($context, [
            'module_key' => 'demo', 'page_key' => 'public', 'name' => 'Public',
        ]);
        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/ui/pages');
        $response->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_static_ui_routes_resolve_before_parameterized(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/ui/runtime')->assertOk();
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/ui/health')->assertOk();
        $this->withHeaders($this->tenantHeaders($context))->getJson('/api/v1/tenant/ui/statistics')->assertOk();
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ui-metadata-'.uniqid()]);
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

    /** @return array<string, string> */
    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }
}
