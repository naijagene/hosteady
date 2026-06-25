<?php

namespace Tests\Feature\Modules\Sdk;

use App\Modules\Demo\DemoModule;
use App\Modules\Sdk\Data\ModuleDependency;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Data\ModulePermission;
use App\Modules\Sdk\Data\ModuleRouteCollection;
use App\Modules\Sdk\Data\ModuleSettingDefinition;
use App\Modules\Sdk\ModuleManifestValidator;
use Tests\TestCase;

class ModuleManifestValidatorTest extends TestCase
{
    private ModuleManifestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ModuleManifestValidator;
    }

    public function test_accepts_valid_demo_module(): void
    {
        $module = new DemoModule;

        $report = $this->validator->validateModule($module, ['core', 'workspace']);

        $this->assertTrue($report->isValid());
    }

    public function test_rejects_invalid_semver(): void
    {
        $manifest = $this->sampleManifest(version: 'not-semver');

        $issues = $this->validator->validateManifest($manifest);

        $this->assertNotEmpty($issues);
        $this->assertSame('invalid_version', $issues[0]->code);
    }

    public function test_rejects_invalid_module_key(): void
    {
        $manifest = $this->sampleManifest(key: 'Bad Key');

        $issues = $this->validator->validateManifest($manifest);

        $this->assertSame('invalid_module_key', $issues[0]->code);
    }

    public function test_rejects_duplicate_setting_keys(): void
    {
        $manifest = new ModuleManifest(
            manifestVersion: 1,
            moduleUuid: '01900000-0000-7000-8000-000000000099',
            key: 'sample',
            name: 'Sample',
            version: '1.0.0',
            category: null,
            icon: null,
            description: null,
            isCore: false,
            bootstrap: false,
            capabilities: [],
            dependencies: [],
            permissions: [],
            settings: [
                new ModuleSettingDefinition('feature.enabled', 'One', null, 'boolean', false),
                new ModuleSettingDefinition('feature.enabled', 'Two', null, 'boolean', false),
            ],
            navigation: [],
            routes: new ModuleRouteCollection,
        );

        $issues = $this->validator->validateManifest($manifest);

        $this->assertSame('duplicate_setting_key', $issues[0]->code);
    }

    public function test_rejects_permission_outside_module_prefix(): void
    {
        $manifest = new ModuleManifest(
            manifestVersion: 1,
            moduleUuid: '01900000-0000-7000-8000-000000000099',
            key: 'sample',
            name: 'Sample',
            version: '1.0.0',
            category: null,
            icon: null,
            description: null,
            isCore: false,
            bootstrap: false,
            capabilities: [],
            dependencies: [],
            permissions: [new ModulePermission('other.records.read', 'Read')],
            settings: [],
            navigation: [],
            routes: new ModuleRouteCollection,
        );

        $issues = $this->validator->validateManifest($manifest);

        $this->assertSame('invalid_permission_key', $issues[0]->code);
    }

    public function test_rejects_unknown_dependency(): void
    {
        $manifest = new ModuleManifest(
            manifestVersion: 1,
            moduleUuid: '01900000-0000-7000-8000-000000000099',
            key: 'sample',
            name: 'Sample',
            version: '1.0.0',
            category: null,
            icon: null,
            description: null,
            isCore: false,
            bootstrap: false,
            capabilities: [],
            dependencies: [new ModuleDependency('missing', '^1.0.0')],
            permissions: [],
            settings: [],
            navigation: [],
            routes: new ModuleRouteCollection,
        );

        $issues = $this->validator->validateManifest($manifest, ['core']);

        $this->assertSame('unknown_dependency', $issues[0]->code);
    }

    public function test_accepts_dependency_with_reserved_version_range(): void
    {
        $manifest = new ModuleManifest(
            manifestVersion: 1,
            moduleUuid: '01900000-0000-7000-8000-000000000099',
            key: 'sample',
            name: 'Sample',
            version: '1.0.0',
            category: null,
            icon: null,
            description: null,
            isCore: false,
            bootstrap: false,
            capabilities: [],
            dependencies: [new ModuleDependency('core', '^1.0.0')],
            permissions: [],
            settings: [],
            navigation: [],
            routes: new ModuleRouteCollection,
        );

        $issues = $this->validator->validateManifest($manifest, ['core']);

        $this->assertSame([], $issues);
    }

    public function test_registry_validation_detects_duplicate_module_uuid(): void
    {
        $sharedUuid = '01900000-0000-7000-8000-000000000099';

        $report = $this->validator->validateRegistry([
            new DuplicateUuidModule('alpha', $sharedUuid),
            new DuplicateUuidModule('beta', $sharedUuid),
        ]);

        $this->assertFalse($report->isValid());
        $this->assertSame('duplicate_module_uuid', $report->issues[0]->code);
    }

    private function sampleManifest(string $key = 'sample', string $version = '1.0.0'): ModuleManifest
    {
        return new ModuleManifest(
            manifestVersion: 1,
            moduleUuid: '01900000-0000-7000-8000-000000000099',
            key: $key,
            name: 'Sample',
            version: $version,
            category: null,
            icon: null,
            description: null,
            isCore: false,
            bootstrap: false,
            capabilities: [],
            dependencies: [],
            permissions: [],
            settings: [],
            navigation: [],
            routes: new ModuleRouteCollection,
        );
    }
}

class DuplicateUuidModule extends \App\Modules\Sdk\AbstractApplicationModule
{
    public function __construct(
        private readonly string $moduleKey,
        private readonly string $moduleUuid,
    ) {
    }

    public function key(): string
    {
        return $this->moduleKey;
    }

    public function name(): string
    {
        return ucfirst($this->moduleKey);
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function manifest(): ModuleManifest
    {
        return $this->buildManifest(
            moduleUuid: $this->moduleUuid,
            key: $this->key(),
            name: $this->name(),
            version: $this->version(),
            isCore: false,
        );
    }
}
