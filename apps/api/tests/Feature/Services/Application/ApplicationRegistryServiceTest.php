<?php

namespace Tests\Feature\Services\Application;

use App\Exceptions\Application\ApplicationNotFoundException;
use App\Models\Application;
use App\Services\Application\ApplicationRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ApplicationRegistryServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private ApplicationRegistryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ApplicationRegistryService::class);
    }

    public function test_lists_platform_catalog_in_name_order(): void
    {
        $this->seedApplicationCatalog();

        $catalog = $this->service->listPlatformCatalog();

        $this->assertCount(4, $catalog);
        $this->assertSame(
            ['Demo Application', 'HEOS Core', 'Hosteady Admin', 'Workspace Module'],
            $catalog->pluck('name')->all(),
        );
    }

    public function test_lists_available_for_install(): void
    {
        $this->seedApplicationCatalog();

        $available = $this->service->listAvailableForInstall();

        $this->assertCount(4, $available);
        $this->assertEqualsCanonicalizing(
            ['core', 'demo', 'hosteady-admin', 'workspace'],
            $available->pluck('key')->all(),
        );
    }

    public function test_finds_application_by_public_id(): void
    {
        $this->seedApplicationCatalog();

        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $found = $this->service->findByPublicId($demo->public_id);

        $this->assertTrue($found->is($demo));
    }

    public function test_finds_application_by_key(): void
    {
        $this->seedApplicationCatalog();

        $found = $this->service->findByKey('workspace');

        $this->assertSame('workspace', $found->key);
    }

    public function test_throws_when_public_id_not_found(): void
    {
        $this->seedApplicationCatalog();

        $this->expectException(ApplicationNotFoundException::class);

        $this->service->findByPublicId('01999999-9999-7999-8999-999999999999');
    }

    public function test_throws_when_key_not_found(): void
    {
        $this->seedApplicationCatalog();

        $this->expectException(ApplicationNotFoundException::class);

        $this->service->findByKey('missing');
    }
}
