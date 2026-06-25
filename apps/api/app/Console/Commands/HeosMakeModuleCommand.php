<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class HeosMakeModuleCommand extends Command
{
    protected $signature = 'heos:make-module
        {name : The module name in StudlyCase}
        {--core : Mark the module as a core platform module}
        {--with-settings : Scaffold sample setting definitions}
        {--with-permissions : Scaffold sample permissions}';

    protected $description = 'Scaffold a new HEOS application module';

    public function handle(Filesystem $files): int
    {
        $studlyName = Str::studly($this->argument('name'));
        $moduleKey = Str::kebab($studlyName);
        $moduleUuid = (string) Str::uuid7();

        $moduleDirectory = app_path('Modules/'.$studlyName);
        $testDirectory = base_path('tests/Feature/Modules/'.$studlyName);

        if ($files->isDirectory($moduleDirectory)) {
            $this->components->error(sprintf('Module directory already exists: %s', $moduleDirectory));

            return self::FAILURE;
        }

        $files->ensureDirectoryExists($moduleDirectory);
        $files->ensureDirectoryExists($testDirectory);

        $files->put($moduleDirectory.'/'.$studlyName.'Module.php', $this->moduleStub($studlyName, $moduleKey, $moduleUuid));
        $files->put($moduleDirectory.'/'.$studlyName.'ModuleServiceProvider.php', $this->providerStub($studlyName));
        $files->put($moduleDirectory.'/README.md', $this->readmeStub($studlyName, $moduleKey));
        $files->put($testDirectory.'/'.$studlyName.'ModuleTest.php', $this->testStub($studlyName, $moduleKey));

        $this->components->info(sprintf('Module [%s] created successfully.', $studlyName));
        $this->line('Next steps:');
        $this->line('  1. Register '.$studlyName.'ModuleServiceProvider::class in config/heos.php');
        $this->line('  2. Run php artisan test tests/Feature/Modules/'.$studlyName);

        return self::SUCCESS;
    }

    private function moduleStub(string $studlyName, string $moduleKey, string $moduleUuid): string
    {
        $isCore = $this->option('core') ? 'true' : 'false';
        $settingsBlock = $this->option('with-settings')
            ? $this->settingsStub()
            : '';
        $permissionsBlock = $this->option('with-permissions')
            ? $this->permissionsStub($moduleKey)
            : '';

        return <<<PHP
<?php

namespace App\\Modules\\{$studlyName};

use App\\Modules\\Sdk\\AbstractApplicationModule;
use App\\Modules\\Sdk\\Data\\ModuleManifest;
{$this->optionalUseStatements()}
class {$studlyName}Module extends AbstractApplicationModule
{
    public const MODULE_UUID = '{$moduleUuid}';

    public function key(): string
    {
        return '{$moduleKey}';
    }

    public function name(): string
    {
        return '{$studlyName}';
    }

    public function version(): string
    {
        return '0.1.0';
    }
{$permissionsBlock}{$settingsBlock}
    public function manifest(): ModuleManifest
    {
        return \$this->buildManifest(
            moduleUuid: self::MODULE_UUID,
            key: \$this->key(),
            name: \$this->name(),
            version: \$this->version(),
            isCore: {$isCore},
            category: 'business',
        );
    }
}

PHP;
    }

    private function optionalUseStatements(): string
    {
        $uses = [];

        if ($this->option('with-permissions')) {
            $uses[] = 'use App\\Modules\\Sdk\\Data\\ModulePermission;';
        }

        if ($this->option('with-settings')) {
            $uses[] = 'use App\\Modules\\Sdk\\Data\\ModuleSettingDefinition;';
        }

        if ($uses === []) {
            return '';
        }

        return implode("\n", $uses)."\n";
    }

    private function permissionsStub(string $moduleKey): string
    {
        return <<<PHP

    public function permissions(): array
    {
        return [
            new ModulePermission('{$moduleKey}.records.read', 'Read Records'),
        ];
    }

PHP;
    }

    private function settingsStub(): string
    {
        return <<<PHP

    public function settingDefinitions(): array
    {
        return [
            new ModuleSettingDefinition(
                settingKey: 'feature.enabled',
                label: 'Feature Enabled',
                description: 'Toggle the primary feature.',
                settingType: 'boolean',
                defaultValue: false,
            ),
        ];
    }

PHP;
    }

    private function providerStub(string $studlyName): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$studlyName};

use App\\Providers\\HeosModuleServiceProvider;

class {$studlyName}ModuleServiceProvider extends HeosModuleServiceProvider
{
    protected function moduleClass(): string
    {
        return {$studlyName}Module::class;
    }
}

PHP;
    }

    private function readmeStub(string $studlyName, string $moduleKey): string
    {
        return <<<MD
# {$studlyName} Module

HEOS application module scaffold.

- Module key: `{$moduleKey}`
- Register `{$studlyName}ModuleServiceProvider` in `config/heos.php`
- Manifest version: 1
- Reserved commands: `heos:doctor`, `heos:sync-modules` (future slices)

MD;
    }

    private function testStub(string $studlyName, string $moduleKey): string
    {
        return <<<PHP
<?php

namespace Tests\\Feature\\Modules\\{$studlyName};

use App\\Modules\\{$studlyName}\\{$studlyName}Module;
use App\\Modules\\Sdk\\ModuleManifestValidator;
use Tests\\TestCase;

class {$studlyName}ModuleTest extends TestCase
{
    public function test_module_manifest_is_valid(): void
    {
        \$module = new {$studlyName}Module;
        \$report = app(ModuleManifestValidator::class)->validateModule(\$module);

        \$this->assertTrue(\$report->isValid());
        \$this->assertSame('{$moduleKey}', \$module->key());
    }
}

PHP;
    }
}
