<?php

namespace Tests\Feature\Services\Document;

use App\Enums\AuditAction;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\EnterpriseAttachment;
use App\Models\EnterpriseDocument;
use App\Models\Role;
use App\Modules\Sdk\Document\Contracts\AttachmentRepository;
use App\Modules\Sdk\Document\Contracts\DocumentOcrProvider;
use App\Modules\Sdk\Document\Contracts\DocumentPermissionResolver;
use App\Modules\Sdk\Document\Contracts\DocumentPreviewProvider;
use App\Modules\Sdk\Document\Contracts\DocumentRepository;
use App\Modules\Sdk\Document\Contracts\DocumentScanner;
use App\Modules\Sdk\Document\Contracts\DocumentStorageProvider;
use App\Modules\Sdk\Document\Contracts\DocumentThumbnailProvider;
use App\Modules\Sdk\Document\Contracts\DocumentVersionManager;
use App\Modules\Sdk\Document\Data\AttachmentReference;
use App\Modules\Sdk\Document\Data\AttachmentRequest;
use App\Modules\Sdk\Document\Data\DocumentHealthReport;
use App\Modules\Sdk\Document\Data\DocumentOcrResult;
use App\Modules\Sdk\Document\Data\DocumentPreview;
use App\Modules\Sdk\Document\Data\DocumentQuotaReport;
use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Document\Data\DocumentRetentionRule;
use App\Modules\Sdk\Document\Data\DocumentScanResult;
use App\Modules\Sdk\Document\Data\DocumentStatistics;
use App\Modules\Sdk\Document\Data\DocumentThumbnail;
use App\Modules\Sdk\Document\Data\DocumentUpdateRequest;
use App\Modules\Sdk\Document\Data\DocumentUploadRequest;
use App\Modules\Sdk\Document\Data\DocumentVersionReference;
use App\Modules\Sdk\Document\Data\DocumentVersionRequest;
use App\Modules\Sdk\Document\Enums\AttachmentSubjectType;
use App\Modules\Sdk\Document\Exceptions\DocumentNotFoundException;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Modules\Sdk\Report\Data\ReportExportDefinition;
use App\Services\DataRepository\EnterpriseEntityRecordAttachmentBridge;
use App\Services\Document\EnterpriseAttachmentService;
use App\Services\Document\EnterpriseDocumentDevelopmentService;
use App\Services\Document\EnterpriseDocumentHealthService;
use App\Services\Document\EnterpriseDocumentPermissionService;
use App\Services\Form\DynamicFormRegistryService;
use App\Services\Form\DynamicFormSubmissionService;
use App\Services\Module\ModuleDoctorService;
use App\Services\Report\DynamicReportExportService;
use App\Services\Report\DynamicReportRegistryService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M6EnterpriseDocumentEngineTest extends TestCase
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

    public function test_document_reference_dto_roundtrip(): void
    {
        $reference = DocumentReference::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000601',
            'title' => 'Contract',
            'status' => 'active',
            'visibility' => 'organization',
            'category' => 'contract',
            'module_key' => 'demo.core',
            'current_version_number' => 2,
        ]);

        $roundtrip = DocumentReference::fromArray($reference->toArray());

        $this->assertSame('Contract', $roundtrip->title);
        $this->assertSame(2, $roundtrip->currentVersionNumber);
    }

    public function test_document_version_reference_dto_roundtrip(): void
    {
        $reference = DocumentVersionReference::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000602',
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'version_number' => 1,
            'platform_file_public_id' => '01900000-0000-7000-8000-000000000603',
            'status' => 'active',
        ]);

        $this->assertSame(1, DocumentVersionReference::fromArray($reference->toArray())->versionNumber);
    }

    public function test_attachment_reference_dto_roundtrip(): void
    {
        $reference = AttachmentReference::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000604',
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'subject_type' => 'entity_record',
            'subject_public_id' => '01900000-0000-7000-8000-000000000605',
            'subject_module_key' => 'demo.core',
            'subject_entity_key' => 'asset',
        ]);

        $this->assertSame('entity_record', $reference->jsonSerialize()['subject_type']);
    }

    public function test_document_upload_request_dto_roundtrip(): void
    {
        $request = DocumentUploadRequest::fromArray([
            'title' => 'Invoice',
            'original_filename' => 'invoice.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'contents' => 'pdf',
            'category' => 'invoice',
        ]);

        $this->assertSame('invoice.pdf', $request->toArray()['original_filename']);
    }

    public function test_document_update_request_dto_roundtrip(): void
    {
        $request = DocumentUpdateRequest::fromArray([
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'title' => 'Updated Title',
        ]);

        $this->assertSame('Updated Title', $request->jsonSerialize()['title']);
    }

    public function test_document_version_request_dto_roundtrip(): void
    {
        $request = DocumentVersionRequest::fromArray([
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'original_filename' => 'v2.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'contents' => 'v2',
        ]);

        $this->assertSame('v2.pdf', $request->toArray()['original_filename']);
    }

    public function test_attachment_request_dto_roundtrip(): void
    {
        $request = AttachmentRequest::fromArray([
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'subject_type' => 'form_submission',
            'subject_public_id' => '01900000-0000-7000-8000-000000000606',
        ]);

        $this->assertSame('form_submission', $request->toArray()['subject_type']);
    }

    public function test_document_preview_dto_roundtrip(): void
    {
        $preview = DocumentPreview::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000607',
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'status' => 'pending',
        ]);

        $this->assertSame('pending', $preview->jsonSerialize()['status']);
    }

    public function test_document_thumbnail_dto_roundtrip(): void
    {
        $thumbnail = DocumentThumbnail::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000608',
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'status' => 'pending',
        ]);

        $this->assertSame('pending', $thumbnail->toArray()['status']);
    }

    public function test_document_scan_result_dto_roundtrip(): void
    {
        $scan = DocumentScanResult::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000609',
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'status' => 'pending',
        ]);

        $this->assertSame('pending', $scan->jsonSerialize()['status']);
    }

    public function test_document_ocr_result_dto_roundtrip(): void
    {
        $ocr = DocumentOcrResult::fromArray([
            'public_id' => '01900000-0000-7000-8000-000000000610',
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'status' => 'pending',
            'ocr_text' => 'sample text',
        ]);

        $this->assertSame('sample text', $ocr->toArray()['ocr_text']);
    }

    public function test_document_retention_rule_dto_roundtrip(): void
    {
        $rule = DocumentRetentionRule::fromArray([
            'document_public_id' => '01900000-0000-7000-8000-000000000601',
            'action' => 'archive',
        ]);

        $this->assertSame('archive', $rule->jsonSerialize()['action']);
    }

    public function test_document_quota_report_dto_roundtrip(): void
    {
        $report = DocumentQuotaReport::fromArray([
            'documents_count' => 2,
            'attachments_count' => 1,
            'total_bytes' => 4096,
            'quota_bytes' => 5368709120,
        ]);

        $this->assertSame(4096, $report->toArray()['total_bytes']);
    }

    public function test_document_statistics_dto_roundtrip(): void
    {
        $statistics = DocumentStatistics::fromArray([
            'documents' => 3,
            'versions' => 4,
            'attachments' => 2,
            'previews' => 1,
            'scans' => 1,
            'ocr_results' => 1,
            'activity_logs' => 5,
        ]);

        $this->assertSame(3, $statistics->jsonSerialize()['documents']);
    }

    public function test_document_health_report_dto_roundtrip(): void
    {
        $report = DocumentHealthReport::fromArray([
            'enabled' => true,
            'documents' => 1,
            'versions' => 1,
            'attachments' => 0,
            'warnings' => [],
            'status' => 'healthy',
            'missing_tables' => [],
        ]);

        $this->assertSame('healthy', $report->toArray()['status']);
    }

    public function test_document_contracts_are_bound(): void
    {
        $this->assertInstanceOf(DocumentRepository::class, app(DocumentRepository::class));
        $this->assertInstanceOf(DocumentStorageProvider::class, app(DocumentStorageProvider::class));
        $this->assertInstanceOf(DocumentVersionManager::class, app(DocumentVersionManager::class));
        $this->assertInstanceOf(AttachmentRepository::class, app(AttachmentRepository::class));
        $this->assertInstanceOf(DocumentPreviewProvider::class, app(DocumentPreviewProvider::class));
        $this->assertInstanceOf(DocumentThumbnailProvider::class, app(DocumentThumbnailProvider::class));
        $this->assertInstanceOf(DocumentScanner::class, app(DocumentScanner::class));
        $this->assertInstanceOf(DocumentOcrProvider::class, app(DocumentOcrProvider::class));
        $this->assertInstanceOf(DocumentPermissionResolver::class, app(DocumentPermissionResolver::class));
    }

    public function test_upload_creates_document_and_version(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $document = $this->uploadSampleDocument($context, 'report.pdf', 'Report content');

        $this->assertNotEmpty($document->publicId);
        $this->assertDatabaseHas('enterprise_documents', ['public_id' => $document->publicId]);
        $this->assertDatabaseHas('enterprise_document_versions', [
            'document_public_id' => $document->publicId,
            'version_number' => 1,
        ]);
    }

    public function test_upload_records_audit_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $this->uploadSampleDocument($context, 'audit.txt', 'audit');

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentUploaded->value)->exists());
    }

    public function test_upload_records_activity(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $document = $this->uploadSampleDocument($context, 'activity.txt', 'activity');

        $this->assertDatabaseHas('enterprise_document_activity', [
            'document_public_id' => $document->publicId,
            'action' => 'uploaded',
        ]);
    }

    public function test_update_document_metadata(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $document = $this->uploadSampleDocument($context, 'update.txt', 'before');

        $updated = $service->update($context, DocumentUpdateRequest::fromArray([
            'document_public_id' => $document->publicId,
            'title' => 'Updated Document',
            'metadata' => ['reviewed' => true],
        ]));

        $this->assertSame('Updated Document', $updated->title);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentUpdated->value)->exists());
    }

    public function test_delete_document_soft_deletes_record(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $document = $this->uploadSampleDocument($context, 'delete.txt', 'delete me');
        $service->delete($context, $document->publicId);

        $this->assertSoftDeleted('enterprise_documents', ['public_id' => $document->publicId]);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentDeleted->value)->exists());
    }

    public function test_create_version_increments_version_number(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $document = $this->uploadSampleDocument($context, 'v1.txt', 'version one');

        $version = $service->createVersion($context, new DocumentVersionRequest(
            documentPublicId: $document->publicId,
            originalFilename: 'v2.txt',
            mimeType: 'text/plain',
            sizeBytes: 11,
            contents: 'version two',
            label: 'v2',
            metadata: [],
        ));

        $this->assertSame(2, $version->versionNumber);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentVersionCreated->value)->exists());
    }

    public function test_list_versions_returns_history(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $document = $this->uploadSampleDocument($context, 'history.txt', 'v1');
        $service->createVersion($context, new DocumentVersionRequest(
            documentPublicId: $document->publicId,
            originalFilename: 'history-v2.txt',
            mimeType: 'text/plain',
            sizeBytes: 2,
            contents: 'v2',
        ));

        $versions = $service->listVersions($context, $document->publicId);

        $this->assertCount(2, $versions);
    }

    public function test_restore_version_sets_current_version(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $document = $this->uploadSampleDocument($context, 'restore.txt', 'v1');
        $service->createVersion($context, new DocumentVersionRequest(
            documentPublicId: $document->publicId,
            originalFilename: 'restore-v2.txt',
            mimeType: 'text/plain',
            sizeBytes: 2,
            contents: 'v2',
        ));

        $versionOne = collect($service->listVersions($context, $document->publicId))
            ->first(fn (DocumentVersionReference $version) => $version->versionNumber === 1);

        $restored = $service->restoreVersion($context, $document->publicId, $versionOne->publicId);

        $this->assertSame(1, $restored->versionNumber);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentVersionRestored->value)->exists());
    }

    public function test_attach_and_detach_document(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $document = $this->uploadSampleDocument($context, 'attach.txt', 'attach');

        $attachment = $service->attach($context, new AttachmentRequest(
            documentPublicId: $document->publicId,
            subjectType: AttachmentSubjectType::EntityRecord->value,
            subjectPublicId: '01900000-0000-7000-8000-000000000701',
            subjectModuleKey: 'demo.core',
            subjectEntityKey: 'asset',
            metadata: [],
        ));

        $this->assertDatabaseHas('enterprise_attachments', ['public_id' => $attachment->publicId]);

        $service->detach($context, $attachment->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentAttached->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentDetached->value)->exists());
    }

    public function test_list_attachments_for_document(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $document = $this->uploadSampleDocument($context, 'list-attach.txt', 'attach');
        $service->attach($context, new AttachmentRequest(
            documentPublicId: $document->publicId,
            subjectType: AttachmentSubjectType::Generic->value,
            subjectPublicId: '01900000-0000-7000-8000-000000000702',
            subjectModuleKey: null,
            subjectEntityKey: null,
            metadata: [],
        ));

        $this->assertCount(1, $service->listAttachments($context, $document->publicId));
    }

    public function test_owner_can_read_documents(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $resolver = app(EnterpriseDocumentPermissionService::class);

        $this->assertTrue($resolver->canRead($context));
        $this->assertTrue($resolver->canUpload($context));
    }

    public function test_member_can_upload_and_attach_but_not_delete(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        $resolver = app(EnterpriseDocumentPermissionService::class);

        $this->assertTrue($resolver->canRead($memberContext));
        $this->assertTrue($resolver->canUpload($memberContext));
        $this->assertTrue($resolver->canAttach($memberContext));
        $this->assertFalse($resolver->canDelete($memberContext));
    }

    public function test_viewer_can_read_only(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);
        $resolver = app(EnterpriseDocumentPermissionService::class);

        $this->assertTrue($resolver->canRead($viewerContext));
        $this->assertFalse($resolver->canUpload($viewerContext));
        $this->assertFalse($resolver->canUpdate($viewerContext));
    }

    public function test_viewer_cannot_upload_via_api(): void
    {
        $ownerContext = $this->tenantContext();
        $viewerContext = $this->viewerContext($ownerContext);

        $response = $this->withHeaders($this->tenantHeaders($viewerContext))
            ->post('/api/v1/tenant/documents', [
                'file' => UploadedFile::fake()->create('denied.txt', 10, 'text/plain'),
            ]);

        $response->assertForbidden();
    }

    public function test_member_can_upload_via_api(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);

        $response = $this->withHeaders($this->tenantHeaders($memberContext))
            ->post('/api/v1/tenant/documents', [
                'file' => UploadedFile::fake()->create('allowed.txt', 10, 'text/plain'),
                'title' => 'Allowed Upload',
            ]);

        $response->assertCreated();
    }

    public function test_api_list_and_show_documents(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'api.txt', 'api content');

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/documents')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/documents/'.$document->publicId)
            ->assertOk()
            ->assertJsonPath('data.public_id', $document->publicId);
    }

    public function test_api_update_and_delete_document(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'lifecycle.txt', 'lifecycle');

        $this->withHeaders($this->tenantHeaders($context))
            ->patchJson('/api/v1/tenant/documents/'.$document->publicId, [
                'title' => 'Lifecycle Updated',
            ])
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/documents/'.$document->publicId)
            ->assertOk();
    }

    public function test_api_versions_and_restore_endpoints(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'versions.txt', 'v1');

        $versionResponse = $this->withHeaders($this->tenantHeaders($context))
            ->post('/api/v1/tenant/documents/'.$document->publicId.'/versions', [
                'file' => UploadedFile::fake()->create('v2.txt', 10, 'text/plain'),
            ]);

        $versionResponse->assertCreated();
        $versionPublicId = $versionResponse->json('data.public_id');

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/documents/'.$document->publicId.'/versions')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/documents/'.$document->publicId.'/versions/'.$versionPublicId.'/restore')
            ->assertOk();
    }

    public function test_api_attachments_endpoints(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'api-attach.txt', 'attach');

        $create = $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/documents/'.$document->publicId.'/attachments', [
                'subject_type' => 'generic',
                'subject_public_id' => '01900000-0000-7000-8000-000000000703',
            ]);

        $create->assertCreated();
        $attachmentPublicId = $create->json('data.public_id');

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/documents/'.$document->publicId.'/attachments')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->deleteJson('/api/v1/tenant/documents/attachments/'.$attachmentPublicId)
            ->assertOk();
    }

    public function test_api_preview_thumbnail_scan_and_ocr_endpoints(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'processing.txt', 'processing');

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/documents/'.$document->publicId.'/preview')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/documents/'.$document->publicId.'/thumbnail')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/documents/'.$document->publicId.'/scan')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->postJson('/api/v1/tenant/documents/'.$document->publicId.'/ocr')
            ->assertOk();
    }

    public function test_api_activity_health_and_statistics_endpoints(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'metrics.txt', 'metrics');

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/documents/'.$document->publicId.'/activity')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/documents/health')
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/documents/statistics')
            ->assertOk();
    }

    public function test_request_preview_records_audit_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);
        $document = $this->uploadSampleDocument($context, 'preview.txt', 'preview');

        $service->requestPreview($context, $document->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentPreviewRequested->value)->exists());
    }

    public function test_request_thumbnail_records_audit_event(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);
        $document = $this->uploadSampleDocument($context, 'thumb.txt', 'thumb');

        $service->requestThumbnail($context, $document->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentThumbnailRequested->value)->exists());
    }

    public function test_scan_and_ocr_record_audit_events(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);
        $document = $this->uploadSampleDocument($context, 'scan.txt', 'scan');

        $service->scan($context, $document->publicId);
        $service->ocr($context, $document->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentScanRequested->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::DocumentOcrRequested->value)->exists());
    }

    public function test_health_service_assessment(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $this->uploadSampleDocument($context, 'health.txt', 'health');

        $assessment = app(EnterpriseDocumentHealthService::class)->assess($context);

        $this->assertTrue($assessment['enabled']);
        $this->assertSame(1, $assessment['documents']);
    }

    public function test_statistics_and_quota_reports(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);
        $this->uploadSampleDocument($context, 'quota.txt', 'quota content');

        $statistics = $service->statistics($context);
        $quota = $service->quota($context);

        $this->assertSame(1, $statistics->documents);
        $this->assertSame(1, $quota->documentsCount);
        $this->assertSame((int) config('heos.enterprise.documents.quota_bytes'), $quota->quotaBytes);
    }

    public function test_module_doctor_includes_documents_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('documents', $report->platformSummary['enterprise'] ?? []);
    }

    public function test_config_enables_documents(): void
    {
        $this->assertTrue((bool) config('heos.enterprise.documents.enabled'));
        $this->assertSame(5_368_709_120, (int) config('heos.enterprise.documents.quota_bytes'));
    }

    public function test_runtime_contribution_includes_documents(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $contribution = app(EnterpriseDocumentHealthService::class)->runtimeContribution($context);

        $this->assertTrue($contribution['enabled']);
        $this->assertArrayHasKey('documents', $contribution);
    }

    public function test_permission_catalog_has_one_hundred_permissions(): void
    {
        $this->seedHeosPermissions();
        $this->assertPermissionCatalogComplete();
    }

    public function test_show_missing_document_throws_not_found(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $this->expectException(DocumentNotFoundException::class);
        $service->show($context, '01900000-0000-7000-8000-000000000999');
    }

    public function test_create_placeholder_document_for_export(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $document = $service->createPlaceholder(
            $context,
            'demo.core.summary export',
            'demo.core',
            ['source' => 'report_export'],
        );

        $this->assertSame('export', $document->category);
        $this->assertTrue($document->metadata['placeholder'] ?? false);
    }

    public function test_report_export_store_as_document_creates_placeholder(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);

        $moduleKey = 'reports.'.uniqid();
        $reportKey = 'summary';

        app(DynamicReportRegistryService::class)->register([
            'module_key' => $moduleKey,
            'report_key' => $reportKey,
            'name' => 'Summary Report',
            'type' => 'list',
            'status' => 'registered',
            'visibility' => 'organization',
            'columns' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => 'text',
            ]],
            'aggregates' => [[
                'key' => 'total_count',
                'function' => 'count',
                'label' => 'Total Records',
            ]],
            'metadata' => ['owner' => 'platform'],
        ]);

        $export = app(DynamicReportExportService::class)->export(
            app(DynamicReportRegistryService::class)->find($moduleKey, $reportKey),
            ReportExportDefinition::fromArray([
                'export_format' => 'csv',
                'metadata' => ['store_as_document' => true],
            ]),
        );

        $this->assertNotEmpty($export->fileReference['document_public_id'] ?? null);
        $this->assertDatabaseHas('enterprise_documents', [
            'public_id' => $export->fileReference['document_public_id'],
        ]);
    }

    public function test_form_submission_attaches_document_fields(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'form-file.txt', 'form file');

        $definition = FormDefinition::fromArray([
            'module_key' => 'forms.'.uniqid(),
            'form_key' => 'upload',
            'name' => 'Upload Form',
            'type' => 'create',
            'fields' => [[
                'key' => 'attachment',
                'label' => 'Attachment',
                'type' => 'file',
            ]],
        ]);
        $definition = app(DynamicFormRegistryService::class)->register($definition);

        app(DynamicFormSubmissionService::class)->submit(
            FormSubmissionRequest::fromArray([
                'module_key' => $definition->moduleKey,
                'form_key' => $definition->formKey,
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace->id,
                'values' => [
                    'attachment' => ['document_public_id' => $document->publicId],
                ],
            ]),
            $definition,
        );

        $this->assertTrue(
            EnterpriseAttachment::query()
                ->where('document_public_id', $document->publicId)
                ->where('subject_type', AttachmentSubjectType::FormSubmission->value)
                ->exists(),
        );
    }

    public function test_entity_record_attachment_bridge_uses_document_service(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'record-bridge.txt', 'bridge');

        app(EnterpriseEntityRecordAttachmentBridge::class)->attachBestEffort(
            'demo.core',
            'asset',
            '01900000-0000-7000-8000-000000000704',
            $document->publicId,
        );

        $this->assertDatabaseHas('enterprise_attachments', [
            'document_public_id' => $document->publicId,
            'subject_type' => AttachmentSubjectType::EntityRecord->value,
            'subject_public_id' => '01900000-0000-7000-8000-000000000704',
        ]);
    }

    public function test_list_documents_returns_uploaded_records(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);

        $this->uploadSampleDocument($context, 'listed.txt', 'listed');

        $this->assertCount(1, $service->list($context));
    }

    public function test_apply_retention_requires_manage_permission(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        app()->instance(TenantContext::class, $ownerContext);
        $service = app(EnterpriseDocumentDevelopmentService::class);
        $document = $this->uploadSampleDocument($ownerContext, 'retention.txt', 'retention');

        app()->instance(TenantContext::class, $memberContext);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->applyRetention($memberContext, DocumentRetentionRule::fromArray([
            'document_public_id' => $document->publicId,
            'action' => 'archive',
        ]));
    }

    public function test_owner_can_apply_retention(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseDocumentDevelopmentService::class);
        $document = $this->uploadSampleDocument($context, 'retain.txt', 'retain');

        $rule = $service->applyRetention($context, DocumentRetentionRule::fromArray([
            'document_public_id' => $document->publicId,
            'action' => 'archive',
        ]));

        $this->assertSame('archive', $rule->action);
    }

    public function test_attachment_service_lists_for_subject(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $service = app(EnterpriseAttachmentService::class);
        $document = $this->uploadSampleDocument($context, 'subject.txt', 'subject');
        $subjectPublicId = '01900000-0000-7000-8000-000000000705';

        $service->attach(
            $context->organization->id,
            $context->workspace->id,
            new AttachmentRequest(
                documentPublicId: $document->publicId,
                subjectType: AttachmentSubjectType::Generic->value,
                subjectPublicId: $subjectPublicId,
                subjectModuleKey: null,
                subjectEntityKey: null,
                metadata: [],
            ),
        );

        $this->assertCount(1, $service->listForSubject(
            $context->organization->id,
            $context->workspace->id,
            AttachmentSubjectType::Generic->value,
            $subjectPublicId,
        ));
    }

    public function test_api_response_uses_public_ids_only(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'public-id.txt', 'public id');

        $response = $this->withHeaders($this->tenantHeaders($context))
            ->getJson('/api/v1/tenant/documents/'.$document->publicId);

        $response->assertOk();
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_document_model_persists_expected_fields(): void
    {
        $context = $this->tenantContext();
        app()->instance(TenantContext::class, $context);
        $document = $this->uploadSampleDocument($context, 'model.txt', 'model', [
            'module_key' => 'demo.core',
            'category' => 'report',
        ]);

        $model = EnterpriseDocument::query()->where('public_id', $document->publicId)->firstOrFail();

        $this->assertSame('demo.core', $model->module_key);
        $this->assertSame('report', $model->category->value);
    }

    private function uploadSampleDocument(
        TenantContext $context,
        string $filename,
        string $contents,
        array $options = [],
    ): DocumentReference {
        app()->instance(TenantContext::class, $context);

        return app(EnterpriseDocumentDevelopmentService::class)->upload($context, new DocumentUploadRequest(
            title: $options['title'] ?? $filename,
            originalFilename: $filename,
            mimeType: $options['mime_type'] ?? 'text/plain',
            sizeBytes: strlen($contents),
            contents: $contents,
            description: $options['description'] ?? null,
            visibility: $options['visibility'] ?? 'organization',
            category: $options['category'] ?? 'general',
            moduleKey: $options['module_key'] ?? null,
            metadata: $options['metadata'] ?? [],
        ));
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'enterprise-documents-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'member');
    }

    private function viewerContext(TenantContext $ownerContext): TenantContext
    {
        return $this->roleContext($ownerContext, 'viewer');
    }

    private function roleContext(TenantContext $ownerContext, string $roleKey): TenantContext
    {
        $user = $this->createActiveUser();
        $role = Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', $roleKey)
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $user->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $user,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }

    /**
     * @return array<string, string>
     */
    private function tenantHeaders(TenantContext $context): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueToken($context->user),
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }
}
