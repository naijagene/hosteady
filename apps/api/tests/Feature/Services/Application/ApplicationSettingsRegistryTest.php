<?php

namespace Tests\Feature\Services\Application;

use App\Enums\SettingDefinitionStatus;
use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use App\Services\Application\ApplicationSettingsRegistry;
use App\Services\Application\Data\SettingDefinition;
use App\Services\Application\Data\SettingValidationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ApplicationSettingsRegistryTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private ApplicationSettingsRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = app(ApplicationSettingsRegistry::class);
    }

    public function test_seeds_demo_workspace_definitions(): void
    {
        $this->seedHeosPlatform();

        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $this->assertTrue($this->registry->hasWorkspaceDefinitions($demo->id));
        $this->assertSame(3, $this->registry->workspaceDefinitionsForApplication($demo->id)->count());
    }

    public function test_maps_definition_rows_to_setting_definition_dtos(): void
    {
        $this->seedHeosPlatform();

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $definition = $this->registry->findWorkspaceDefinition($demo->id, 'feature.enabled');

        $this->assertInstanceOf(SettingDefinition::class, $definition);
        $this->assertSame('feature.enabled', $definition->settingKey);
        $this->assertFalse($definition->defaultValue);
        $this->assertSame('features', $definition->category);
    }

    public function test_maps_validation_rules_to_dto(): void
    {
        $this->seedHeosPlatform();

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $definition = $this->registry->findWorkspaceDefinition($demo->id, 'notification.email');

        $this->assertInstanceOf(SettingValidationRule::class, $definition?->validationRules);
        $this->assertSame('^[^@]+@[^@]+\.[^@]+$', $definition->validationRules->pattern);
    }

    public function test_returns_null_for_unknown_definition_key(): void
    {
        $this->seedHeosPlatform();

        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $this->assertNull($this->registry->findWorkspaceDefinition($demo->id, 'unknown.key'));
    }

    public function test_core_application_has_no_workspace_definitions(): void
    {
        $this->seedHeosPlatform();

        $core = Application::query()->where('key', 'core')->firstOrFail();

        $this->assertFalse($this->registry->hasWorkspaceDefinitions($core->id));
        $this->assertTrue($this->registry->workspaceDefinitionsForApplication($core->id)->isEmpty());
    }

    public function test_excludes_deprecated_definitions(): void
    {
        $this->seedHeosPlatform();

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        ApplicationSettingDefinition::query()
            ->where('application_id', $demo->id)
            ->where('setting_key', 'feature.enabled')
            ->update(['status' => SettingDefinitionStatus::Deprecated]);

        $freshRegistry = app(ApplicationSettingsRegistry::class);

        $this->assertNull($freshRegistry->findWorkspaceDefinition($demo->id, 'feature.enabled'));
        $this->assertSame(2, $freshRegistry->workspaceDefinitionsForApplication($demo->id)->count());
    }
}
