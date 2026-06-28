<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseDocumentPreview extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'enterprise_document_id',
        'document_public_id',
        'version_public_id',
        'enterprise_document_version_id',
        'organization_id',
        'workspace_id',
        'status',
        'preview_format',
        'platform_file_public_id',
        'platform_file_id',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDocument::class, 'enterprise_document_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDocumentVersion::class, 'enterprise_document_version_id');
    }

    public function platformFile(): BelongsTo
    {
        return $this->belongsTo(PlatformFile::class);
    }
}
