<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HeosModulesCommandTest extends TestCase
{
    public function test_modules_command_lists_registered_modules(): void
    {
        Artisan::call('heos:modules');

        $output = Artisan::output();

        $this->assertStringContainsString('core', $output);
        $this->assertStringContainsString('workspace', $output);
        $this->assertStringContainsString('demo', $output);
    }

    public function test_modules_json_output_contains_module_keys(): void
    {
        Artisan::call('heos:modules', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(3, $payload['module_count']);
        $this->assertCount(3, $payload['modules']);

        $keys = array_column($payload['modules'], 'module_key');
        $this->assertEqualsCanonicalizing(['core', 'demo', 'workspace'], $keys);
    }

    public function test_modules_json_with_details_includes_support_flags(): void
    {
        Artisan::call('heos:modules', ['--json' => true, '--details' => true]);

        $payload = json_decode(Artisan::output(), true);
        $demo = collect($payload['modules'])->firstWhere('module_key', 'demo');

        $this->assertTrue($demo['runtime_contributor']);
        $this->assertTrue($demo['lifecycle_supported']);
        $this->assertTrue($demo['sync_supported']);
    }

    public function test_modules_health_option_includes_statistics_in_json(): void
    {
        Artisan::call('heos:modules', ['--json' => true, '--health' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertArrayHasKey('statistics', $payload);
        $this->assertSame(3, $payload['statistics']['module_count']);
    }

    public function test_modules_details_option_prints_statistics(): void
    {
        Artisan::call('heos:modules', ['--details' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Statistics', $output);
        $this->assertStringContainsString('Modules: 3', $output);
    }

    public function test_modules_displays_uuid_version_and_dependencies(): void
    {
        Artisan::call('heos:modules', ['--details' => true, '--health' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('01900000-0000-7000-8000-000000000003', $output);
        $this->assertStringContainsString('1.0.0', $output);
        $this->assertStringContainsString('core, workspace', $output);
        $this->assertStringContainsString('healthy', $output);
    }

    public function test_modules_json_without_details_uses_compact_payload(): void
    {
        Artisan::call('heos:modules', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $module = $payload['modules'][0];

        $this->assertArrayHasKey('module_key', $module);
        $this->assertArrayNotHasKey('permissions', $module);
    }
}
