<?php

namespace Tests\Feature\Console;

use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HeosSyncModulesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_modules_runs_successfully(): void
    {
        $exitCode = Artisan::call('heos:sync-modules');

        $this->assertSame(0, $exitCode);
        $this->assertSame(3, Application::query()->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $exitCode = Artisan::call('heos:sync-modules', ['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, Application::query()->count());
    }

    public function test_module_filter_syncs_only_requested_module(): void
    {
        $exitCode = Artisan::call('heos:sync-modules', ['--module' => 'demo']);

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, Application::query()->count());
        $this->assertSame('demo', Application::query()->value('key'));
    }

    public function test_json_returns_structured_payload(): void
    {
        Artisan::call('heos:sync-modules', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('modules_scanned', $payload);
        $this->assertArrayHasKey('success', $payload);
        $this->assertTrue($payload['success']);
    }

    public function test_unknown_module_exits_with_failure(): void
    {
        $exitCode = Artisan::call('heos:sync-modules', ['--module' => 'missing']);

        $this->assertSame(1, $exitCode);
    }
}
