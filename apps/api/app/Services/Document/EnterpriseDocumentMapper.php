<?php

namespace App\Services\Document;

use App\Models\EnterpriseAttachment;
use App\Models\EnterpriseDocument;
use App\Models\EnterpriseDocumentActivity;
use App\Models\EnterpriseDocumentOcrResult;
use App\Models\EnterpriseDocumentPreview;
use App\Models\EnterpriseDocumentScan;
use App\Models\EnterpriseDocumentThumbnail;
use App\Models\EnterpriseDocumentVersion;
use App\Modules\Sdk\Document\Data\AttachmentReference;
use App\Modules\Sdk\Document\Data\DocumentOcrResult;
use App\Modules\Sdk\Document\Data\DocumentPreview;
use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Document\Data\DocumentScanResult;
use App\Modules\Sdk\Document\Data\DocumentThumbnail;
use App\Modules\Sdk\Document\Data\DocumentVersionReference;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;

class EnterpriseDocumentMapper
{
    public static function toReference(EnterpriseDocument $model): DocumentReference
    {
        $currentVersion = $model->relationLoaded('currentVersion')
            ? $model->currentVersion
            : null;

        return new DocumentReference(
            publicId: $model->public_id,
            title: $model->title,
            description: $model->description,
            status: self::enumValue($model->status, 'active'),
            visibility: self::enumValue($model->visibility, 'organization'),
            category: self::enumValue($model->category, 'general'),
            moduleKey: $model->module_key,
            currentVersionPublicId: $currentVersion?->public_id,
            currentVersionNumber: (int) ($currentVersion?->version_number ?? 0),
            platformFilePublicId: $currentVersion?->platform_file_public_id,
            organizationId: $model->organization_id,
            workspaceId: $model->workspace_id,
            retentionAction: self::enumValue($model->retention_action, 'none'),
            metadata: is_array($model->metadata) ? $model->metadata : [],
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    public static function toVersionReference(EnterpriseDocumentVersion $model): DocumentVersionReference
    {
        return new DocumentVersionReference(
            publicId: $model->public_id,
            documentPublicId: $model->document_public_id,
            versionNumber: (int) $model->version_number,
            platformFilePublicId: $model->platform_file_public_id,
            status: self::enumValue($model->status, 'active'),
            label: $model->label,
            metadata: is_array($model->metadata) ? $model->metadata : [],
            createdAt: $model->created_at?->toIso8601String(),
        );
    }

    public static function toAttachmentReference(EnterpriseAttachment $model): AttachmentReference
    {
        return new AttachmentReference(
            publicId: $model->public_id,
            documentPublicId: $model->document_public_id,
            subjectType: $model->subject_type,
            subjectPublicId: $model->subject_public_id,
            subjectModuleKey: $model->subject_module_key,
            subjectEntityKey: $model->subject_entity_key,
            status: self::enumValue($model->status, 'active'),
            metadata: is_array($model->metadata) ? $model->metadata : [],
            createdAt: $model->created_at?->toIso8601String(),
        );
    }

    public static function toPreview(EnterpriseDocumentPreview $model): DocumentPreview
    {
        return new DocumentPreview(
            publicId: $model->public_id,
            documentPublicId: $model->document_public_id,
            versionPublicId: $model->version_public_id,
            status: (string) $model->status,
            previewFormat: $model->preview_format,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toThumbnail(EnterpriseDocumentThumbnail $model): DocumentThumbnail
    {
        return new DocumentThumbnail(
            publicId: $model->public_id,
            documentPublicId: $model->document_public_id,
            versionPublicId: $model->version_public_id,
            status: (string) $model->status,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toScanResult(EnterpriseDocumentScan $model): DocumentScanResult
    {
        return new DocumentScanResult(
            publicId: $model->public_id,
            documentPublicId: $model->document_public_id,
            status: self::enumValue($model->status, 'pending'),
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    public static function toOcrResult(EnterpriseDocumentOcrResult $model): DocumentOcrResult
    {
        return new DocumentOcrResult(
            publicId: $model->public_id,
            documentPublicId: $model->document_public_id,
            status: self::enumValue($model->status, 'pending'),
            ocrText: $model->ocr_text,
            metadata: is_array($model->metadata) ? $model->metadata : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toActivityReference(EnterpriseDocumentActivity $model): array
    {
        return [
            'public_id' => $model->public_id,
            'document_public_id' => $model->document_public_id,
            'action' => $model->action,
            'before_state' => is_array($model->before_state) ? $model->before_state : [],
            'after_state' => is_array($model->after_state) ? $model->after_state : [],
            'metadata' => is_array($model->metadata) ? $model->metadata : [],
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Builder<EnterpriseDocument>  $query
     */
    public static function applyWorkspaceScope(Builder $query, ?string $workspaceId): Builder
    {
        if ($workspaceId === null) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($workspaceId) {
            $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
        });
    }

    public static function enumValue(mixed $value, string $default): string
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
