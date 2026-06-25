<?php

namespace Tests\Feature\Console;

use App\Models\AuditLog;
use App\Enums\AuditAction;
use App\Modules\Sdk\Data\ModuleValidationIssue;
use App\Modules\Sdk\Data\ModuleValidationReport;
use App\Services\Module\ModuleValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HeosDoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_runs_successfully_after_sync(): void
    {
        Artisan::call('heos:sync-modules');

        $exitCode = Artisan::call('heos:doctor');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('HEOS Platform Doctor', Artisan::output());
    }

    public function test_doctor_returns_warning_exit_code_when_catalog_missing(): void
    {
        $exitCode = Artisan::call('heos:doctor');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Missing', Artisan::output());
    }

    public function test_doctor_json_output_contains_platform_summary(): void
    {
        Artisan::call('heos:doctor', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('platform_summary', $payload);
        $this->assertArrayHasKey('validation', $payload);
        $this->assertArrayHasKey('dependency_graph', $payload);
        $this->assertArrayHasKey('health', $payload);
        $this->assertSame(3, $payload['platform_summary']['module_count']);
    }

    public function test_doctor_returns_error_exit_code_when_validation_fails(): void
    {
        $this->mock(ModuleValidationService::class, function ($mock): void {
            $mock->shouldReceive('validate')->andReturn(new ModuleValidationReport([
                new ModuleValidationIssue('invalid_manifest', 'Manifest validation failed.', 'demo'),
            ]));
        });

        $exitCode = Artisan::call('heos:doctor', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(2, $exitCode);
        $this->assertSame(2, $payload['exit_code']);
        $this->assertNotEmpty($payload['errors']);
    }

    public function test_doctor_generate_docs_creates_markdown_files(): void
    {
        $outputDirectory = storage_path('framework/testing/module-docs-'.uniqid());
        $result = app(\App\Services\Module\ModuleDocumentationService::class)->generate($outputDirectory);

        $this->assertFileExists($outputDirectory.'/index.md');
        $this->assertFileExists($outputDirectory.'/demo.md');
        $this->assertSame(4, count($result->generatedFiles));
    }

    public function test_doctor_records_audit_event(): void
    {
        Artisan::call('heos:doctor');

        $this->assertTrue(
            AuditLog::query()
                ->where('action', AuditAction::ModuleDoctorExecuted->value)
                ->exists(),
        );
    }

    public function test_doctor_includes_runtime_contribution_support_for_demo(): void
    {
        Artisan::call('heos:doctor', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertTrue($payload['runtime_contribution_support']['demo']);
        $this->assertFalse($payload['runtime_contribution_support']['core']);
    }

    public function test_doctor_includes_lifecycle_support_for_all_modules(): void
    {
        Artisan::call('heos:doctor', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertTrue($payload['lifecycle_support']['core']);
        $this->assertTrue($payload['lifecycle_support']['workspace']);
        $this->assertTrue($payload['lifecycle_support']['demo']);
    }

    public function test_doctor_reports_topological_dependency_graph(): void
    {
        Artisan::call('heos:doctor', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertContains('core', $payload['dependency_graph']['nodes']);
        $this->assertContains('demo', $payload['dependency_graph']['nodes']);
        $this->assertSame([], $payload['dependency_graph']['cycles']);
    }

    public function test_doctor_recommends_sync_when_catalog_missing(): void
    {
        Artisan::call('heos:doctor', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertContains(
            'Run `php artisan heos:sync-modules` to synchronize module manifests into the catalog.',
            $payload['recommendations'],
        );
    }
}
