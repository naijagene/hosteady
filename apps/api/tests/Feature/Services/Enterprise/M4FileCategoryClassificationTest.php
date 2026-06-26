<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\FileCategory;
use App\Models\PlatformFile;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileUploadRequest;
use App\Services\Enterprise\FileMedia\EnterpriseStorageHealthService;
use App\Services\Enterprise\FileMedia\FileCategoryClassifier;
use App\Services\Enterprise\FileMedia\FileService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4FileCategoryClassificationTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_classifier_maps_image_mime_to_image_category(): void
    {
        $category = app(FileCategoryClassifier::class)->classify('image/png', 'png');

        $this->assertSame(FileCategory::Image, $category);
    }

    public function test_classifier_maps_application_json_to_data_category(): void
    {
        $category = app(FileCategoryClassifier::class)->classify('application/json', 'json');

        $this->assertSame(FileCategory::Data, $category);
    }

    public function test_classifier_uses_extension_when_mime_is_missing(): void
    {
        $category = app(FileCategoryClassifier::class)->classify(null, 'pdf');

        $this->assertSame(FileCategory::Document, $category);
    }

    public function test_classifier_maps_unknown_mime_to_other_without_extension_fallback(): void
    {
        $category = app(FileCategoryClassifier::class)->classify('application/octet-stream', 'pdf');

        $this->assertSame(FileCategory::Other, $category);
    }

    public function test_upload_persists_category_on_platform_file(): void
    {
        $context = $this->tenantContext();

        $result = app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'photo.png',
            mimeType: 'image/png',
            sizeBytes: 4,
            contents: 'png',
        ));

        $file = PlatformFile::query()->where('public_id', $result->publicId)->firstOrFail();

        $this->assertSame(FileCategory::Image, $file->category);
        $this->assertSame('image', $result->category);
    }

    public function test_api_resource_exposes_category(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->post('/api/v1/tenant/files', [
                'file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
                'visibility' => 'organization',
            ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.category', 'document');
    }

    public function test_runtime_metadata_exposes_supported_categories(): void
    {
        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertSame(
            ['image', 'video', 'audio', 'document', 'archive', 'data', 'other'],
            $runtime->runtimeMetadata['enterprise']['storage']['supported_categories'],
        );
    }

    public function test_storage_health_includes_supported_categories(): void
    {
        $context = $this->tenantContext();
        $health = app(EnterpriseStorageHealthService::class)->runtimeContribution($context);

        $this->assertContains('document', $health['supported_categories']);
        $this->assertContains('other', $health['supported_categories']);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'file-category-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }
}
