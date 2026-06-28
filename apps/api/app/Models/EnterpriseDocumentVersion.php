<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Document\Enums\DocumentVersionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseDocumentVersion extends Model
{
    use HasHeosPublicId, HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'enterprise_document_id',
        'document_public_id',
        'organization_id',
        'workspace_id',
        'version_number',
        'platform_file_public_id',
        'platform_file_id',
        'status',
        'label',
        'metadata',
        'created_by_user_id',
        'created_by_membership_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'version_number' => 'integer',
            'status' => DocumentVersionStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDocument::class, 'enterprise_document_id');
    }

    public function platformFile(): BelongsTo
    {
        return $this->belongsTo(PlatformFile::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
