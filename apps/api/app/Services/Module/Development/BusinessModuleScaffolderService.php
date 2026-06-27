<?php

namespace App\Services\Module\Development;

use App\Modules\Sdk\Development\Contracts\BusinessModuleScaffolder;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleScaffoldRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleScaffoldResult;
use App\Modules\Sdk\Development\Enums\BusinessModuleScaffoldTarget;
use App\Modules\Sdk\Development\Exceptions\BusinessModuleScaffoldException;
use Illuminate\Support\Str;

class BusinessModuleScaffolderService implements BusinessModuleScaffolder
{
    public function __construct(
        private readonly BusinessModuleFilesystemService $filesystem,
        private readonly BusinessModuleRegistryService $registryService,
        private readonly BusinessModuleAuditRecorder $auditRecorder,
    ) {
    }

    public function scaffold(BusinessModuleScaffoldRequest $request): BusinessModuleScaffoldResult
    {
        $moduleKey = Str::kebab($request->moduleKey);
        $studlyName = Str::studly(str_replace(['.', '-', '_'], ' ', $moduleKey));
        $modulePath = $this->filesystem->modulePathForKey($moduleKey);

        if ($this->filesystem->moduleDirectoryExists($moduleKey) && ! $request->force) {
            throw new BusinessModuleScaffoldException(sprintf('Module directory already exists: %s', $modulePath));
        }

        if (! is_dir($modulePath)) {
            mkdir($modulePath, 0777, true);
        }

        $created = $this->filesystem->ensureStructure($modulePath);
        $skipped = [];

        $manifest = BusinessModuleManifest::fromArray([
            'module_key' => $moduleKey,
            'name' => $request->name ?: $studlyName,
            'description' => sprintf('%s business module for HEOS.', $studlyName),
            'type' => $request->type,
            'version' => '0.1.0',
            'permissions' => [[
                'key' => $moduleKey.'.records.read',
                'name' => 'Read '.$studlyName.' Records',
                'domain' => 'business',
            ]],
            'routes' => [[
                'name' => $moduleKey.'.records.index',
                'method' => 'GET',
                'uri' => '/records',
                'action' => "{$studlyName}Controller@index",
            ]],
            'metadata' => ['scaffolded' => true],
        ]);

        $manifestPath = $modulePath.'/Config/manifest.php';
        if (is_file($manifestPath) && ! $request->force) {
            $skipped[] = $manifestPath;
        } else {
            file_put_contents($manifestPath, $this->manifestStub($manifest));
            $created[] = $manifestPath;
        }

        $servicePath = $modulePath.'/Services/'.$studlyName.'Service.php';
        if (is_file($servicePath) && ! $request->force) {
            $skipped[] = $servicePath;
        } else {
            file_put_contents($servicePath, $this->serviceStub($studlyName));
            $created[] = $servicePath;
        }

        $providerPath = $modulePath.'/Providers/'.$studlyName.'ServiceProvider.php';
        if (is_file($providerPath) && ! $request->force) {
            $skipped[] = $providerPath;
        } else {
            file_put_contents($providerPath, $this->providerStub($studlyName));
            $created[] = $providerPath;
        }

        $readmePath = $modulePath.'/README.md';
        if (! is_file($readmePath) || $request->force) {
            file_put_contents($readmePath, $this->readmeStub($studlyName, $moduleKey));
            $created[] = $readmePath;
        }

        foreach ($request->targets as $target) {
            $targetEnum = BusinessModuleScaffoldTarget::tryFrom($target);

            if ($targetEnum === BusinessModuleScaffoldTarget::Api) {
                $controllerPath = $modulePath.'/Http/Controllers/'.$studlyName.'Controller.php';
                if (! is_file($controllerPath) || $request->force) {
                    file_put_contents($controllerPath, $this->controllerStub($studlyName));
                    $created[] = $controllerPath;
                }

                $routesPath = $modulePath.'/Routes/api.php';
                if (! is_file($routesPath) || $request->force) {
                    file_put_contents($routesPath, $this->routesStub($moduleKey));
                    $created[] = $routesPath;
                }
            }

            if ($targetEnum === BusinessModuleScaffoldTarget::Test) {
                $testPath = base_path('tests/Feature/Modules/'.$studlyName.'/'.$studlyName.'ModuleTest.php');
                $testDir = dirname($testPath);

                if (! is_dir($testDir)) {
                    mkdir($testDir, 0777, true);
                }

                if (! is_file($testPath) || $request->force) {
                    file_put_contents($testPath, $this->testStub($studlyName, $moduleKey));
                    $created[] = $testPath;
                }
            }

            if ($targetEnum === BusinessModuleScaffoldTarget::Entity) {
                $entityPath = $modulePath.'/Domain/Models/'.$studlyName.'Record.php';
                if (! is_file($entityPath) || $request->force) {
                    file_put_contents($entityPath, $this->entityStub($studlyName));
                    $created[] = $entityPath;
                }
            }

            if ($targetEnum === BusinessModuleScaffoldTarget::Seeder) {
                $seederPath = $modulePath.'/Database/Seeders/'.$studlyName.'Seeder.php';
                if (! is_file($seederPath) || $request->force) {
                    file_put_contents($seederPath, $this->seederStub($studlyName));
                    $created[] = $seederPath;
                }
            }

            if ($targetEnum === BusinessModuleScaffoldTarget::Workflow) {
                $workflowPath = $modulePath.'/Domain/Data/'.$studlyName.'Workflow.php';
                if (! is_file($workflowPath) || $request->force) {
                    file_put_contents($workflowPath, $this->workflowStub($studlyName));
                    $created[] = $workflowPath;
                }
            }
        }

        $this->registryService->register($manifest);
        $this->auditRecorder->recordScaffolded($moduleKey);

        return new BusinessModuleScaffoldResult(
            moduleKey: $moduleKey,
            modulePath: $modulePath,
            createdFiles: array_values(array_unique($created)),
            skippedFiles: array_values(array_unique($skipped)),
        );
    }

    private function manifestStub(BusinessModuleManifest $manifest): string
    {
        $export = var_export($manifest->toArray(), true);

        return <<<PHP
<?php

return {$export};

PHP;
    }

    private function serviceStub(string $studlyName): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$studlyName}\\Services;

class {$studlyName}Service
{
}

PHP;
    }

    private function providerStub(string $studlyName): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$studlyName}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$studlyName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }
}

PHP;
    }

    private function controllerStub(string $studlyName): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$studlyName}\\Http\\Controllers;

class {$studlyName}Controller
{
    public function index(): array
    {
        return ['data' => []];
    }
}

PHP;
    }

    private function routesStub(string $moduleKey): string
    {
        return <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;

Route::prefix('{$moduleKey}')->group(function () {
    Route::get('/records', fn () => ['data' => []]);
});

PHP;
    }

    private function entityStub(string $studlyName): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$studlyName}\\Domain\\Models;

class {$studlyName}Record
{
}

PHP;
    }

    private function seederStub(string $studlyName): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$studlyName}\\Database\\Seeders;

use Illuminate\\Database\\Seeder;

class {$studlyName}Seeder extends Seeder
{
    public function run(): void
    {
    }
}

PHP;
    }

    private function workflowStub(string $studlyName): string
    {
        return <<<PHP
<?php

namespace App\\Modules\\{$studlyName}\\Domain\\Data;

class {$studlyName}Workflow
{
}

PHP;
    }

    private function readmeStub(string $studlyName, string $moduleKey): string
    {
        return <<<MD
# {$studlyName}

HEOS business module scaffold.

- Module key: `{$moduleKey}`
- Manifest: `Config/manifest.php`
- Register provider manually when ready.

MD;
    }

    private function testStub(string $studlyName, string $moduleKey): string
    {
        return <<<PHP
<?php

namespace Tests\\Feature\\Modules\\{$studlyName};

use App\\Modules\\Sdk\\Development\\Data\\BusinessModuleManifest;
use Tests\\TestCase;

class {$studlyName}ModuleTest extends TestCase
{
    public function test_manifest_is_valid(): void
    {
        \$manifest = BusinessModuleManifest::fromArray([
            'module_key' => '{$moduleKey}',
            'name' => '{$studlyName}',
            'version' => '0.1.0',
        ]);

        \$this->assertSame('{$moduleKey}', \$manifest->moduleKey);
    }
}

PHP;
    }
}
