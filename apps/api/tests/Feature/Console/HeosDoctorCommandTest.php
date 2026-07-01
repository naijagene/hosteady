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

        $this->assertSame(1, $exitCode);
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
        $this->assertSame(4, $payload['platform_summary']['module_count']);
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
        $this->assertSame(5, count($result->generatedFiles));
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

    public function test_doctor_does_not_crash_when_platform_jobs_table_is_missing(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('platform_jobs');

        $exitCode = Artisan::call('heos:doctor', ['--json' => true]);

        $this->assertSame(1, $exitCode);
        $payload = json_decode(Artisan::output(), true);
        $this->assertIsArray($payload);
        $this->assertSame('warning', $payload['platform_summary']['enterprise']['jobs']['status']);
    }

    public function test_jobs_and_scheduler_health_report_missing_tables(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('platform_jobs');
        $jobs = app(\App\Services\Enterprise\Jobs\PlatformJobHealthService::class)->assess();

        \Illuminate\Support\Facades\Schema::dropIfExists('scheduled_tasks');
        $scheduler = app(\App\Services\Enterprise\Scheduler\SchedulerHealthService::class)->assess();

        $this->assertSame('warning', $jobs['status']);
        $this->assertContains('platform_jobs', $jobs['missing_tables']);
        $this->assertStringContainsString('Run php artisan migrate.', $jobs['warnings'][0]);

        $this->assertSame('warning', $scheduler['status']);
        $this->assertContains('scheduled_tasks', $scheduler['missing_tables']);
    }

    public function test_workflow_runtime_health_reports_missing_runtime_tables(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('workflow_instances');

        $health = app(\App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeHealthService::class)->assess();

        $this->assertSame('warning', $health['status']);
        $this->assertContains('workflow_instances', $health['missing_tables']);
        $this->assertStringContainsString('Run php artisan migrate.', $health['warnings'][0]);
    }

    public function test_doctor_json_output_includes_missing_table_warning(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('platform_jobs');

        Artisan::call('heos:doctor', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertStringContainsString(
            'Required table [platform_jobs] is missing. Run php artisan migrate.',
            $payload['platform_summary']['enterprise']['jobs']['warnings'][0],
        );

        $this->assertTrue(collect($payload['warnings'])->contains(
            fn (string $warning): bool => str_contains($warning, 'platform_jobs')
                && str_contains($warning, 'Run php artisan migrate.'),
        ));
    }
}
