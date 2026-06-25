<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HeosMakeModuleCommandTest extends TestCase
{
    private string $modulePath;

    private string $testPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulePath = app_path('Modules/ScaffoldSample');
        $this->testPath = base_path('tests/Feature/Modules/ScaffoldSample');
    }

    protected function tearDown(): void
    {
        $this->deleteIfExists($this->modulePath);
        $this->deleteIfExists($this->testPath);

        parent::tearDown();
    }

    public function test_scaffolds_module_files(): void
    {
        $exitCode = Artisan::call('heos:make-module', [
            'name' => 'ScaffoldSample',
            '--with-settings' => true,
            '--with-permissions' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->modulePath.'/ScaffoldSampleModule.php');
        $this->assertFileExists($this->modulePath.'/ScaffoldSampleModuleServiceProvider.php');
        $this->assertFileExists($this->modulePath.'/README.md');
        $this->assertFileExists($this->testPath.'/ScaffoldSampleModuleTest.php');

        $contents = file_get_contents($this->modulePath.'/ScaffoldSampleModule.php');
        $this->assertIsString($contents);
        $this->assertStringContainsString('MODULE_UUID', $contents);
        $this->assertStringContainsString('ModuleSettingDefinition', $contents);
        $this->assertStringContainsString('ModulePermission', $contents);
    }

    public function test_rejects_existing_module_directory(): void
    {
        mkdir($this->modulePath, 0777, true);

        $exitCode = Artisan::call('heos:make-module', [
            'name' => 'ScaffoldSample',
        ]);

        $this->assertSame(1, $exitCode);
    }

    private function deleteIfExists(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($path);
    }
}
