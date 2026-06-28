<?php

namespace App\Models;

use App\Models\Concerns\HasHeosPublicId;
use App\Modules\Sdk\Document\Enums\DocumentCategory;
use App\Modules\Sdk\Document\Enums\DocumentRetentionAction;
use App\Modules\Sdk\Document\Enums\DocumentStatus;
use App\Modules\Sdk\Document\Enums\DocumentVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseDocument extends Model
{
    use HasHeosPublicId, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'organization_id',
        'workspace_id',
        'title',
        'description',
        'status',
        'visibility',
        'category',
        'module_key',
        'current_version_id',
        'retention_action',
        'metadata',
        'created_by_user_id',
        'created_by_membership_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => DocumentStatus::class,
            'visibility' => DocumentVisibility::class,
            'category' => DocumentCategory::class,
            'retention_action' => DocumentRetentionAction::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDocumentVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EnterpriseDocumentVersion::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EnterpriseAttachment::class);
    }

    public function previews(): HasMany
    {
        return $this->hasMany(EnterpriseDocumentPreview::class);
    }

    public function thumbnails(): HasMany
    {
        return $this->hasMany(EnterpriseDocumentThumbnail::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(EnterpriseDocumentScan::class);
    }

    public function ocrResults(): HasMany
    {
        return $this->hasMany(EnterpriseDocumentOcrResult::class);
    }

    public function activity(): HasMany
    {
        return $this->hasMany(EnterpriseDocumentActivity::class);
    }
}
