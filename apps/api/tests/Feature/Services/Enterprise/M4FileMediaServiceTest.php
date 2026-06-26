<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Enums\FileVisibility;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\PlatformFile;
use App\Models\Role;
use App\Modules\Sdk\Enterprise\Contracts\FileServicePort;
use App\Modules\Sdk\Enterprise\Contracts\StoragePort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileReference;
use App\Modules\Sdk\Enterprise\Data\FileUpdateRequest;
use App\Modules\Sdk\Enterprise\Data\FileUploadRequest;
use App\Services\Enterprise\FileMedia\EnterpriseStorageHealthService;
use App\Services\Enterprise\FileMedia\FileService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4FileMediaServiceTest extends TestCase
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

    public function test_file_reference_serializes_to_array(): void
    {
        $reference = new FileReference(
            publicId: '01900000-0000-7000-8000-000000000099',
            filename: '01900000-0000-7000-8000-000000000099.pdf',
            originalFilename: 'invoice.pdf',
            extension: 'pdf',
            mimeType: 'application/pdf',
            sizeBytes: 1024,
            visibility: 'private',
            category: 'document',
            moduleKey: 'demo',
            entityReference: new EntityReference('invoice', 'inv-001', 'demo'),
        );

        $this->assertSame('01900000-0000-7000-8000-000000000099', $reference->toArray()['public_id']);
        $this->assertSame('invoice', FileReference::fromArray($reference->toArray())->entityReference?->type);
    }

    public function test_upload_creates_platform_file_and_stores_content(): void
    {
        $context = $this->tenantContext();

        $result = app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            originalFilename: 'report.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 11,
            contents: 'pdf-content',
            visibility: 'private',
        ));

        $this->assertNotEmpty($result->publicId);
        $this->assertSame('report.pdf', $result->originalFilename);

        $file = PlatformFile::query()->where('public_id', $result->publicId)->firstOrFail();
        $this->assertTrue(Storage::disk($file->storage_disk)->exists($file->storage_path));
        $this->assertSame('pdf-content', Storage::disk($file->storage_disk)->get($file->storage_path));
    }

    public function test_upload_records_audit_event(): void
    {
        $context = $this->tenantContext();

        app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'audit.txt',
            mimeType: 'text/plain',
            sizeBytes: 4,
            contents: 'data',
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::FileUploaded->value)->exists());
    }

    public function test_download_returns_file_contents(): void
    {
        $context = $this->tenantContext();
        $service = app(FileService::class);

        $uploaded = $service->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'download.txt',
            mimeType: 'text/plain',
            sizeBytes: 7,
            contents: 'payload',
            visibility: 'organization',
        ));

        $download = $service->download($context, $uploaded->publicId);

        $this->assertSame('payload', $download->contents);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::FileDownloaded->value)->exists());
    }

    public function test_delete_soft_deletes_file_and_removes_storage(): void
    {
        $context = $this->tenantContext();
        $service = app(FileService::class);

        $uploaded = $service->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'remove.txt',
            mimeType: 'text/plain',
            sizeBytes: 3,
            contents: 'bye',
        ));

        $file = PlatformFile::query()->where('public_id', $uploaded->publicId)->firstOrFail();
        $disk = $file->storage_disk;
        $path = $file->storage_path;

        $service->delete($context, $uploaded->publicId);

        $this->assertSoftDeleted('platform_files', ['public_id' => $uploaded->publicId]);
        $this->assertFalse(Storage::disk($disk)->exists($path));
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::FileDeleted->value)->exists());
    }

    public function test_update_records_audit_event(): void
    {
        $context = $this->tenantContext();
        $service = app(FileService::class);

        $uploaded = $service->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'rename.txt',
            mimeType: 'text/plain',
            sizeBytes: 3,
            contents: 'txt',
        ));

        $service->update($context, new FileUpdateRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            filePublicId: $uploaded->publicId,
            displayName: 'Renamed',
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::FileUpdated->value)->exists());
    }

    public function test_private_visibility_blocks_other_members(): void
    {
        $ownerContext = $this->tenantContext();
        $service = app(FileService::class);

        $uploaded = $service->upload($ownerContext, new FileUploadRequest(
            scope: new EnterpriseScope($ownerContext->organizationPublicId, $ownerContext->workspacePublicId),
            originalFilename: 'secret.txt',
            mimeType: 'text/plain',
            sizeBytes: 6,
            contents: 'secret',
            visibility: 'private',
        ));

        $otherMemberContext = $this->memberContext($ownerContext);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->download($otherMemberContext, $uploaded->publicId);
    }

    public function test_organization_visibility_allows_other_members(): void
    {
        $ownerContext = $this->tenantContext();
        $service = app(FileService::class);

        $uploaded = $service->upload($ownerContext, new FileUploadRequest(
            scope: new EnterpriseScope($ownerContext->organizationPublicId, $ownerContext->workspacePublicId),
            originalFilename: 'shared.txt',
            mimeType: 'text/plain',
            sizeBytes: 6,
            contents: 'shared',
            visibility: 'organization',
        ));

        $otherMemberContext = $this->memberContext($ownerContext);
        $download = $service->download($otherMemberContext, $uploaded->publicId);

        $this->assertSame('shared', $download->contents);
    }

    public function test_entity_attachment_lists_files(): void
    {
        $context = $this->tenantContext();
        $service = app(FileService::class);
        $entity = new EntityReference('invoice', 'inv-100', 'demo');

        $service->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            originalFilename: 'attached.pdf',
            mimeType: 'application/pdf',
            sizeBytes: 4,
            contents: 'pdf',
            entityReference: $entity,
        ));

        $files = $service->listForEntity($context, $entity);

        $this->assertCount(1, $files);
        $this->assertSame('attached.pdf', $files[0]->originalFilename);
    }

    public function test_tenant_isolation_prevents_cross_organization_access(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();
        $service = app(FileService::class);

        $uploaded = $service->upload($contextA, new FileUploadRequest(
            scope: new EnterpriseScope($contextA->organizationPublicId, $contextA->workspacePublicId),
            originalFilename: 'org-a.txt',
            mimeType: 'text/plain',
            sizeBytes: 4,
            contents: 'orga',
            visibility: 'organization',
        ));

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $service->download($contextB, $uploaded->publicId);
    }

    public function test_runtime_bridge_enables_storage_and_media_capabilities(): void
    {
        $context = $this->tenantContext();
        $runtime = app(EnterpriseRuntimeBridge::class)->resolve($context);

        $this->assertTrue($runtime->capabilityEnabled('storage'));
        $this->assertTrue($runtime->capabilityEnabled('media'));
    }

    public function test_upload_rejects_when_storage_capability_disabled(): void
    {
        config([
            'heos.enterprise.runtime_aware' => false,
            'heos.enterprise.files.enabled' => false,
        ]);

        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);

        app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'blocked.txt',
            mimeType: 'text/plain',
            sizeBytes: 3,
            contents: 'nope',
        ));
    }

    public function test_storage_port_reports_configured_disks(): void
    {
        $disks = app(StoragePort::class)->configuredDisks();

        $this->assertContains('local', $disks);
        $this->assertContains('public', $disks);
    }

    public function test_storage_health_assessment_is_healthy_with_fake_disks(): void
    {
        $context = $this->tenantContext();
        $health = app(EnterpriseStorageHealthService::class)->assess($context);

        $this->assertTrue($health['enabled']);
        $this->assertTrue($health['default_disk_writable']);
        $this->assertTrue($health['public_disk_writable']);
        $this->assertSame('healthy', $health['status']);
        $this->assertTrue($health['runtime_capabilities']['storage']);
    }

    public function test_doctor_includes_storage_health(): void
    {
        \Illuminate\Support\Facades\Artisan::call('heos:doctor', ['--json' => true]);

        $payload = json_decode(\Illuminate\Support\Facades\Artisan::output(), true);

        $this->assertTrue($payload['platform_summary']['enterprise']['files']);
        $this->assertArrayHasKey('storage', $payload['platform_summary']['enterprise']);
    }

    public function test_files_api_uploads_file(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->post('/api/v1/tenant/files', [
                'file' => UploadedFile::fake()->create('api-upload.pdf', 10, 'application/pdf'),
                'visibility' => 'workspace',
                'module_key' => 'demo',
            ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.original_filename', 'api-upload.pdf')
            ->assertJsonPath('data.visibility', 'workspace');

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_files_api_lists_files(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'listed.txt',
            mimeType: 'text/plain',
            sizeBytes: 4,
            contents: 'list',
            visibility: 'organization',
        ));

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/files')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_files_api_downloads_file(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $uploaded = app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'download-api.txt',
            mimeType: 'text/plain',
            sizeBytes: 12,
            contents: 'download-me',
            visibility: 'organization',
        ));

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->get('/api/v1/tenant/files/download/'.$uploaded->publicId);

        $response->assertOk();
        $this->assertSame('download-me', $response->streamedContent());
    }

    public function test_files_api_lists_entity_attachments(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            originalFilename: 'entity.txt',
            mimeType: 'text/plain',
            sizeBytes: 4,
            contents: 'ent',
            entityReference: new EntityReference('order', 'ord-1', 'demo'),
            visibility: 'organization',
        ));

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/files/entity?entity_type=order&entity_public_id=ord-1&module_key=demo')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_files_api_deletes_file(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $uploaded = app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'delete-me.txt',
            mimeType: 'text/plain',
            sizeBytes: 3,
            contents: 'del',
            visibility: 'organization',
        ));

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->deleteJson('/api/v1/tenant/files/'.$uploaded->publicId)
            ->assertOk();

        $this->assertSoftDeleted('platform_files', ['public_id' => $uploaded->publicId]);
    }

    public function test_file_service_port_uploads_via_adapter(): void
    {
        $context = $this->tenantContext();

        $result = app(FileServicePort::class)->upload(new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'port.txt',
            mimeType: 'text/plain',
            sizeBytes: 4,
            contents: 'port',
            uploadedMembershipPublicId: $context->membershipPublicId,
        ));

        $this->assertSame('port.txt', $result->originalFilename);
    }

    public function test_public_visibility_uses_public_disk(): void
    {
        $context = $this->tenantContext();

        $result = app(FileService::class)->upload($context, new FileUploadRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            originalFilename: 'public.png',
            mimeType: 'image/png',
            sizeBytes: 4,
            contents: 'png',
            visibility: FileVisibility::Public->value,
        ));

        $file = PlatformFile::query()->where('public_id', $result->publicId)->firstOrFail();
        $this->assertSame('public', $file->storage_disk);
    }

    public function test_access_denied_records_audit_event(): void
    {
        $ownerContext = $this->tenantContext();
        $service = app(FileService::class);

        $uploaded = $service->upload($ownerContext, new FileUploadRequest(
            scope: new EnterpriseScope($ownerContext->organizationPublicId, $ownerContext->workspacePublicId),
            originalFilename: 'denied.txt',
            mimeType: 'text/plain',
            sizeBytes: 4,
            contents: 'deny',
            visibility: 'private',
        ));

        try {
            $service->download($this->memberContext($ownerContext), $uploaded->publicId);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException) {
        }

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::FileAccessDenied->value)->exists());
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'files-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        $member = $this->createActiveUser();
        $memberRole = Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', 'member')
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $member->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $member,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }
}
